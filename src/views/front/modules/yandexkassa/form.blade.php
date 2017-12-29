{{--
  -- For more information about form fields
  -- you can visit Yandex Kassa documentation page
  --
  -- @see https://tech.yandex.com/money/doc/payment-solution/payment-form/payment-form-http-docpage/
  --}}
<form action="{{ $yandex_kassa_form_action }}" method="{{ $yandex_kassa_form_method }}" class="uk-form" id="y_kassa">
    <input name="scId" type="hidden" value="{{ $yandex_kassa_sc_id  }}">
    <input name="shopId" type="hidden" value="{{ $yandex_kassa_shop_id }}">
    <div class="uk-form-row">
        <label for="yandex_money_sum" class="uk-form-label">{{trans('larrock::yandex-kassa.label.sum')}}</label>
        <input name="sum" id="yandex_money_sum" type="number">
    </div>
    <div class="uk-form-row">
        <label for="yandex_money_customer_number" class="uk-form-label">{{trans('larrock::yandex-kassa.label.customer_number')}}</label>
        <input name="customerNumber" id="yandex_money_customer_number" type="text">
    </div>
    <div class="uk-form-row">
        <label class="uk-form-label">{{trans('larrock::yandex-kassa.label.payment_type')}}</label>
        @foreach($yandex_kassa_payment_types as $paymentTypeCode)
            <label>
                <input type="radio" name="paymentType" value="{{$paymentTypeCode}}">
                {{trans('larrock::yandex-kassa.payment_types.' . $paymentTypeCode)}}
            </label>
        @endforeach
    </div>
    <div class="uk-form-row">
        <button type="submit" class="uk-button uk-button-primary">{{trans('larrock::yandex-kassa.button.pay')}}</button>
    </div>
</form>
{!! JsValidator::formRequest('Larrock\YandexKassa\Requests\YandexKassaRequest', '#y_kassa')->render() !!}