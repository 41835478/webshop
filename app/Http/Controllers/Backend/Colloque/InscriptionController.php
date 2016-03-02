<?php

namespace App\Http\Controllers\Backend\Colloque;

use App\Droit\Colloque\Repo\ColloqueInterface;
use App\Droit\Inscription\Repo\InscriptionInterface;
use App\Droit\Inscription\Worker\InscriptionWorkerInterface;
use App\Droit\User\Repo\UserInterface;
use App\Droit\Inscription\Repo\GroupeInterface;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Requests\InscriptionCreateRequest;
use App\Http\Controllers\Controller;

use App\Events\InscriptionWasCreated;
use App\Events\GroupeInscriptionWasRegistered;

use App\Jobs\SendConfirmationInscriptionEmail;

class InscriptionController extends Controller
{
    protected $inscription;
    protected $register;
    protected $colloque;
    protected $user;
    protected $generator;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ColloqueInterface $colloque, InscriptionInterface $inscription, UserInterface $user, InscriptionWorkerInterface $register, GroupeInterface $groupe)
    {
        $this->colloque    = $colloque;
        $this->inscription = $inscription;
        $this->register    = $register;
        $this->user        = $user;
        $this->groupe      = $groupe;

        $this->generator   = \App::make('App\Droit\Generate\Pdf\PdfGeneratorInterface');
        $this->helper      = new \App\Droit\Helper\Helper();

        setlocale(LC_ALL, 'fr_FR.UTF-8');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $inscriptions = $this->inscription->getAll(5);

        return view('backend.inscriptions.index')->with(['inscriptions' => $inscriptions]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function colloque($id)
    {
        $colloque        = $this->colloque->find($id);
        $inscriptions    = $this->inscription->getByColloque($id,false,true);
        $desinscriptions = $this->inscription->getByColloqueTrashed($id);

        return view('backend.inscriptions.colloque')->with(['inscriptions' => $inscriptions, 'colloque' => $colloque, 'desinscriptions' => $desinscriptions, 'names' => config('columns.names')]);
    }

    /**
     * Display creation.
     *
     * @return Response
     */
    public function create($colloque_id = null)
    {
        $colloques = $this->colloque->getAll();

        return view('backend.inscriptions.create')->with(['colloques' => $colloques, 'colloque_id' => $colloque_id]);
    }

    /**
     * Display creation.
     *
     * @return Response
     */
    public function add($group_id)
    {
        $inscription = $this->inscription->getByGroupe($group_id);

        return view('backend.inscriptions.add')->with(['group_id' => $group_id, 'groupe' => $inscription->first()->groupe, 'colloque' => $inscription->first()->colloque]);
    }

    /**
     * Display groupe edit.
     *
     * @return Response
     */
    public function groupe($group_id)
    {
        $groupe = $this->groupe->find($group_id);

        return view('backend.inscriptions.groupe')->with(['groupe' => $groupe]);
    }

    public function push(Request $request)
    {
        // Get all infos for inscription/participant
        $options     = $request->input('options',null);
        $groupes     = $request->input('groupes',null);

        $data = [
            'group_id'    => $request->input('group_id'),
            'colloque_id' => $request->input('colloque_id'),
            'participant' => $request->input('participant'),
            'price_id'    => $request->input('price_id')
        ];

        // choosen options for participants
        if(isset($options))
        {
            $data['options'] = $request->input('options');
        }

        // choosen groupe of options for participants
        if(isset($groupes))
        {
            $data['groupes'] = $request->input('groupes');
        }

        // Register a new inscription
        $this->register->register($data,$request->input('colloque_id'));
        
        $group_user = $this->groupe->find($request->input('group_id'));

        event(new GroupeInscriptionWasRegistered($group_user));

        return redirect()->back()->with(array('status' => 'success', 'message' => 'L\'inscription à bien été crée' ));
    }

    public function change(Request $request)
    {
        $groupe = $this->groupe->update([
            'id'      => $request->input('group_id'),
            'user_id' => $request->input('user_id')
        ]);

        event(new GroupeInscriptionWasRegistered($groupe));

        return redirect('admin/inscription/colloque/'.$groupe->colloque_id)->with(array('status' => 'success', 'message' => 'Le groupe a été modifié' ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(InscriptionCreateRequest $request)
    {
        $type     = $request->input('type');
        $colloque = $request->input('colloque_id');

        // if type simple
        if($type == 'simple')
        {
            $inscription = $this->register->register($request->all(), $colloque, true);

            event(new InscriptionWasCreated($inscription));
        }
        else
        {
            $group_user = $this->groupe->create(['colloque_id' => $colloque , 'user_id' => $request->input('user_id')]);

            // Get all infos for inscriptions/participants
            $participants = $request->input('participant');
            $prices       = $request->input('price_id');
            $options      = $request->input('options');
            $groupes      = $request->input('groupes');

            foreach($participants as $index => $participant)
            {
                $data = [
                    'group_id'    => $group_user->id,
                    'colloque_id' => $colloque,
                    'participant' => $participant,
                    'price_id'    => $prices[$index]
                ];

                // choosen options for participants
                if(isset($options[$index]))
                {
                    $data['options'] = $options[$index];
                }

                // choosen groupe of options for participants
                if(isset($groupes[$index]))
                {
                    $data['groupes'] = $groupes[$index];
                }

                // Register a new inscription
                $this->register->register($data,$colloque);

            }

            event(new GroupeInscriptionWasRegistered($group_user));
        }

        return redirect('admin/inscription/colloque/'.$colloque)->with(array('status' => 'success', 'message' => 'L\'inscription à bien été crée' ));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $inscription = $this->inscription->find($id);

        $inscription->load('colloque','user_options','groupe','participant');

        if(isset($inscription->user_options))
        {
            $inscription->user_options->load('option');
        }

        return view('backend.inscriptions.show')->with(['inscription' => $inscription, 'colloque' => $inscription->colloque, 'user' => $inscription->inscrit]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function generate($id)
    {
        $inscription = $this->inscription->find($id);

        if($inscription->group_id)
        {
            event(new GroupeInscriptionWasRegistered($inscription->groupe));
        }
        else
        {
            event(new InscriptionWasCreated($inscription));
        }

        return redirect()->back()->with(array('status' => 'success', 'message' => 'Les documents ont été mis à jour' ));
    }

    /**
     * Send inscription via admin
     *
     * @return Response
     */
    public function send(Request $request)
    {
        $id    = $request->input('id');
        $email = $request->input('email',null);

        $inscription = $this->inscription->find($id);

        $job = (new SendConfirmationInscriptionEmail($inscription,$email))->delay(5);

        $this->dispatch($job);

        return redirect()->back()->with(array('status' => 'success', 'message' => 'Email envoyé'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $inscription = $this->inscription->update($request->all());

        $annexes     = $inscription->colloque->annexe;

        $this->generator->setInscription($inscription)->generate($annexes);

        return redirect()->back()->with(array('status' => 'success', 'message' => 'L\'inscription a été mise à jour' ));
    }

    public function edit(Request $request)
    {
        $name  = $request->input('name');
        $model = $request->input('model');

        if($model == 'group')
        {
            $group = $this->groupe->find($request->input('pk'));

            if(!$group->inscriptions->isEmpty())
            {
                foreach($group->inscriptions as $inscription)
                {
                    $inscription = $this->inscription->update(['id' => $inscription->id , $name => $request->input('value')]);
                }
            }
        }
        else
        {
            $inscription = $this->inscription->update(['id' => $request->input('pk'), $name => $request->input('value')]);
        }

        return response()->json(['OK' => 200, 'etat' => ($inscription->status == 'pending' ? 'En attente' : 'Payé'),'color' => ($inscription->status == 'pending' ? 'warning' : 'success')]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $inscription = $this->inscription->find($id);

        $this->inscription->delete($id);

        // Refresh the groupe invoice and bv
        if($inscription->group_id > 0)
        {
            event(new GroupeInscriptionWasRegistered($inscription->groupe));
        }

        return redirect('admin/inscription/colloque/'.$inscription->colloque_id)->with(array('status' => 'success', 'message' => 'Désinscription effectué' ));
    }

    /**
     * Restore the inscription
     *
     * @param  int  $id
     * @return Response
     */
    public function restore($id)
    {
        $this->inscription->restore($id);

        $inscription = $this->inscription->find($id);

        if($inscription->group_id)
        {
            event(new GroupeInscriptionWasRegistered($inscription->groupe));
        }
        else
        {
            event(new InscriptionWasCreated($inscription));
        }

        return redirect()->back()->with(array('status' => 'success', 'message' => 'L\'inscription a été restauré' ));
    }

    /**
     * Inscription partial
     * @return Response
     */
    public function inscription(Request $request){

        $colloque = $this->colloque->find($request->input('colloque_id'));
        $user     = $this->user->find($request->input('user_id'));
        $type     = $request->input('type');

        echo view('backend.inscriptions.partials.'.$type)->with(['colloque' => $colloque, 'user_id' => $request->input('user_id'), 'user' => $user, 'type' => $type])->__toString();
    }

}
