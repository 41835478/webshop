<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Droit\Sondage\Repo\ReponseInterface;
use App\Droit\Sondage\Repo\SondageInterface;

use App\Droit\Sondage\Worker\ReponseWorker;

class ReponseController extends Controller
{
    protected $reponse;
    protected $sondage;
    protected $worker;

    public function __construct(ReponseInterface $reponse, SondageInterface $sondage, ReponseWorker $worker)
    {
        $this->reponse = $reponse;
        $this->sondage = $sondage;
        $this->worker  = $worker;
    }

    public function index()
    {
        return view('sondages.index');
    }

    public function create($token)
    {
        $data    = (array) json_decode(base64_decode($token));
        $sondage = $this->sondage->find($data['sondage_id']);
        $answer  = $this->reponse->hasAnswer($data['email'], $data['sondage_id']);
        $isTest  = isset($data['isTest']) ? true : false;

        if($answer)
        {
            alert()->warning('Vous avez déjà répondu au sondage!');

            return redirect('reponse');
        }

        return view('sondages.create')->with(['sondage' => $sondage, 'email' => $data['email'], 'isTest' => $isTest]);
    }

    public function store(Request $request)
    {
        $reponse = $this->worker->make($request->except('reponses'), $request->only('reponses'));

        alert()->success('Merci pour votre participation au sondage!');

        return redirect('reponse');
    }
}
