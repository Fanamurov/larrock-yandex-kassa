<?php

namespace Larrock\YandexKassa\Helper;

use Session;
use Larrock\YandexKassa\YandexKassaComponent;

class CancelPayment
{
    public $YKassa;

    public function __construct()
    {
        $this->YKassa = new YandexKassaComponent();
    }

    /**
     * Отмена платежа.
     * @param $answer
     * @return \YandexCheckout\Request\Payments\Payment\CancelResponse
     */
    public function cancelPayment($answer)
    {
        $response = $this->YKassa->client->cancelPayment(
            $answer->id
        );
        \Log::info('CANCEL PAYMENT YANDEX.KASSA #'.$answer->id.' 
        orderId:'.$answer->metadata->orderNumber.' userID:'.$answer->metadata->customerNumber);

        return $response;
    }

    /**
     * Отмена платежа по ID.
     * @param $paymentId
     * @return \YandexCheckout\Request\Payments\Payment\CancelResponse
     */
    public function cancelPaymentById($paymentId)
    {
        $payment = $this->YKassa->client->cancelPayment(
            $paymentId
        );

        $cartAction = new CartAction();
        $cartAction->changePaymentData($payment);
        $cartAction->changeOrderStatus($payment);
        Session::push('message.danger', 'Платеж отменен');

        return $payment;
    }
}
