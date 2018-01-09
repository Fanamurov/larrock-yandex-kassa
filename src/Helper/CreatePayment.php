<?php

namespace Larrock\YandexKassa\Helper;

use Illuminate\Http\Request;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoMetadata;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoRecept;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoReturnUrl;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoSum;

/**
 * @see https://kassa.yandex.ru/docs/checkout-api/?php#platezhi
 * Class Payment
 * @package Larrock\YandexKassa\Helper
 */
class CreatePayment
{
    /**
     * Чтобы принять оплату, необходимо создать объект платежа — Payment. Он содержит всю необходимую информацию для
     * проведения оплаты (сумму, валюту и статус). У платежа линейный жизненный цикл, он последовательно переходит из статуса в статус.
     * @var array
     */
    public $payment;

    /**
     * @param Request $request
     * @return array
     * @throws YandexKassaCreatePaymentNoMetadata
     * @throws YandexKassaCreatePaymentNoRecept
     * @throws YandexKassaCreatePaymentNoSum
     */
    public function create(Request $request)
    {
        $this->addAmountPayment($request)->addConfirmationPayment()->addReceptPayment($request)->addMetadata($request);
        return $this->payment;
    }

    /**
     * Сумма платежа. Для некоторых способов оплаты с покупателя может взиматься дополнительная комиссия.
     * @param Request $request
     * @return $this
     * @throws \Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoSum
     */
    protected function addAmountPayment(Request $request)
    {
        if($request->has('sum')){
            $this->payment['amount'] = [
                'value' => $request->get('sum'),
                'currency' => 'RUB'
            ];
            return $this;
        }
        throw new YandexKassaCreatePaymentNoSum('Сумма платежа не указана');
    }

    /**
     * URL страницы на вашей стороне, на которую пользователь вернется после оплаты
     * @return $this
     * @throws YandexKassaCreatePaymentNoReturnUrl
     */
    protected function addConfirmationPayment()
    {
        if(config('larrock-yandex-kassa.return_url')){
            $this->payment['confirmation'] = [
                'type' => 'redirect',
                'enforce' => true,
                'return_url' => config('larrock-yandex-kassa.return_url'),
            ];
            return $this;
        }
        throw new YandexKassaCreatePaymentNoReturnUrl('Не указан return_url');
    }

    /**
     * Данные для формирования чека в онлайн-кассе (для соблюдения 54-ФЗ).
     * @param Request $request
     * @return $this
     * @throws \Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoRecept
     */
    protected function addReceptPayment(Request $request)
    {
        if($request->has('cps_phone') || $request->has('cps_email')){
            $this->payment['recept'] = [
                'items' => [],
                'tax_system_code' => 1
            ];
        }else{
            throw new YandexKassaCreatePaymentNoRecept('Данные для формирования чека не переданы (телефон или email)');
        }
        if($request->has('cps_email')){
            $this->payment['recept'] = [
                'email' => $request->get('cps_email')
            ];
        }else{
            if($request->has('cps_phone')){
                $this->payment['recept'] = [
                    'phone' => $request->get('cps_phone')
                ];
            }
        }
        return $this;
    }

    /**
     * Идентификатор сохраненного способа оплаты.
     * @param Request $request
     * @return $this
     */
    protected function addPaymentMethodId(Request $request)
    {
        if($request->has('paymentType')){
            $this->payment['payment_method_id'] = $request->get('paymentType');
        }
        return $this;
    }

    /**
     * Дополнительные данные, которые можно передать вместе с запросом (и получить в ответе от Яндекс.Кассы для
     * реализации внутренней логики). Передаются в виде набора пар «ключ-значение». Имена ключей уникальны.
     * @param Request $request
     * @return $this
     * @throws \Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoMetadata
     */
    protected function addMetadata(Request $request)
    {
        if( !$request->has('customerNumber') && !$request->has('orderNumber')){
            throw new YandexKassaCreatePaymentNoMetadata('Metadata платежа не передана');
        }
        if($request->has('customerNumber')){
            $this->payment['metadata']['customerNumber'] = $request->get('customerNumber');
        }
        if($request->has('orderNumber')){
            $this->payment['metadata']['orderNumber'] = $request->get('orderNumber');
        }
        return $this;
    }
}