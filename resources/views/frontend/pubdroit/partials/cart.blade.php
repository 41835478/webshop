@if(!Cart::content()->isEmpty())

    <?php $cart  = Cart::content(); ?>
    <div class="cart-table-holder">
        <table width="100%" border="0" cellpadding="10">
            <tr>
                <th width="47%" align="left" colspan="2">Ouvrage</th>
                <th width="15%" style="text-align: center;">Prix par unité</th>
                <th width="12%" style="text-align: center;">Quantité</th>
                <th width="15%" style="text-align: right;">Sous-total</th>
                <th width="5%" class="mobile-hidden">&nbsp;</th>
            </tr>
            @foreach($cart as $item)
            <tr bgcolor="#FFFFFF" class="product-detail">
                <td valign="top" class="mobile-hidden" align="center">
                    <img style="max-height:80px;" src="{{ asset('files/products/'.$item->options->image ) }}" alt="{{ $item->name }}">
                </td>
                <td valign="middle">{{ $item->name }}</td>
                <td align="center" valign="middle">{{ $item->product->price_cents }} CHF</td>
                <td align="center" valign="middle">
                    <form method="post" action="{{ url('cart/quantityProduct') }}" class="form-inline">
                        <div class="input-group">
                            <input type="text" class="form-control" name="qty" value="{{ $item->qty }}">
                            <span class="input-group-btn">
                               <button class="btn btn-default btn-sm" type="submit">éditer</button>
                            </span>
                        </div><!-- /input-group -->
                        <input type="hidden" name="rowid" value="{{ $item->rowid }}">
                    </form>
                </td>
                <td align="right" valign="middle">{{ number_format((float)($item->price * $item->qty), 2, '.', '') }} CHF</td>
                <td align="center" valign="middle" class="mobile-hidden">
                    <form method="post" action="{{ url('cart/removeProduct') }}" class="form-inline">{!! csrf_field() !!}
                        <input type="hidden" name="rowid" value="{{ $item->rowid }}">
                        <button type="submit"><i class="icon-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
        </table>
    </div>

@endif

@if( !\Cart::instance('abonnement')->content()->isEmpty() )

    <?php $abos = Cart::instance('abonnement')->content(); ?>
    <div class="cart-table-holder">
        <table width="100%" border="0" cellpadding="10">
            <tr>
                <th width="47%" align="left">Nom</th>
                <th width="15%" style="text-align: center;">Prix annuel</th>
                <th width="12%" style="text-align: center;">Quantité</th>
                <th width="15%" style="text-align: right;">Sous-total</th>
                <th width="5%" class="mobile-hidden">&nbsp;</th>
            </tr>
            @foreach($abos as $item)
                <tr bgcolor="#FFFFFF" class="product-detail">
                    <td valign="top">
                        @foreach($item->options as $option)
                            <img style="max-height: 60px;" src="{{ asset('files/main/'.$option) }}" />
                        @endforeach
                        Abonnement au {{ $item->name }}
                    </td>
                    <td class="text-center" valign="middle">{{ $item->price }} CHF</td>
                    <td class="text-center" valign="middle">
                        <form method="post" action="{{ url('cart/quantityAbo') }}" class="form-inline">
                            <div class="input-group">
                                <input type="text" class="form-control" name="qty" value="{{ $item->qty }}">
                                <span class="input-group-btn">
                                   <button class="btn btn-default btn-sm" type="submit">éditer</button>
                                </span>
                            </div><!-- /input-group -->
                            <input type="hidden" name="rowid" value="{{ $item->rowid }}">
                        </form>
                    </td>
                    <td class="text-right" valign="middle">{{ number_format((float)($item->price * $item->qty), 2, '.', '') }} CHF</td>
                    <td class="text-center" valign="middle" class="mobile-hidden">
                        <form method="post" action="{{ url('cart/removeAbo') }}" class="form-inline">{!! csrf_field() !!}
                            <input type="hidden" name="rowid" value="{{ $item->rowid }}">
                            <button type="submit"><i class="icon-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endif