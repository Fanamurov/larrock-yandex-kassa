<?php

namespace Larrock\YandexKassa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Larrock\ComponentPages\Facades\LarrockPages;
use Larrock\YandexKassa\Helper\CreatePayment;
use Larrock\YandexKassa\Helper\Payment;
use Mail;
use Session;

class YandexKassaContoller extends Controller
{
    public $YKassa;

    public function __construct()
    {
        //YandexKassa::shareConfig();
        $this->YKassa = new YandexKassaComponent();
        $this->middleware(LarrockPages::combineFrontMiddlewares());
    }

    /**
     * Шаг 1. Создайте платеж с выбором способа оплаты на стороне Яндекс.Кассы
     * Шаг 2. Перенаправьте пользователя на страницу в Яндекс.Кассе
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws Exceptions\YandexKassaCreatePaymentNoMetadata
     * @throws Exceptions\YandexKassaCreatePaymentNoRecept
     * @throws Exceptions\YandexKassaCreatePaymentNoSum
     */
    public function createPayment(Request $request)
    {
        $request->merge([
            'sum' => '1837.00',
            'cps_phone' => '89142165178',
            'cps_email' => 'fanamurov@ya.ru',
            'customerNumber' => 1,
            'orderNumber' => 1
        ]);
        $paymentHelper = new CreatePayment();
        $payment = $paymentHelper->create($request);

        /*$YKassa = new YandexKassaComponent();
        $pay = $YKassa->client->createPayment($payment, uniqid('', true));
        return redirect()->to($pay['confirmation']['confirmation_url']);*/

        return $this->test_createPayment();
    }

    public function test_createPayment()
    {
        $test['id'] = '21740069-000f-50be-b000-0486ffbf45b0';
        $test['status'] = 'pending';
        $test['paid'] = false;
        $test['amount']['value'] = '1837.00';
        $test['amount']['currency'] = 'RUB';
        $test['confirmation']['type'] = 'redirect';
        $test['confirmation']['confirmation_url'] = 'https://money.yandex.ru/api-pages/v2/payment-confirm/epl?orderId=21740069-000f-50be-b000-0486ffbf45b0';
        $test['created_at'] = '2018-01-08T10:53:29.072Z';
        $test['metadata']['customerNumber'] = 1;
        $test['metadata']['orderNumber'] = 2;
        //return $test;

        return redirect()->to($test['confirmation']['confirmation_url']);
    }

    /**
     * Шаг 3. Дождитесь уведомления о платеже
     */
    public function confirmationOrder()
    {
        $answer = $this->test_confirmationOrder();
        $answer = json_decode($answer);

        /* Статус waiting_for_capture означает, что платеж прошел успешно.
        Теперь вам нужно подтвердить готовность принять платеж. */
        if($answer->event === 'payment.waiting_for_capture'){
            if($this->checkOrderOnSite($answer)){
                $this->test_capturePayment($answer);
            }else{
                $this->test_cancelPayment($answer);
            }
        }else{
            return redirect()->to(config('larrock-yandex.kassa.routing.failURL', '/ykassa/avoidOrder'));
        }
        dd($answer);
    }

    public function test_confirmationOrder()
    {
        $test['type'] = 'notification';
        $test['event'] = 'payment.waiting_for_capture';
        $test['id'] = '21740069-000f-50be-b000-0486ffbf45b0';
        $test['status'] = 'waiting_for_capture';
        $test['paid'] = true;
        $test['amount']['value'] = '1837.00';
        $test['amount']['currency'] = 'RUB';
        $test['created_at'] = '2018-01-08T10:53:29.072Z';
        $test['metadata']['customerNumber'] = 1;
        $test['metadata']['orderNumber'] = 2;
        $test['payment_method']['type'] = 'yandex_money';
        $test['payment_method']['id'] = '731714f2-c6eb-4ae0-aeb6-8162e89c1065';
        $test['payment_method']['saved'] = false;
        $test['payment_method']['account_number'] = '410011066000000';
        $test['payment_method']['title'] = 'Yandex.Money wallet 410011066000000';

        $test = json_encode($test);

        return $test;
    }

    /**
     * Проверка заказа на сайте
     * @param $answer
     * @return bool|null
     */
    protected function checkOrderOnSite($answer)
    {
        $get_order = \LarrockCart::getModel()->whereOrderId($answer->metadata->orderNumber)
            ->whereUser($answer->metadata->customerNumber)->first();
        if($get_order &&
            (float) $answer->amount->value === (float) $get_order->cost &&
            $answer->amount->currency === 'RUB' &&
            $get_order->status_pay === 'Не оплачено' &&
            $get_order->status_order === 'Обработано'){
                return TRUE;
        }
        return NULL;
    }

    public function getPaymentInfo($paymentId)
    {
        $payment = $this->YKassa->client->getPaymentInfo($paymentId);
    }

    /**
     * Подтверждение платежа
     * @param $answer
     */
    public function capturePayment($answer)
    {
        $idempotenceKey = uniqid('', true);
        $response = $this->YKassa->client->capturePayment(
            array(
                'amount' => array(
                    'value' => $answer->amount->value,
                    'currency' => $answer->amount->currency,
                ),
                'recept' => array(

                )
            ),
            $answer->id,
            $idempotenceKey
        );
        $this->changeCartItem($answer);
        \Log::info('NEW SUCCESS PAYMENT YANDEX.KASSA #'. $answer->id .' 
        orderId:'. $answer->metadata->orderNumber .' userID:'. $answer->metadata->customerNumber);
        dd($response);
    }

    public function test_capturePayment($answer)
    {
        $test['id'] = '21740069-000f-50be-b000-0486ffbf45b0';
        $test['status'] = 'succeeded';
        $test['paid'] = true;
        $test['amount']['value'] = '1837.00';
        $test['amount']['currency'] = 'RUB';
        $test['created_at'] = '2018-01-08T10:53:29.072Z';
        $test['metadata']['customerNumber'] = 1;
        $test['metadata']['orderNumber'] = 2;
        $test['payment_method']['type'] = 'yandex_money';
        $test['payment_method']['id'] = '731714f2-c6eb-4ae0-aeb6-8162e89c1065';
        $test['payment_method']['saved'] = false;
        $test['payment_method']['account_number'] = '410011066000000';
        $test['payment_method']['title'] = 'Yandex.Money wallet 410011066000000';

        $test = json_encode($test);

        //$this->changeCartItem($answer);

        dd($test);

        return $test;
    }

    /**
     * Смена статуса оплаты заказа в БД
     * @param $answer
     */
    protected function changeCartItem($answer)
    {
        $get_order = \LarrockCart::getModel()->whereOrderId($answer->metadata->orderNumber)
            ->whereUser($answer->metadata->customerNumber)->first();
        if($get_order){
            $get_order->status_pay = 'Оплачено';
            $get_order->pay_at = $answer->created_at;
            $get_order->payment_data = json_encode($answer);
            if($get_order->save()){
                Session::push('message.success', 'Заказ #'. $get_order->order_id .' успешно оплачен');
                $this->mailFullOrderChange($get_order);
            }else{
                Session::push('message.danger', 'Заказ #'. $get_order->order_id .' успешно оплачен, но произошла ошибка смены статуса заказа');
                Session::push('message.danger', 'Администраторы сайта в кратчайшие сроки проверят данные и сменят статус оплаты');
            }
        }else{
            Session::push('message.danger', 'Заказ #'. $answer->metadata->orderNumber .' не существует в нашем магазине');
        }
    }

    /**
     * Отмена платежа
     * @param $answer
     */
    public function cancelPayment($answer)
    {
        $idempotenceKey = uniqid('', true);

        $response = $this->YKassa->client->cancelPayment(
            $answer->id,
            $idempotenceKey
        );
        \Log::info('NEW CANCEL PAYMENT YANDEX.KASSA #'. $answer->id .' 
        orderId:'. $answer->metadata->orderNumber .' userID:'. $answer->metadata->customerNumber);
        dd($response);
    }

    public function test_cancelPayment($answer)
    {
        $test['id'] = '21740069-000f-50be-b000-0486ffbf45b0';
        $test['status'] = 'canceled';
        $test['paid'] = true;
        $test['amount']['value'] = '1837.00';
        $test['amount']['currency'] = 'RUB';
        $test['created_at'] = '2018-01-08T10:53:29.072Z';
        $test['metadata']['customerNumber'] = 1;
        $test['metadata']['orderNumber'] = 2;
        $test['payment_method']['type'] = 'yandex_money';
        $test['payment_method']['id'] = '731714f2-c6eb-4ae0-aeb6-8162e89c1065';
        $test['payment_method']['saved'] = false;
        $test['payment_method']['account_number'] = '410011066000000';
        $test['payment_method']['title'] = 'Yandex.Money wallet 410011066000000';

        $test = json_encode($test);

        return $test;
    }

    /**
     * Отправка email'а об изменении заказа
     *
     * @param         $order
     * @param null    $subject
     */
    public function mailFullOrderChange($order, $subject = NULL)
    {
        $mails = array_map('trim', explode(',', env('MAIL_TO_ADMIN', 'robot@martds.ru')));
        $mails[] = $order->email;

        if( !$subject){
            $subject = 'Заказ #'. $order->order_id .' на сайте '. env('SITE_NAME', array_get($_SERVER, 'HTTP_HOST')) .' изменен';
        }
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        Mail::send('larrock::emails.orderFull-delete', ['data' => $order->toArray(), 'subject' => $subject],
            function($message) use ($mails, $subject){
                $message->from('no-reply@'. array_get($_SERVER, 'HTTP_HOST'), env('MAIL_TO_ADMIN_NAME', 'ROBOT'));
                $message->to($mails);
                $message->subject($subject);
            });

        \Log::info('ORDER CHANGE: #'. $order->order_id .'. Order: '. json_encode($order));
    }
}