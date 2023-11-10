<?php

namespace Twdd\Repositories;


use Twdd\Models\EdenredKey;
use Zhyu\Repositories\Eloquents\Repository;

class EdenredKeyRepository extends Repository
{
    public function model()
    {
        return EdenredKey::class;
    }

    /**
     * 用日期取出work key
     *
     * @param string $date (Y-m-d)
     * @return \Twdd\Models\EdenredKey
     */
    public function getWorkKeyByDate($date=date('Y-m-d'))
    {
        return $this->model->whereDate('created_at', $date)->first();
    }
}
