<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;

class PaymentErrors extends ErrorAbstract
{
    protected $unit = 'payment';

    /*
    public function error1000(){

        return trans('twdd::payment.payment_error');
    }
    */

    public function error2001(){

        return trans('twdd::payment.must_provide_email_for_spgateway_to_pay');
    }

    public function error2002(){

        return trans('twdd::payment.money_must_over_zero_for_spgateway_to_pay');
    }

    public function error2003(){

        return trans('twdd::payment.spgateway_error');
    }

    public function error2004(){

        return trans('twdd::payment.spgateway_time_too_close');
    }

}