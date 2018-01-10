<?php

//Создание платежа
Route::post('/ykassa/createPayment', 'Larrock\YandexKassa\YandexKassaContoller@createPayment')->name('YandexKassa.createPayment');

//Проверка статуса платежа
Route::get(config('larrock-yandex.kassa.routing.returnURL', '/ykassa/returnURL') .'/{orderId}/{user}',
    'Larrock\YandexKassa\YandexKassaContoller@returnURL')->name('YandexKassa.returnURL');

//Отмена платежа
Route::get(config('larrock-yandex.kassa.routing.cancelPayment', '/ykassa/cancelPayment') .'{paymentId}',
    'Larrock\YandexKassa\YandexKassaContoller@cancelPayment')->name('YandexKassa.cancelPayment');