<?php
namespace Larrock\YandexKassa\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YandexKassaRequest extends FormRequest
{
    /**
     *
     * @var bool
     */
    private $hashIsValid;

    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'scId' => 'required',
            'shopId' => 'required',
            'sum' => 'required',
        ];
    }



    /**
     * Returns true if request hash is valid
     *
     * @return bool
     */
    public function isValidHash()
    {
        if ($this->hashIsValid === null) {
            $parameters = [
                $this->get('action'),
                $this->get('orderSumAmount'),
                $this->get('orderSumCurrencyPaycash'),
                $this->get('orderSumBankPaycash'),
                $this->get('shopId'),
                $this->get('invoiceId'),
                $this->get('customerNumber'),
                config('yandex-kassa.shop_password')
            ];

            $this->hashIsValid = strtolower(md5(implode(';', $parameters))) === strtolower($this->get('md5'));
        }

        return $this->hashIsValid;
    }
}