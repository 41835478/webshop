@extends('emails.layouts.notification')
@section('content')

    <a style="{{ $fontFamily }} display:block; height: 115px;" href="{{ url('pubdroit') }}" target="_blank">
        <img width="max-width:100%;" src="{{ secure_asset('images/pubdroit/header_email.png') }}" alt="{{ config('app.name') }}">
    </a>
    <table style="{{ $style['email-body_inner'] }}" align="center" width="570" cellpadding="0" cellspacing="0">
        <tr>
            <td style="{{ $fontFamily }} {{ $style['email-body_cell_header'] }}">
                <!-- Greeting -->
                @if($sondage->marketing)
                    <h1 style="{{ $style['header-1'] }}">{{ $sondage->title }}</h1>
                    <div style="text-align: left;">{!! $sondage->description !!}</div>
                @else
                    <h1 style="{{ $style['header-1'] }}">Formulaire d'évaluation</h1>
                    <p style="{{ $style['paragraph'] }}">Votre avis nous intéresse !</p>
                    <h2 style="{{ $style['header-2'] }}">{{ $sondage->colloque->titre }}<br/></h2>
                    <p style="color: #000;margin-top: 5px;margin-bottom: 10px;">{{ $sondage->colloque->event_date }}</p>
                @endif
            </td>
        </tr>
        <tr>
            <td style="{{ $fontFamily }} {{ $style['email-body_cell_content'] }}">
                <!-- Intro -->
                <!-- p style="{{ $style['paragraph'] }}">Votre avis nous intéresse !</p -->

                <table style="{{ $style['body_action'] }}" align="center" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <a href="{{ secure_url('reponse/create/'.$url) }}"
                               style="{{ $fontFamily }} {{ $style['button'] }} {{ $style['button--blue'] }}"
                               class="button" target="_blank">Lien vers le formulaire
                            </a>
                        </td>
                    </tr>
                </table>

                <!-- Salutation -->
                @if(!$sondage->marketing)
                    <p style="{{ $style['paragraph'] }}">Nous vous remercions pour votre participation.</p>
                @endif
                <p style="{{ $style['paragraph'] }}"><strong>Le secrétariat de la Faculté de droit</strong></p>

            </td>
        </tr>
    </table>
@stop
