@extends('backend.layouts.master')
@section('content')

<div class="row"><!-- row -->
    <div class="col-md-4"><!-- col -->
        <p><a class="btn btn-default" href="{{ url('admin/sondage') }}"><i class="fa fa-reply"></i> &nbsp;Retour à la liste</a></p>
    </div>
    <div class="col-md-4 text-right"><!-- col -->
        <p><a class="btn btn-info" href="{{ url('admin/reponse/'.$sondage->id) }}"><i class="fa fa-bullhorn"></i> &nbsp;Voir les réponses</a></p>
    </div>
</div>
<!-- start row -->

<div class="row">
    <div class="col-md-8">

        <!-- form start -->
        <form action="{{ url('admin/sondage/'.$sondage->id) }}" method="POST" class="validate-form form-horizontal" data-validate="parsley">
            <input type="hidden" name="_method" value="PUT">
            {!! csrf_field() !!}

            <div class="panel panel-midnightblue">
                <div class="panel-body">

                    <h3>Sondage</h3>

                    <div class="form-group">
                        <label for="message" class="col-sm-3 control-label">Colloque</label>
                        <div class="col-sm-6">
                            <select autocomplete="off" name="colloque_id" required class="form-control">
                                <option value="">Choisir le colloque</option>
                                @if(!$colloques->isEmpty())
                                    @foreach($colloques as $colloque)
                                        <option {{ (old('colloque_id') == $colloque->id ) || ($sondage->colloque_id == $colloque->id) ? 'selected' : '' }} value="{{ $colloque->id }}">{{ $colloque->titre }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message" class="col-sm-3 control-label">Valide jusqu'au</label>
                        <div class="col-sm-3">
                            <input type="text" name="valid_at" value="{{ $sondage->valid_at->format('Y-m-d') }}" class="form-control datePicker required">
                        </div>
                    </div>

                </div>
                <div class="panel-footer mini-footer ">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-9">
                        <button class="btn btn-primary" type="submit">Envoyer</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="panel panel-midnightblue">
            <div class="panel-body">
                <h3>Questions  <a href="{{ url('admin/avis/create') }}" class="btn btn-sm btn-success pull-right"><i class="fa fa-plus"></i> &nbsp;Nouvelle question</a></h3>

                <h4>Ajouter une question</h4>

                <form action="{{ url('admin/sondagequestion') }}" method="POST">{!! csrf_field() !!}
                    <input type="hidden" name="id" value="{{ $sondage->id }}" />
                    <div class="input-group">

                        @if(!$avis->isEmpty())
                            <select class="form-control" name="question_id">
                                <option value="">Choix</option>
                                @foreach($avis as $question)
                                    <option value="{{ $question->id }}">{{ $question->question }}</option>
                                @endforeach
                            </select>
                        @endif

                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="submit">Ajouter</button>
                        </span>
                    </div><!-- /input-group -->
                </form>

                <hr/>
                <h4>Liste des questions</h4>
                @if(!$sondage->avis->isEmpty())
                    <ol class="sortquestion" data-id="{{ $sondage->id }}">
                        @foreach($sondage->avis as $avis)
                            <li class="form-group type-choix question-item" id="question_rang_{{ $avis->id }}">
                                <strong>{{ $avis->question }}</strong>
                                <form action="{{ url('admin/sondagequestion/'.$avis->sondage_id) }}" method="POST" class="pull-right">
                                    <input type="hidden" name="_method" value="DELETE">{!! csrf_field() !!}
                                    <input type="hidden" name="question_id" value="{{ $avis->id }}" />
                                    <button class="btn btn-danger btn-xs">Retirer</button>
                                </form>
                            </li>
                        @endforeach
                    </ol>
                @endif

            </div>
        </div>

    </div>
</div>

@stop