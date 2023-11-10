<?php

namespace Twdd\Repositories;


use Twdd\Models\EdenredCoupon;
use Zhyu\Repositories\Eloquents\Repository;

class EdenredCouponRepository extends Repository
{
    public function model()
    {
        return EdenredCoupon::class;
    }

    /**
     * 用日期取出最大的序號
     *
     * @param string $date (Y-m-d)
     * @return \Twdd\Models\EdenredCoupon
     */
    public function getMaxSsnByDate($date=date('Y-m-d'))
    {
        $max = $this->model->whereDate('created_at', $date)->max('order_no') ?? 0;
        return $max + 1;
    }
}
