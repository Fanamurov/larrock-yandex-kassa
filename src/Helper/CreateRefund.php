<?php

namespace Larrock\YandexKassa\Helper;

use Larrock\YandexKassa\YandexKassaComponent;

class CreateRefund
{
    public $YKassa;

    public function __construct()
    {
        $this->YKassa = new YandexKassaComponent();
    }

    public function createRefund($payment_id, $sum)
    {
        $answer = $this->YKassa->client->createRefund(
            array(
                'amount' => array(
                    'value' => $sum,
                    'currency' => 'RUB',
                ),
                'payment_id' => $payment_id,
            )
        );

        return $answer;
    }
}