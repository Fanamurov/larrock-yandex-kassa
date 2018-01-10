<?php

namespace Larrock\YandexKassa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Larrock\ComponentCart\Facades\LarrockCart;
use Larrock\ComponentPages\Facades\LarrockPages;
use Larrock\YandexKassa\Exceptions\YandexKassaEmptyPaymentId;
use Larrock\YandexKassa\Helper\CancelPayment;
use Larrock\YandexKassa\Helper\CapturePayment;
use Larrock\YandexKassa\Helper\CartAction;
use Larrock\YandexKassa\Helper\CreatePayment;
use Larrock\YandexKassa\Helper\GetPaymentInfo;
use Session;

class YandexKassaContoller extends Controller
{
    public $YKassa;

    public function __construct()
    {
        $this->YKassa = new YandexKassaComponent();
        $this->middleware(LarrockPages::combineFrontMiddlewares());
    }

    /**
     * Шаг 1. Создайте платеж с выбором способа оплаты на стороне Яндекс.Кассы
     * Шаг 2. Перенаправьте пользователя на страницу в Яндекс.Кассе
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws Exceptions\YandexKassaCreatePaymentNoCustomerNumber
     * @throws Exceptions\YandexKassaCreatePaymentNoMetadata
     * @throws Exceptions\YandexKassaCreatePaymentNoOrderItems
     * @throws Exceptions\YandexKassaCreatePaymentNoOrderNumber
     * @throws Exceptions\YandexKassaCreatePaymentNoRecept
     * @throws Exceptions\YandexKassaCreatePaymentNoReturnUrl
     * @throws Exceptions\YandexKassaCreatePaymentNoSum
     */
    public function createPayment(Request $request): \Illuminate\Http\RedirectResponse
    {
        /*$request->merge([
            'sum' => '1837.00',
            'cps_phone' => '89142165178',
            'cps_email' => 'fanamurov@ya.ru',
            'customerNumber' => 1,
            'orderNumber' => 2
        ]);*/
        $paymentHelper = new CreatePayment();
        $payment = $paymentHelper->create($request);

        $YKassa = new YandexKassaComponent();
        $pay = $YKassa->client->createPayment($payment);

        $cartAction = new CartAction();
        $cartAction->changePaymentData($pay);

        return redirect()->to($pay->confirmation->confirmation_url);
    }

    /**
     * Ожидание уведомления о платеже
     * Шаг 3. Дождитесь уведомления о платеже
     * Шаг 4. Подтвердите платеж или отмените
     *
     * @param $orderId
     * @param $userId
     * @return \Illuminate\Http\RedirectResponse
     * @throws YandexKassaEmptyPaymentId
     */
    public function returnURL($orderId, $userId)
    {
        $cartAction = new CartAction();
        if($get_order = LarrockCart::getModel()->whereOrderId($orderId)->whereUser($userId)->first()){
            $getPaymentInfo = new GetPaymentInfo();
            $payment = $getPaymentInfo->getPaymentInfo($get_order->invoiceId);
            $cartAction->changePaymentData($payment);

            switch ($payment->status) {
                case 'waiting_for_capture':
                    $capturePayment = new CapturePayment();
                    $capturePayment->capturePayment($payment);
                    $cartAction->changePaymentData($payment);
                    break;
                case 'pending':
                    if($payment->paid === false){
                        echo 'Ожидается поступление оплаты...';
                        Session::push('message.success', 'Ожидается поступление оплаты...');
                        //return redirect()->to($payment->confirmation->confirmation_url);
                    }else{
                        echo 'Платеж обрабатывается...';
                    }
                    return redirect()->to('/cabinet');
                    //Session::push('message.success', 'Платеж обрабатывается...');
                    break;
                case 'succeeded':
                    $cartAction->changePaymentData($payment);
                    $cartAction->changeOrderStatus($payment);
                    echo 'Платеж успешно проведен';
                    Session::push('message.success', 'Платеж успешно проведен');
                    return redirect()->to('/cabinet');
                    break;
                case 'canceled':
                    $cartAction->changePaymentData($payment);
                    $cartAction->changeOrderStatus($payment);
                    echo 'Платеж отменен';
                    Session::push('message.danger', 'Платеж отменен');
                    return redirect()->to('/cabinet');
                    break;
            }
        }

        Session::push('message.danger', 'Такого заказа нет в нашем магазине');
        return redirect()->to('/');
    }

    /**
     * Отмена платежа
     * @param $paymentId
     */
    public function cancelPayment($paymentId)
    {
        $cancelPayment = new CancelPayment();
        $cancelPayment->cancelPaymentById($paymentId);
    }
}