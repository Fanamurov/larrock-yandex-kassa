<?php

namespace Larrock\YandexKassa\Helper;

use Larrock\YandexKassa\YandexKassaComponent;
use Larrock\YandexKassa\Exceptions\YandexKassaEmptyPaymentId;

class GetPaymentInfo
{
    public $YKassa;

    public function __construct()
    {
        $this->YKassa = new YandexKassaComponent();
    }

    public function getPaymentInfo($paymentId)
    {
        if (empty($paymentId)) {
            throw new YandexKassaEmptyPaymentId('paymentID пустой');
        }

        return $this->YKassa->client->getPaymentInfo($paymentId);
    }
}
