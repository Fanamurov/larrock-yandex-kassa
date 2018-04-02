<?php

namespace Larrock\YandexKassa\Helper;

use Larrock\YandexKassa\YandexKassaComponent;

class CapturePayment
{
    public $YKassa;

    public function __construct()
    {
        $this->YKassa = new YandexKassaComponent();
    }

    /**
     * Подтверждение платежа.
     * @param $answer
     * @return \YandexCheckout\Request\Payments\Payment\CancelResponse|\YandexCheckout\Request\Payments\Payment\CreateCaptureResponse
     */
    public function capturePayment($answer)
    {
        if ($this->checkOrderOnSite($answer)) {
            $response = $this->YKassa->client->capturePayment(
                [
                    'amount' => [
                        'value' => $answer->amount->value,
                        'currency' => $answer->amount->currency,
                    ],
                ],
                $answer->id
            );
            \Log::info('NEW SUCCESS PAYMENT YANDEX.KASSA #'.$answer->id.' 
        orderId:'.$answer->metadata->orderNumber.' userID:'.$answer->metadata->customerNumber);
        } else {
            $cancelPayment = new CancelPayment();
            $response = $cancelPayment->cancelPayment($answer);
        }
        $cartAction = new CartAction();
        $cartAction->changePaymentData($answer);
        $cartAction->changeOrderStatus($answer);

        return $response;
    }

    /**
     * Проверка заказа на сайте.
     * @param $answer
     * @return bool|null
     */
    protected function checkOrderOnSite($answer)
    {
        $get_order = \LarrockCart::getModel()->whereOrderId($answer->metadata->orderNumber)
            ->whereUser($answer->metadata->customerNumber)->first();
        if ($get_order &&
            (float) $answer->amount->value === (float) $get_order->cost &&
            $answer->amount->currency === 'RUB' &&
            $get_order->status_pay === 'Не оплачено' &&
            $get_order->status_order === 'Обработано') {
            return true;
        }
        \Log::info('checkOrderOnSite #'.$answer->metadata->orderNumber.' PAYMENT YANDEX.KASSA SUCCESS');

        return null;
    }
}
