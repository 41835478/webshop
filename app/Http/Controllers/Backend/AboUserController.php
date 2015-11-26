<?php namespace App\Http\Controllers\Backend;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Droit\Adresse\Repo\AdresseInterface;
use App\Droit\Abo\Repo\AboUserInterface;
use App\Droit\Abo\Repo\AboInterface;

class AboUserController extends Controller {

    protected $abonnement;
    protected $adresse;
    protected $abo;

    public function __construct(AboUserInterface $abonnement, AdresseInterface $adresse, AboInterface $abo)
    {
        $this->abonnement = $abonnement;
        $this->adresse    = $adresse;
        $this->abo        = $abo;

        setlocale(LC_ALL, 'fr_FR.UTF-8');
	}

    public function index($id){

        $abo = $this->abo->find($id);

        return view('backend.abos.show')->with(['abo' => $abo]);
    }


    public function show($id){

        $abonnement = $this->abonnement->find($id);

        return view('backend.abos.abonnement')->with(['abonnement' => $abonnement]);
    }

	public function store(Request $request)
	{

	}
		
	public function destroy(Request $request)
	{

	}
}