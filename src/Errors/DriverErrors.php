<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;

class DriverErrors extends ErrorAbstract
{
    protected $unit = 'driver';

    public function error1000(){

        return trans('twdd::driver.driver_does_not_exists');
    }

    public function error1001(){

        return trans('twdd::driver.no_driver_id');
    }

    public function error1002(){

        return trans('twdd::driver.no_driver_password');
    }

    public function error1003(){

        return trans('twdd::driver.no_driver_push_token');
    }

    public function error1004(){

        return trans('twdd::driver.no_driver_phone');
    }

    public function error1005(){

        return trans('twdd::driver.driver_can_not_online');
    }

    public function error1006(){

        return trans('twdd::driver.driver_is_out');
    }

    public function error1007(){

        return trans('twdd::driver.driver_credit_is_under_100');
    }

    public function error1008(){

        return trans('twdd::driver.driver_is_not_driver');
    }

    public function error1009(){
        $replaces = $this->getReplaces('1009');
        return trans('twdd::driver.driver_is_rookie', $replaces['1009']);
    }

    public function error1010(){
        //$replaces = $this->getReplaces('1010');
        return trans('twdd::driver.driver_is_temp_offline');
    }

    public function error1011(){

        return trans('twdd::errors.login_fail');
    }

    public function error1012(){

        return trans('twdd::driver.driver_can_not_offline_because_onservice');
    }

    public function error2003(){

        return trans('twdd::driver.this_driver_doesnot_exist');
    }

    public function error3000(){

        return trans('twdd::driver.driver_offline_fail');
    }

    public function error3001(){

        return trans('twdd::driver.driver_online_fail');
    }

    public function error3002(){

        return trans('twdd::driver.driver_onservice_fail');
    }
}