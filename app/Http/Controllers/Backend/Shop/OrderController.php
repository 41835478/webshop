<?php
namespace App\Http\Controllers\Backend\Shop;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Droit\Shop\Product\Repo\ProductInterface;
use App\Droit\Shop\Order\Repo\OrderInterface;
use App\Droit\Shop\Order\Worker\OrderAdminWorkerInterface;
use App\Droit\Shop\Categorie\Repo\CategorieInterface;
use App\Droit\Adresse\Repo\AdresseInterface;
use App\Droit\Shop\Shipping\Repo\ShippingInterface;
use App\Droit\Generate\Pdf\PdfGeneratorInterface;

class OrderController extends Controller {

	protected $product;
    protected $categorie;
    protected $order;
    protected $generator;
    protected $pdfgenerator;
    protected $worker;
    protected $adresse;
    protected $shipping;
    protected $helper;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(
        ProductInterface $product,
        CategorieInterface $categorie,
        OrderInterface $order,
        OrderAdminWorkerInterface $worker,
        AdresseInterface $adresse,
        ShippingInterface $shipping,
        PdfGeneratorInterface $pdfgenerator
    )
	{
        $this->product       = $product;
        $this->categorie     = $categorie;
        $this->order         = $order;
        $this->worker        = $worker;
        $this->adresse       = $adresse;
        $this->shipping      = $shipping;
        $this->pdfgenerator  = $pdfgenerator;

        $this->generator = new \App\Droit\Generate\Excel\ExcelGenerator();
        $this->helper    = new \App\Droit\Helper\Helper();

        setlocale(LC_ALL, 'fr_FR.UTF-8');
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function index(Request $request)
	{
        $names    = $request->input('columns',config('columns.names'));

        $period   = $request->all();
        $status   = $request->input('status',null);
        $onlyfree = $request->input('onlyfree',null);
        $details  = $request->input('details',null);
        $columns  = $request->input('columns',$this->generator->columns);
        $export   = $request->input('export',null);

        $period['start'] = (!isset($period['start']) ? \Carbon\Carbon::now()->startOfMonth() : \Carbon\Carbon::parse($period['start']) );
        $period['end']   = (!isset($period['end'])   ? \Carbon\Carbon::now()->endOfMonth()   : \Carbon\Carbon::parse($period['end']) );

        $orders = $this->order->getPeriod($period['start'],$period['end'], $status, $onlyfree);

        if($export)
        {
            $this->generator->setColumns($columns);
            $this->export($orders,$names,$period,$details);
        }

        $cancelled = $this->order->getTrashed($period['start'],$period['end']);

		return view('backend.orders.index')->with(
            ['orders' => $orders,'start' => $period['start'],'end' => $period['end'],'columns' => config('columns.names'),'names' => $names,'onlyfree' => $onlyfree, 'details' => $details, 'cancelled' => $cancelled]
        );
	}

    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function export($orders, $names, $period = null, $details = null)
    {
        \Excel::create('Export Commandes', function($excel) use ($orders,$period,$details,$names)
        {
            $excel->sheet('Export_Commandes', function($sheet) use ($orders,$period,$details,$names)
            {
                $columns = array_keys($names);

                if(!$orders->isEmpty())
                {
                    foreach($orders as $order)
                    {
                        $info['Numero']  = $order->order_no;
                        $info['Montant'] = $order->price_cents.' CHF';
                        $info['Date']    = $order->created_at->formatLocalized('%d %B %Y');
                        $info['Paye']    = $order->payed_at ? $order->payed_at->formatLocalized('%d %B %Y') : '';
                        $info['Status']  = $order->status_code['status'];

                        if($details)
                        {
                            $grouped = $order->products->groupBy('id');

                            foreach($grouped as $product)
                            {
                                $data['title']  = $product->first()->title;
                                $data['qty']    = $product->count();
                                $data['prix']   = $product->first()->price_cents;
                                $data['free']   = $product->first()->pivot->isFree ? 'Oui' : '';
                                $data['rabais'] = $product->first()->pivot->rabais ? ceil($product->first()->pivot->rabais).'%' : '';

                                $converted[] = $info + $data;
                            }
                        }
                        else
                        {
                            if($order->user && !$order->user->adresses->isEmpty())
                            {
                                foreach($columns as $column)
                                {
                                    $data[$column] = $order->user->adresses->first()->$column;
                                }

                                $converted[] = $info + $data;
                            }
                        }

                    }
                }

                // Columns
                $names = ($details ? ['Numero','Montant','Date','Paye','Status','Titre','Quantité','Prix','Gratuit','Rabais'] : (['Numero','Montant','Date','Paye','Status'] + $names));

                // Set header
                $sheet->row(1, ['Commandes du '.$this->helper->formatTwoDates($period['start'],$period['end'])]);
                $sheet->row(1,function($row) {
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });

                // Set Columns
                $sheet->row(2,['']);
                $sheet->row(3, $names);
                $sheet->row(3,function($row) {
                    $row->setFontWeight('bold');
                    $row->setFontSize(12);
                });

                // Set Orders list
                $sheet->rows($converted);
                $sheet->appendRow(['']);
                $sheet->appendRow(['Total', $orders->sum('price_cents').' CHF']);
                $sheet->row($sheet->getHighestRow(), function ($row)
                {
                    $row->setFontWeight('bold');
                    $row->setFontSize(13);
                });

            });
        })->export('xls');
    }

    /**
     *
     * @return Response
     */
    public function show($id)
    {
        $shippings = $this->shipping->getAll();
        $order     = $this->order->find($id);

        return view('backend.orders.show')->with(['order' => $order,'shippings' => $shippings]);
    }

    public function generate(Request $request)
    {
        $order = $this->order->find($request->input('id'));

        $this->pdfgenerator->factureOrder($order->id);

        return redirect()->back()->with(array('status' => 'success', 'message' => 'La facture a été regénéré' ));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = $this->product->getAll();

        return view('backend.orders.create')->with(['products' => $products]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $order    = $request->input('order');
        $products = $this->helper->convertProducts($order);

        $validator = \Validator::make($request->all(), [
            'adresse.first_name'  => 'required_without:user_id',
            'adresse.last_name'   => 'required_without:user_id',
            'adresse.adresse'     => 'required_without:user_id',
            'adresse.npa'         => 'required_without:user_id',
            'adresse.ville'       => 'required_without:user_id',
        ], [
            'adresse.first_name.required_without'  => 'Une adresse (prénom) est requise sans utilisateur',
            'adresse.last_name.required_without'   => 'Une adresse (nom) est requise sans utilisateur',
            'adresse.adresse.required_without'     => 'Une adresse (adresse) est requise sans utilisateur',
            'adresse.npa.required_without'         => 'Une adresse (npa) est requise sans utilisateur',
            'adresse.ville.required_without'       => 'Une adresse (ville) est requise sans utilisateur',
        ]);

        if ($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->with('old_products', $products)->withInput();
        }

        $order = $this->worker->make($request->all());

        return redirect('admin/orders')->with(array('status' => 'success', 'message' => 'La commande a été crée' ));
    }

    /**
     * Update resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $name  = $request->input('name');
        $order = $this->order->update([ 'id' => $request->input('pk'), $name =>  $request->input('value')]);

        if($order)
        {
            return response()->json(['OK' => 200, 'etat' => ($order->status == 'pending' ? 'En attente' : 'Payé'),'color' => ($order->status == 'pending' ? 'warning' : 'success')]);
        }

        return response()->json(['status' => 'error','msg' => 'problème']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = $this->order->find($id);

        $this->worker->resetQty($order,'+');
        $order->delete();

        return redirect('admin/orders')->with(array('status' => 'success' , 'message' => 'La commande a été annulé' ));
    }

    /**
     * Restore the inscription
     *
     * @param  int  $id
     * @return Response
     */
    public function restore($id)
    {
        $this->order->restore($id);

        return redirect()->back()->with(array('status' => 'success', 'message' => 'La commande a été restauré' ));
    }

}
