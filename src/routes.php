<?php

//Создание платежа
Route::get('/ykassa/createPayment', 'Larrock\YandexKassa\YandexKassaContoller@createPayment')->name('YandexKassa.createPayment');

//Проверка корректности заказа
Route::get(config('larrock-yandex.kassa.routing.checkURL', env('APP_URL') .'/ykassa/checkURL'),
    'Larrock\YandexKassa\YandexKassaContoller@confirmationOrder')->name('YandexKassa.checkURL');

//Проверка оплаты
Route::get(config('larrock-yandex.kassa.routing.avisoURL', env('APP_URL') .'/ykassa/avisoURL'),
    'Larrock\YandexKassa\YandexKassaContoller@confirmationOrder')->name('YandexKassa.avisoURL');

//Отмена плаежа
Route::get(config('larrock-yandex.kassa.routing.failURL', env('APP_URL') .'/ykassa/avoidOrder'),
    'Larrock\YandexKassa\YandexKassaContoller@cancelPayment')->name('YandexKassa.avoidOrder');