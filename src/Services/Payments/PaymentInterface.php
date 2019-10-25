<?php


namespace Twdd\Services\Payments;


interface PaymentInterface
{
    public function back();

    public function cancel();

    public function pay(array $params = []);

    public function query();

}