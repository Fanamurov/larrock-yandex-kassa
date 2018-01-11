<?php

namespace Larrock\YandexKassa\Helper;

use Larrock\ComponentCart\Models\Cart;
use Mail;
use Session;

class CartAction
{
    /**
     * Проверка заказа на сайте
     *
     * @param $answer
     * @return bool|null|Cart
     */
    public function checkOrderOnSite($answer)
    {
        if($get_order = \LarrockCart::getModel()->whereOrderId($answer->metadata->orderNumber)
            ->whereUser($answer->metadata->customerNumber)->first()){
            return $get_order;
        }
        Session::push('message.danger', 'Заказ #'. $answer->metadata->orderNumber .' не существует в нашем магазине');
        return NULL;
    }

    /**
     * Запись данных о процессе оплаты в БД
     *
     * @param $answer
     * @return bool|Cart|null
     */
    public function changePaymentData($answer)
    {
        if($get_order = $this->checkOrderOnSite($answer)){
            $get_order->pay_at = $answer->created_at;
            $get_order->invoiceId = $answer->id;
            $get_order->payment_data = json_encode($answer);
            $get_order->save();
        }
        return $get_order;
    }

    /**
     * Смена статуса оплаты заказа в БД на "оплачено"
     *
     * @param $answer
     * @return bool|Cart|null
     */
    public function changeOrderStatus($answer)
    {
        if($get_order = $this->checkOrderOnSite($answer)){
            if($answer->status === 'succeeded'){
                $get_order->status_pay = 'Оплачено';
                if($get_order->save()){
                    Session::push('message.success', 'Заказ #'. $get_order->order_id .' успешно оплачен');
                    $this->mailFullOrderChange($get_order);
                    return TRUE;
                }

                \Log::alert('Заказ #'. $get_order->order_id .' успешно оплачен, но произошла ошибка смены статуса заказа');
                Session::push('message.danger', 'Заказ #'. $get_order->order_id .' успешно оплачен, но произошла ошибка смены статуса заказа');
                Session::push('message.danger', 'Администраторы сайта в кратчайшие сроки проверят данные и сменят статус оплаты');
            }
        }
        return $get_order;
    }

    /**
     * Смена статуса оплаты заказа в БД на "не оплачено" (после Refund)
     *
     * @param $answer
     * @return bool|Cart|null
     */
    public function changeOrderStatusRefund($answer)
    {
        if($get_order = $this->checkOrderOnSite($answer)){
            if($answer->status === 'succeeded'){
                $get_order->status_pay = 'Не оплачено';
                if($get_order->save()){
                    Session::push('message.success', 'Заказ #'. $get_order->order_id .' переведен в статус "Не оплачено". Возврат средст осуществлен.');
                    $this->mailFullOrderChange($get_order);
                    return TRUE;
                }

                \Log::alert('Заказ #'. $get_order->order_id .' переведен в статус "Не оплачено", но произошла ошибка смены статуса заказа. Возврат средст осуществлен.');
                Session::push('message.danger', 'Заказ #'. $get_order->order_id .' переведен в статус "Не оплачено", но произошла ошибка смены статуса заказа. Возврат средст осуществлен.');
                Session::push('message.danger', 'Администраторы сайта в кратчайшие сроки проверят данные и сменят статус оплаты');
            }
        }
        return $get_order;
    }

    /**
     * Отправка email'а об изменении заказа
     *
     * @param         $order
     * @param null $subject
     * @return bool
     */
    public function mailFullOrderChange($order, $subject = NULL)
    {
        $mails = array_map('trim', explode(',', env('MAIL_TO_ADMIN', 'robot@martds.ru')));
        $mails[] = $order->email;

        if( !$subject){
            $subject = 'Заказ #'. $order->order_id .' на сайте '. env('SITE_NAME', array_get($_SERVER, 'HTTP_HOST')) .' изменен';
        }
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        Mail::send('larrock::emails.orderFull-delete', ['data' => $order->toArray(), 'subject' => $subject, 'app' => \LarrockCart::getConfig()],
            function($message) use ($mails, $subject){
                $message->from('no-reply@'. array_get($_SERVER, 'HTTP_HOST'), env('MAIL_TO_ADMIN_NAME', 'ROBOT'));
                $message->to($mails);
                $message->subject($subject);
            });

        \Log::info('ORDER CHANGE: #'. $order->order_id .'. Order: '. json_encode($order));
        return TRUE;
    }
}