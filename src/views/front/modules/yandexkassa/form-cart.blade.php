{{--
  -- For more information about form fields
  -- you can visit Yandex Kassa documentation page
  --
  -- @see https://tech.yandex.com/money/doc/payment-solution/payment-form/payment-form-http-docpage/
  --}}
<form action="{{ YandexKassa::getConfig()->yandex_kassa_form_action }}" method="{{ YandexKassa::getConfig()->yandex_kassa_form_method }}" class="uk-form" id="y_kassa_{{ $data->order_id }}">
    <input name="scId" type="hidden" value="{{ YandexKassa::getConfig()->yandex_kassa_sc_id  }}">
    <input name="shopId" type="hidden" value="{{ YandexKassa::getConfig()->yandex_kassa_shop_id }}">
    @if($data->cost_discount > 0 && $data->cost_discount < $data->cost)
        <input name="sum" type="hidden" value="{{ $data->cost_discount }}">
    @else
        <input name="sum" type="hidden" value="{{ $data->cost }}">
    @endif
    <input name="customerNumber" value="{{ $data->user_id }}" type="hidden"/>
    <input name="orderNumber" value="{{ $data->order_id }}" type="hidden"/>
    <input name="cps_phone" value="{{ $current_user->tel }}" type="hidden"/>
    <input name="cps_email" value="{{ $current_user->email }}" type="hidden"/>
    <input name="paymentType" value="" type="hidden">

    <div class="uk-form-row">
        <button type="submit" class="uk-button uk-button-primary uk-button-large">{{trans('larrock::yandex-kassa.button.pay')}}</button>
    </div>
</form>
{!! JsValidator::formRequest('Larrock\YandexKassa\Requests\YandexKassaRequest', '#y_kassa_'. $data->order_id)->render() !!}