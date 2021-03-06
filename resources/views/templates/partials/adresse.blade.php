@if($adresse)
    <ul id="user">

        <?php $name = $adresse->invoice_name; ?>
        @if(!empty($name))
            @foreach($name as $line)
                <li>{{ $line }}</li>
            @endforeach
        @endif

        <li>{{ $adresse->adresse }}</li>
        {!! (!empty($adresse->complement) ? '<li>'.$adresse->complement.'</li>' : '') !!}
        {!! (!empty($adresse->cp) ? '<li>'.$adresse->cp_trim.'</li>' : '') !!}
        <li>{{ $adresse->npa.' '.$adresse->ville }}</li>
    </ul>
@endif