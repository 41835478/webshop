@extends('frontend.matrimonial.layouts.master')
@section('content')

<div id="content" class="inner inner-app">

	<div class="row">
		<div class="col-md-12">

			<h3 class="line up">Désinscription</h3>
			<h3>Entrez votre adresse email pour vous <strong>désinscrire</strong></h3>
			<div class="row">
				<div class="col-md-5 col-xs-12">
					@if(!$newsletters->isEmpty())
						@foreach($newsletters as $newsletter)
							<h4>{{ $newsletter->titre }}</h4>
							@include('frontend.newsletter.partials.unsubscribe', ['newsletter' => $newsletter, 'return_path' => 'matrimonial'])
						@endforeach
					@endif
				</div>
			</div>

		</div>
	</div>

</div>

@stop