<?php

namespace App\Droit\Shop\Order\Worker;

use App\Droit\Shop\Order\Worker\OrderMakerInterface;

use App\Droit\Shop\Order\Repo\OrderInterface;
use App\Droit\Shop\Product\Repo\ProductInterface;
use App\Droit\Shop\Shipping\Repo\ShippingInterface;
use App\Droit\Adresse\Repo\AdresseInterface;
use App\Droit\Generate\Pdf\PdfGeneratorInterface;
use App\Droit\Shop\Cart\Repo\CartInterface;

class OrderMaker implements OrderMakerInterface{

    protected $order;
    protected $product;
    protected $shipping;
    protected $adresse;
    protected $generator;
    protected $cart;

    public function __construct(OrderInterface $order, ProductInterface $product, ShippingInterface $shipping, AdresseInterface $adresse, PdfGeneratorInterface $generator, CartInterface $cart)
    {
        $this->order     = $order;
        $this->product   = $product;
        $this->shipping  = $shipping;
        $this->adresse   = $adresse;
        $this->generator = $generator;
        $this->cart      = $cart;
    }

    /*
     * Prepare data and insert order in DB
     * We can pass shipping already calculated and coupon from shop
     * Generate a invoice in pdf and add messages and/or change TVA
     * */
    public function make($commande, $shipping = null, $coupon = null)
    {
        $data  = $this->prepare($commande, $shipping, $coupon);

        $order = $this->insert($data);

        // Update Qty of products
        $this->resetQty($order,'-');

        // Create invoice for order
        if(isset($commande['tva']) && !empty(array_filter($commande['tva'])))
            $this->generator->setTva(array_filter($commande['tva']));

        if(isset($commande['message']) && !empty(array_filter($commande['message'])))
            $this->generator->setMsg(array_filter($commande['message']));

        $this->generator->factureOrder($order->id);

        return $order;
    }

    /*
    * Prepare data for order
    * From frontend and backend
    * */
    public function prepare($order = null, $shipping = null, $coupon = null)
    {
        $data = [
            'order_no'    => $this->order->newOrderNumber(),
            'amount'      => isset($order['admin']) ? $this->total($order['order']) : \Cart::total() * 100,
            'coupon_id'   => ($coupon ? $coupon['id'] : null),
            'shipping_id' => isset($order['admin']) ? $this->getShipping($order) : $shipping->id,
            'payement_id' => 1,
            'products'    => isset($order['admin']) ? $this->getProducts($order['order']) : $this->getProductsCart(\Cart::content())
        ];

        $user = isset($order['admin']) ? $this->getUser($order) : ['user_id' => \Auth::user()->id];
        $data = array_merge($user,$data);

        return $data;
    }

    /*
    * Insert new order
    * Save the cart if any
    * */
    public function insert($data)
    {
        $order = $this->order->create($data);

        $cart  = \Cart::content();

        if(!$order && !empty($cart) && !$cart->isEmpty())
        {
            $this->cart->create([
                'user_id'   => $data['user_id'],
                'cart'      => serialize($cart),
                'coupon_id' => $data['coupon_id']
            ]);

            \Log::error('Problème lors de la commande'. serialize($data));

            throw new \App\Exceptions\OrderCreationException('Problème lors de la commande');
        }

        return $order;
    }

    /*
     *  Get the user or make new adresse from backend
     * */
    public function getUser($order)
    {
        if(!isset($order['user_id']))
        {
            $adresse = $this->adresse->create($order['adresse']);
            return ['adresse_id' => $adresse->id];
        }
        else
        {
            return ['user_id' => $order['user_id']];
        }
    }

    /*
    * Form admin the values for rabais and free can be send as null in the order array
    * Unset empty elements to create new order
    * */
    public function removeEmpty($items)
    {
        foreach($items as $key => $value)
        {
            if(is_null($value) || $value == '')
                unset($items[$key]);
        }

        return $items;
    }

    /*
    * Count qty for each product in order
    * */
    public function getCountProducts($order)
    {
        $products = $order->products->groupBy('id');

        foreach($products as $id => $product)
        {
            $count[$id] = $product->sum(function ($item) {
                return count($item['id']);
            });
        }

        return $count;
    }

    /*
     * Reset qty of products when canceling order
     * */
    public function resetQty($order,$operator)
    {
        $products = $this->getQty($order);

        if(!empty($products))
        {
            foreach($products as $product_id => $qty)
            {
                $this->product->sku($product_id, $qty, $operator);
            }
        }
    }

    /*
    * Get qty for each product
    *
    * Return [21 => 1, 3 => 2, 223 => 1] (product_id => qty)
    * */
    public function getQty($order)
    {
        return $products = $order->products->groupBy('id')->map(function ($group) {
            return $group->sum(function ($item) {
                return count($item['id']);
            });
        });
    }

    /*
    * Get products id (from qty) from cart instance
    *
    * Return [55,55,54,34]
    * */
    public function getProductsCart($cart)
    {
        $ids = [];

        $cart->each(function($product) use (&$ids)
        {
            for($x = 0; $x < $product->qty; $x++)
            {
                $ids[] = $product->id;
            }
        });

        return $ids;
    }

    /*
    * Get products id from form
    *
    * $expected = [
        [1 => ['isFree' => 1]],
        [2 => ['isFree' => null,'rabais' => 10]],
        [2 => ['isFree' => null,'rabais' => 10]],
        [3 => ['isFree' => null]]
      ];
    * */
    public function getProducts($order)
    {
        $ids = [];

        $products = new \Illuminate\Support\Collection($order['products']);

        $data['qty']     = $this->removeEmpty($order['qty']);
        $data['gratuit'] = (isset($order['gratuit']) ? $this->removeEmpty($order['gratuit']) : []);
        $data['rabais']  = (isset($order['rabais']) ?  $this->removeEmpty($order['rabais'])  : []);

        $products->map(function($product,$index) use (&$ids, $data)
        {
            for($x = 0; $x < $data['qty'][$index]; $x++)
            {
                $list['id']     = $product;
                $list['isFree'] = (isset($data['gratuit'][$index]) ? 1 : null);
                $list['rabais'] = (isset($data['rabais'][$index]) ? $data['rabais'][$index] : null);

                $ids[] = $list;
            }
        });

        return $ids;
    }

    /*
     *  Total for price and weight
     * */
    public function total($commande, $proprety = 'price')
    {
        $total    = 0;
        $products = new \Illuminate\Support\Collection($commande['products']);

        $data['qty']     = $this->removeEmpty($commande['qty']);
        $data['gratuit'] = (isset($commande['gratuit']) ? $this->removeEmpty($commande['gratuit']) : []);
        $data['rabais']  = (isset($commande['rabais']) ?  $this->removeEmpty($commande['rabais'])  : []);

        $products->map(function($product_id,$index) use (&$total, $data, $proprety)
        {
            $product = $this->product->find($product_id);

            for($x = 0; $x < $data['qty'][$index]; $x++)
            {
                if($proprety == 'price' && !isset($data['gratuit'][$index]) && isset($data['rabais'][$index]))
                {
                    $total += $product->$proprety - ( $product->$proprety * ($data['rabais'][$index]/100) );
                }
                elseif( ($proprety == 'price' && !isset($data['gratuit'][$index])) || $proprety != 'price')
                {
                    $total += $product->$proprety;
                }
            }
        });

        return $total;
    }

    /*
     * Get Shipping from the weight or test if free request from backend
     **/
    public function getShipping($order)
    {
        $weight   = $this->total($order['order'], 'weight');
        $weight   = isset($order['free']) ? null : $weight;
        $shipping = $this->shipping->getShipping($weight);

        return $shipping->id;
    }

}