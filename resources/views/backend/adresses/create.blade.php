@extends('backend.layouts.master')
@section('content')

<div class="row"><!-- row -->
    <div class="col-md-10"><!-- col -->
        <p><a class="btn btn-default" href="{{ redirect()->getUrlGenerator()->previous() }}"><i class="fa fa-reply"></i> &nbsp;Retour à la liste</a></p>

        <div class="panel panel-midnightblue">
            <div class="panel-body">

                @include('backend.adresses.partials.create')

            </div>
        </div>

    </div>
</div>
@stop