<?php

namespace Larrock\YandexKassa;

use Larrock\Core\Component;
use Larrock\Core\Helpers\FormBuilder\FormInput;
use Larrock\Core\Helpers\FormBuilder\FormTextarea;
use Larrock\YandexKassa\Exceptions\YandexKassaNoPaymentTypesProvidedException;
use YandexCheckout\Client;

class YandexKassaComponent extends Component
{
    private $formAction = 'https://money.yandex.ru/eshop.xml';

    private $testFormAction = 'https://demomoney.yandex.ru/eshop.xml';

    /**
     * Collection with available payment types
     *
     * @var \Illuminate\Support\Collection
     */
    public $paymentTypes;

    /**
     * Payment form submit url
     *
     * @var string
     */
    public $yandex_kassa_form_action;

    /**
     * Payment form submit method
     *
     * @var string
     */
    public $yandex_kassa_form_method;

    public $yandex_kassa_sc_id;

    public $yandex_kassa_shop_id;

    public $yandex_kassa_return_url;

    /**
     * Yandex.Kassa SDK
     *
     * @var Client
     */
    public $client;

    public function __construct()
    {
        $this->name = $this->table = 'ykassa';
        $this->title = 'YandexKassa';
        $this->description = 'Мост к YandexKassa SDK';
        $this->model = \config('larrock.models.yandexkassa', YandexKassaComponent::class);
        $this->addRows()->addPositionAndActive()->isSearchable()->initYConfig()->getPaymentTypes();
    }

    protected function initYConfig()
    {
        $this->client = new Client();
        $this->client->setAuth(config('larrock-yandex-kassa.shop_id'), config('larrock-yandex-kassa.sc_id'));
        $this->client->setRetryTimeout(config('larrock-yandex-kassa.timeout'));
        $this->client->setMaxRequestAttempts(config('larrock-yandex-kassa.attempts', 3));
        $this->client->setLogger(config('larrock-yandex-kassa.logger'));
        $this->client->setConfig(config('larrock-yandex-kassa.config', ['url' => 'https://payment.yandex.net/api/v3']));

        $this->yandex_kassa_form_action = config('larrock-yandex-kassa.test_mode', true) ? $this->testFormAction : $this->formAction;
        $this->yandex_kassa_form_method = config('larrock-yandex-kassa.test_mode', true) ? 'POST' : 'GET';
        $this->yandex_kassa_sc_id = config('larrock-yandex-kassa.sc_id');
        $this->yandex_kassa_shop_id = config('larrock-yandex-kassa.shop_id');
        $this->yandex_kassa_return_url = config('larrock-yandex-kassa.routing.return_url');

        return $this;
    }

    protected function addRows()
    {
        $row = new FormInput('title', 'Название блока');
        $this->rows['title'] = $row->setValid('max:255|required')->setTypo()->setFillable();

        $row = new FormTextarea('description', 'Текст блока');
        $this->rows['description'] = $row->setTypo()->setFillable();

        return $this;
    }

    public function getPaymentTypes()
    {
        $this->paymentTypes = collect(config('larrock-yandex-kassa.payment_types', []));

        if ($this->paymentTypes->isEmpty()) {
            throw new YandexKassaNoPaymentTypesProvidedException('Не указаны возможные способы оплаты');
        }

        return $this;
    }
}