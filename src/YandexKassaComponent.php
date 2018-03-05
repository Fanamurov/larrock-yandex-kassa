<?php

namespace Larrock\YandexKassa;

use Larrock\Core\Component;
use YandexCheckout\Client;

class YandexKassaComponent extends Component
{
    public $yandex_kassa_shop_id;

    public $yandex_kassa_return_url;

    /**
     * Yandex.Kassa SDK
     * @var Client
     */
    public $client;

    public function __construct()
    {
        $this->name = 'ykassa';
        $this->title = 'YandexKassa';
        $this->description = 'Мост к YandexKassa SDK';
        $this->initYConfig();
    }

    protected function initYConfig()
    {
        $this->client = new Client();
        $this->client->setAuth(config('larrock-yandex-kassa.shop_id'), config('larrock-yandex-kassa.secret_key'));
        $this->client->setRetryTimeout(config('larrock-yandex-kassa.timeout'));
        $this->client->setMaxRequestAttempts(config('larrock-yandex-kassa.attempts', 3));
        $this->client->setLogger(config('larrock-yandex-kassa.logger'));
        $this->client->setConfig(config('larrock-yandex-kassa.config', ['url' => 'https://payment.yandex.net/api/v3']));

        $this->yandex_kassa_shop_id = config('larrock-yandex-kassa.shop_id');
        $this->yandex_kassa_return_url = config('larrock-yandex-kassa.routing.return_url');

        return $this;
    }
}