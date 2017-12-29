<?php

namespace Larrock\YandexKassa;

use App\Http\Controllers\Controller;
use Larrock\ComponentPages\Facades\LarrockPages;

class YandexKassaContoller extends Controller
{
    public function __construct()
    {
        //YandexKassa::shareConfig();
        $this->middleware(LarrockPages::combineFrontMiddlewares());
    }

    public function index()
    {
        $test = new YandexKassaComponent();
        //$test->client->setAuth(config('larrock-yandex-kassa.shop_id'), config('larrock-yandex-kassa.sc_id'));
        return view('larrock::front.modules.yandexkassa.form');
        dd($test->client);
    }
}