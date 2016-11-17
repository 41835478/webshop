@extends('backend.layouts.master')
@section('content')

<div class="row">
    <div class="col-md-12">
        <h3>Réponses au sondage</h3>
    </div>
</div>

<div class="row">
    <div class="col-md-10 col-xs-12">

        @if($sondage)

            <div class="panel panel-primary">
                <div class="panel-body">

                    <div class="reponses-wrapper">

                        <form action="{{ url('admin/reponse/'.$sondage->id) }}" method="POST" class="validate-form" data-validate="parsley">
                            {!! csrf_field() !!}
                            <label for="message" class="control-label">Trier par</label>
                            <div class="input-group">
                                <select name="sort" class="form-control">
                                    <option selected value="reponse_id">Personne</option>
                                    <option value="avis_id">Question</option>
                                </select>
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="submit">Envoyer</button>
                                </span>
                            </div>
                        </form>

                       @if(!$reponses->isEmpty())
                           @foreach($reponses as $id => $response)
                                <div class="sondage-reponse-wrapper">
                                    @if($sort == 'avis_id')
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="question-title">{!! $response->first()->avis->question !!}</div>
                                                @foreach($response as $avis)
                                                    <div class="question-reponse">{!! $avis->reponse !!}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h4>{{ $response->first()->response->user->name }}</h4>
                                                <p>{{ $response->first()->response->email }}</p>
                                            </div>
                                            <div class="col-md-8">
                                                @foreach($response as $avis)
                                                    <div class="question-title">{!! $avis->avis->question !!}</div>
                                                    <div class="question-reponse">{!! $avis->reponse !!}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                       @endif
                   </div>

                </div>
            </div>
        @endif

    </div>
</div>

@stop