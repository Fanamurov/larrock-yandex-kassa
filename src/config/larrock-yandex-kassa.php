<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Yandex Money shop parameters
    | Параметры магазина Яндекс Деньги
    |--------------------------------------------------------------------------
    |
    | In this section you should write yandex money requisites,
    | that you can get on Yandex Kassa official website, after
    | registering own shop
    |
    | Параметры, которые нужно заполнить ниже можно получить
    | в личном кабинете Яндекс Кассы, после регистрации
    | магазина
    |
    | @see https://money.yandex.ru/joinups
    |
    */
    'shop_id' => env('YANDEX_KASSA_SHOP_ID', null),
    'sc_id' => env('YANDEX_KASSA_SC_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Ключ к API (secret_key)
    |--------------------------------------------------------------------------
    |
    | @see https://kassa.yandex.ru/docs/guides/#bystryj-start
    |
    */
    'secret_key' => env('YANDEX_KASSA_SECRET_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Payment types
    | Способы оплаты
    |--------------------------------------------------------------------------
    |
    | Payment types that will be given in payment form.
    | All available payment types you can find
    | in Yandex Kassa documentation
    |
    | Способы оплаты, которые будут предложены в форме
    | оплаты. Все доступные способы оплаты можно найти
    | в документации Яндекс Кассы
    |
    | @see https://kassa.yandex.ru/docs/guides/#sposoby-oplaty
    |
    */
    'payment_types' => [
        'bank_card', 'yandex_money', 'qiwi',
        'webmoney', 'alfabank', 'cash'
    ],

    //TODO:реализовать оплату через Сбербанк Онлайн (смс)
    //@see https://kassa.yandex.ru/docs/guides/#sberbank-onlajn-sms

    /*
     * Подключение логики для обеспечения оплаты по 54-ФЗ
     */
    'online_kassa' => env('YANDEX_KASSA_ONLINE_KASSA', FALSE),

    /*
    |--------------------------------------------------------------------------
    | Код системы налогооблажения (для 54ФЗ)
    |--------------------------------------------------------------------------
    |
    | Код системы налогообложения передается в объекте receipt, в параметре tax_system_code.
    | Возможные значения — цифра от 1 до 6.
    |
    | Код	Система налогообложения
    | 1	    Общая система налогообложения
    | 2	    Упрощенная (УСН, доходы)
    | 3	    Упрощенная (УСН, доходы минус расходы)
    | 4	    Единый налог на вмененный доход (ЕНВД)
    | 5	    Единый сельскохозяйственный налог (ЕСН)
    | 6	    Патентная система налогообложения
    |
    | @see https://kassa.yandex.ru/docs/guides/#kody-sistem-nalogooblozheniya
    |
    */
    'tax_system_code' => env('YANDEX_KASSA_TAX_SYSTEM_CODE', 1),

    /*
    |--------------------------------------------------------------------------
    | Routes settings
    | Настройки путей
    |--------------------------------------------------------------------------
    |
    */

    'routing' => [
        'cancelPayment' => '/ykassa/cancelPayment',
        'returnURL' => '/ykassa/returnURL'
    ],

    /* Установка значение задержки между повторными запросами */
    'timeout' => null,

    /* Установка значения количества попыток повторных запросов при статусе 202 */
    'attempts' => 3,

    /* Устанавливает логгер приложения */
    'logger' => null,

    /* Устанавливает конфиг клиента. URL: https://payment.yandex.net/api/v3 обязателен! */
    'config' => [
        'url' => 'https://payment.yandex.net/api/v3'
    ],
];
