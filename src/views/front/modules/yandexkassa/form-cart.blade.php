{{--
  -- For more information about form fields
  -- you can visit Yandex Kassa documentation page
  --
  -- @see https://tech.yandex.com/money/doc/payment-solution/payment-form/payment-form-http-docpage/
  --}}
@if($data->status_order === 'Обработано')
    <form action="/ykassa/createPayment" method="POST" class="uk-form" id="y_kassa_{{ $data->order_id }}">
        @if($data->cost_discount > 0 && $data->cost_discount < $data->cost)
            <input name="sum" type="hidden" value="{{ $data->cost_discount + $data->cost_delivery }}">
        @else
            <input name="sum" type="hidden" value="{{ $data->cost + $data->cost_delivery }}">
        @endif
        <input name="customerNumber" value="{{ Auth::id() }}" type="hidden"/>
        <input name="orderNumber" value="{{ $data->order_id }}" type="hidden"/>
        <input name="cps_phone" value="@if( !empty($data->tel)){{ $data->tel }}@else{{ $current_user->tel }}@endif" type="hidden"/>
        <input name="cps_email" value="@if( !empty($data->email)){{ $data->email }}@else{{ $current_user->email }}@endif" type="hidden"/>
        <input name="paymentType" value="" type="hidden">
        <div class="uk-form-row">
            {{ csrf_field() }}
            <button type="submit" class="yamoney-pay-button"><span class="yamoney-pay-button__text">Заплатить</span><br/><span class="yamoney-pay-button__subtext">через Яндекс</span></button>
        </div>
    </form>
    {!! JsValidator::formRequest('Larrock\YandexKassa\Requests\YandexKassaRequest', '#y_kassa_'. $data->order_id)->render() !!}
@else
    <p class="uk-alert uk-display-block">Оплата будет доступна после проверки заказа нашими менеджерами</p>
@endif