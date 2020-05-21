<?php


namespace Twdd\Services\Payments;


interface PaymentInterface
{
    public function back(int $amt, bool $is_notify_member = false);

    public function cancel(string $OrderNo = null, int $amount = null);

    public function pay(array $params = [], bool $is_notify_member = true);

    public function query();

}
