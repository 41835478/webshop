<?php namespace App\Http\Controllers\Backend\Abo;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Droit\Abo\Repo\AboInterface;
use App\Droit\Shop\Product\Repo\ProductInterface;

use App\Droit\Abo\Worker\AboFactureWorkerInterface;
use App\Droit\Abo\Worker\AboRappelWorkerInterface;

class AboFileController extends Controller {
    
    protected $abo;
    protected $facture;
    protected $rappel;
    protected $product;

    public function __construct(AboInterface $abo, ProductInterface $product, AboFactureWorkerInterface $facture, AboRappelWorkerInterface $rappel)
    {
        $this->abo        = $abo;
        $this->product    = $product;
        $this->facture    = $facture;
        $this->rappel     = $rappel;

        setlocale(LC_ALL, 'fr_FR.UTF-8');
	}

    /*
     * Generate all invoices and bind the all
    **/
    public function generate(Request $request)
    {
        $abo     = $this->abo->findAboByProduct($request->input('product_id'));
        $product = $this->product->find($request->input('product_id'));

        $worker  = $request->input('worker');

        $this->$worker->generate($product,$abo, $request->input('all',false));

        alert()->success('La création des '.$worker.'s est en cours.<br/>Un email vous sera envoyé dès que la génération sera terminée.');

        return redirect()->back();
    }

    /*
   * Bind all invoices
   * */
    public function bind(Request $request)
    {
        $abo     = $this->abo->findAboByProduct($request->input('product_id'));
        $product = $this->product->find($request->input('product_id'));
        
        $worker  = $request->input('worker');
        
        $this->$worker->bind($product, $abo);

        alert()->success('Les '.$worker.'s sont re-attachés<br/>Rafraichissez la page pour mettre à jour le document.');

        return redirect()->back();
    }
    
}