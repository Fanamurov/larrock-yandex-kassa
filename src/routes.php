<?php

//Создание платежа
Route::post('/ykassa/createPayment', 'Larrock\YandexKassa\YandexKassaContoller@createPayment')->name('YandexKassa.createPayment');

//Проверка статуса платежа
Route::get(config('larrock-yandex.kassa.routing.returnURL', '/ykassa/returnURL') .'/{orderId}/{user}',
    'Larrock\YandexKassa\YandexKassaContoller@returnURL')->name('YandexKassa.returnURL');

//Возврат платежа (админ. метод)
Route::post('/ykassa/createRefund/{paymentId}', 'Larrock\YandexKassa\YandexKassaContoller@createRefund')->name('YandexKassa.createRefund');

//Отмена платежа (админ. метод)
Route::post('/ykassa/сancelPayment/{paymentId}', 'Larrock\YandexKassa\YandexKassaContoller@сancelPayment')->name('YandexKassa.сancelPayment');