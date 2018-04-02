<?php

namespace Larrock\YandexKassa\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YandexKassaRequest extends FormRequest
{
    /** @return bool */
    public function authorize()
    {
        return true;
    }

    /** @return array */
    public function rules()
    {
        return [
            'sum' => 'required',
            'customerNumber' => 'required',
            'orderNumber' => 'required',
        ];
    }
}
