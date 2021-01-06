<?php


namespace Twdd\Errors;


class CreditcardError extends ErrorAbstract
{
    protected $unit = 'creditcard';

    public function error0001(){

        return trans('twdd::creditcard.no_valid_drivermerchant');
    }
}