<?php

namespace Larrock\YandexKassa\Helper;

use Illuminate\Http\Request;
use Larrock\ComponentCart\Facades\LarrockCart;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoCustomerNumber;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoMetadata;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoOrderItems;
use Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoOrderNumber;
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
     * @var array
     */
    public $payment;

    /**
     * Чтобы принять оплату, необходимо создать объект платежа — Payment. Он содержит всю необходимую информацию для
     * проведения оплаты (сумму, валюту и статус). У платежа линейный жизненный цикл, он последовательно переходит из статуса в статус.
     *
     * @param Request $request
     * @return array
     * @throws YandexKassaCreatePaymentNoCustomerNumber
     * @throws YandexKassaCreatePaymentNoMetadata
     * @throws YandexKassaCreatePaymentNoOrderItems
     * @throws YandexKassaCreatePaymentNoOrderNumber
     * @throws YandexKassaCreatePaymentNoRecept
     * @throws YandexKassaCreatePaymentNoReturnUrl
     * @throws YandexKassaCreatePaymentNoSum
     */
    public function create(Request $request): array
    {
        $this->addAmountPayment($request)->addConfirmationPayment($request)->addReceptPayment($request)->addMetadata($request);
        return $this->payment;
    }

    /**
     * Сумма платежа. Для некоторых способов оплаты с покупателя может взиматься дополнительная комиссия.
     *
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
     *
     * @param Request $request
     * @return $this
     * @throws YandexKassaCreatePaymentNoCustomerNumber
     * @throws YandexKassaCreatePaymentNoOrderNumber
     * @throws YandexKassaCreatePaymentNoReturnUrl
     */
    protected function addConfirmationPayment(Request $request)
    {
        if(config('larrock-yandex-kassa.routing.returnURL')){
            if( !$request->has('orderNumber')){
                throw new YandexKassaCreatePaymentNoOrderNumber('orderNumber не передан');
            }
            if( !$request->has('customerNumber')){
                throw new YandexKassaCreatePaymentNoCustomerNumber('customerNumber не передан');
            }
            $this->payment['confirmation'] = [
                'type' => 'redirect',
                'enforce' => true,
                'return_url' => env('APP_URL') . config('larrock-yandex-kassa.routing.returnURL')
                    .'/'. $request->get('orderNumber') .'/'. $request->get('customerNumber'),
            ];
            return $this;
        }
        throw new YandexKassaCreatePaymentNoReturnUrl('Не указан returnURL');
    }

    /**
     * Данные для формирования чека в онлайн-кассе (для соблюдения 54-ФЗ).
     * @param Request $request
     * @return $this
     * @throws \Larrock\YandexKassa\Exceptions\YandexKassaCreatePaymentNoRecept
     * @throws YandexKassaCreatePaymentNoOrderItems
     */
    protected function addReceptPayment(Request $request)
    {
        if(config('larrock-yandex-kassa.online_kassa') === TRUE){
            if($request->has('cps_phone') || $request->has('cps_email')){
                $get_order = LarrockCart::getModel()->whereOrderId($request->get('orderNumber'))
                    ->whereUser($request->get('customerNumber'))->first();
                $items = [];
                foreach ($get_order->items as $item){
                    $items[] = [
                        'description' => $item->name,
                        'quantity' => $item->qty,
                        'amount' => [
                            'value' => $item->price,
                            'currency' => 'RUB'
                        ],
                        'vat_code' => config('larrock-yandex-kassa.tax_system_code')
                    ];
                }
                if(\count($items) === 0){
                    throw new YandexKassaCreatePaymentNoOrderItems('Не удалось узнать список товаров в заказе');
                }
                $this->payment['recept'] = [
                    'items' => $items,
                    'tax_system_code' => config('larrock-yandex-kassa.tax_system_code')
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