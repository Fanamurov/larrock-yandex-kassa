<?php

namespace Larrock\YandexKassa\Facades;

use Illuminate\Support\Facades\Facade;

class YandexKassa extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'yandexkassa';
    }
}
