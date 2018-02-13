<?php

namespace Larrock\YandexKassa;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Larrock\ComponentCart\Facades\LarrockCart;
use Larrock\ComponentPages\Facades\LarrockPages;
use Larrock\YandexKassa\Exceptions\YandexKassaEmptyPaymentId;
use Larrock\YandexKassa\Helper\CancelPayment;
use Larrock\YandexKassa\Helper\CapturePayment;
use Larrock\YandexKassa\Helper\CartAction;
use Larrock\YandexKassa\Helper\CreatePayment;
use Larrock\YandexKassa\Helper\CreateRefund;
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
     * @see https://kassa.yandex.ru/docs/checkout-api/#podtwerzhdenie-platezha
     * @see https://github.com/yandex-money/yandex-money-joinup/blob/master/checkout-api/031-01%20url%20для%20уведомлений.md
     * @see https://github.com/yandex-money/yandex-money-joinup/blob/master/checkout-api/sample/rest/insomnia/how-to.md
     *
     * @param Request $request
     * @param $orderId
     * @param $userId
     * @return \Illuminate\Http\RedirectResponse
     * @throws YandexKassaEmptyPaymentId
     */
    public function returnURL(Request $request, $orderId = null, $userId = null)
    {
        if( !$request->has('orderId')){
            if($get_order = LarrockCart::getModel()->whereOrderId($orderId)->whereUser($userId)->first()){
                if(empty($get_order->invoiceId)){
                    Session::push('message.danger', 'Такого заказа нет в нашем магазине');
                }else{
                    $orderId = $get_order->invoiceId;
                }
            }
        }else{
            $orderId = $request->get('orderId');
        }

        if(empty($orderId)){
            Session::push('message.danger', 'orderId не передан');
        }

        $cartAction = new CartAction();
        $getPaymentInfo = new GetPaymentInfo();
        $payment = $getPaymentInfo->getPaymentInfo($orderId);
        $cartAction->changePaymentData($payment);

        switch ($payment->status) {
            case 'waiting_for_capture':
                $capturePayment = new CapturePayment();
                $capture = $capturePayment->capturePayment($payment);
                $payment = $getPaymentInfo->getPaymentInfo($orderId);
                $cartAction->changeOrderStatus($payment);
                $cartAction->changePaymentData($payment);
                if($capture->status === 'succeeded'){
                    Session::push('message.success', trans('larrock::ykassa.status.default.succeeded'));
                    return redirect()->to('/cabinet');
                }
                if($capture->status === 'canceled'){
                    Session::push('message.success', trans('larrock::ykassa.status.default.canceled'));
                    return redirect()->to('/cabinet');
                }
                return response()->make('STATUS:'. $capture->status);
                break;
            case 'pending':
                Session::push('message.success', trans('larrock::ykassa.status.default.pending'));
                $cartAction->changePaymentData($payment);
                $cartAction->changeOrderStatus($payment);
                return redirect()->to('/cabinet');
                break;
            case 'succeeded':
                $cartAction->changePaymentData($payment);
                $cartAction->changeOrderStatus($payment);
                Session::push('message.success', trans('larrock::ykassa.status.default.succeeded'));
                return redirect()->to('/cabinet');
                break;
            case 'canceled':
                $cartAction->changePaymentData($payment);
                $cartAction->changeOrderStatus($payment);
                Session::push('message.success', trans('larrock::ykassa.status.default.canceled'));
                return redirect()->to('/cabinet');
                break;
        }

        return redirect()->to('/cabinet');
    }

    /**
     * Отмена платежа
     * Возврат может быть осуществлен только администраторами
     *
     * @param Guard $auth
     * @param $paymentId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelPayment(Guard $auth, $paymentId): \Illuminate\Http\RedirectResponse
    {
        if ($auth->check() && $auth->user()->level() >= 3) {
            $cancelPayment = new CancelPayment();
            $cancelPayment->cancelPaymentById($paymentId);
            Session::push('message.success', trans('larrock::ykassa.status.canceled.succeeded'));
        }else{
            Session::push('message.danger', 'Недостаточно прав для выполнения операции');
            Session::push('message.danger', trans('larrock::ykassa.status.canceled.error'));
        }
        return back();
    }

    /**
     * Возврат платежа
     * Возврат может быть осуществлен только администраторами
     *
     * @param Guard $auth
     * @param $payment_id
     * @return \Illuminate\Http\RedirectResponse
     * @throws YandexKassaEmptyPaymentId
     */
    public function createRefund(Guard $auth, $payment_id): \Illuminate\Http\RedirectResponse
    {
        if ($auth->check() && $auth->user()->level() >= 3) {
            $getPaymentInfo = new GetPaymentInfo();
            $payment = $getPaymentInfo->getPaymentInfo($payment_id);

            $createRefund = new CreateRefund();
            $refund = $createRefund->createRefund($payment_id, $payment->amount->value);
            if($refund->status === 'succeeded'){
                Session::push('message.success', trans('larrock::ykassa.status.refund.succeeded'));
                $cartAction = new CartAction();
                $cartAction->changeOrderStatusRefund($refund);
            }else{
                Session::push('message.danger', trans('larrock::ykassa.status.refund.error'));
            }
        }else{
            Session::push('message.danger', 'Недостаточно прав для выполнения операции');
            Session::push('message.danger', trans('larrock::ykassa.status.refund.error'));
        }
        return back();
    }
}