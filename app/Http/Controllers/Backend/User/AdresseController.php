<?php

namespace App\Http\Controllers\Backend\User;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Droit\Adresse\Repo\AdresseInterface;
use App\Droit\User\Repo\UserInterface;
use App\Http\Requests\CreateAdresse;
use App\Http\Requests\UpdateAdresse;
use App\Http\Requests\SearchTermRequest;

class AdresseController extends Controller {

    protected $adresse;
    protected $user;
    protected $format;

    public function __construct(AdresseInterface $adresse, UserInterface $user)
    {
        $this->adresse = $adresse;
        $this->user    = $user;
        $this->format  = new \App\Droit\Helper\Format();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request,$back = null)
    {
        if($back){
            $search = session()->get('adresse_search');
            $term   = isset($search['term']) && !empty($search['term']) ? $search['term'] : null;
        }
        else{
            $term = $request->input('term',null);
            session(['adresse_search' => ['term' => $term]]);
        }

        $adresses = $term ? $this->adresse->search($term) : $this->adresse->getAll();
        
        return view('backend.adresses.index')->with(['adresses' => $adresses, 'term' => $term]);
    }

    /**
     * Create new adresse, if we passe an user_id make the adresse for the user
     *
     * @return Response
     */
    public function create($user_id = null)
    {
        $user = $user_id ? $this->user->find($user_id) : null;

        return view('backend.adresses.create')->with(['user' => $user]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(CreateAdresse $request)
    {
        $adresse = $this->adresse->create($request->all());

        alert()->success('Adresse crée');

        if($request->input('user_id'))
        {
            $user = $this->user->find($request->input('user_id'));
            $user->adresses()->save($adresse);

            return redirect('admin/user/'.$user->id);
        }

        return redirect('admin/adresse/'.$adresse->id);
    }

    public function convert(Request $request)
    {
        $adresse = $this->adresse->find($request->input('id'));

        $data = [
            'first_name' => !empty($adresse->first_name) ? $adresse->first_name : "",
            'last_name'  => !empty($adresse->last_name) ? $adresse->last_name : "",
            'company'    => $adresse->company,
            'email'      => $adresse->email,
            'password'   => $request->input('password',null),
        ];

        $validator = \Validator::make($data, [
            'first_name' => 'required_without:company',
            'last_name'  => 'required_without:company',
            'company'    => 'required_without_all:first_name,last_name',
            'email'      => 'required|email|max:255|unique:users',
            'password'   => 'required|min:5',
        ]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data['password'] = bcrypt($request->input('password',null));

        // Create user account
        $user = $this->user->create($data);

        // update adresse with user_id
        $this->adresse->update(['id' => $adresse->id, 'user_id' => $user->id, 'livraison' => 1]);
        
        // Assign all orders to new user
        $this->adresse->assignOrdersToUser($adresse->id, $user->id);

        alert()->success('Adresse convertie');

        return redirect('admin/user/'.$user->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id,Request $request)
    {
        $adresse = $this->adresse->find($id);

        return view('backend.adresses.show')->with(['adresse' => $adresse , 'term' => session()->get('term')]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id,CreateAdresse $request)
    {
        $adresse = $this->adresse->update($request->all());

        alert()->success('Adresse mise à jour');

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id, Request $request)
    {
        $adresse = $this->adresse->find($id);

        // Validate deletion, if no user or user with no orders or inscriptions delete the adresse
        $validator = new \App\Droit\Adresse\Worker\AdresseValidation($adresse);
        $validator->activate();

        $this->adresse->delete($id);

        $back = $request->input('url',null);
        $back = $back && $back == url('admin/adresses') ? url('admin/adresses/back') : $back;

        alert()->success('Adresse supprimée');

        return redirect($back);
    }
    
    public function livraison(Request $request)
    {
        $this->adresse->changeLivraison($request->input('adresse_id') , $request->input('user_id'));

        alert()->success('Adresse de livraison modifié');

        return redirect()->back();
    }

}
