<?php


namespace Twdd\Repositories;


use Twdd\Models\EnterpriseStaffs;
use Zhyu\Repositories\Eloquents\Repository;

class EnterpriseStaffRepository extends Repository
{
    public function model()
    {
        return EnterpriseStaffs::class;
    }

    public function getStaff($phone)
    {
        $model = $this->getModel();

        return $model->where('mobile', $phone)
            ->where('enable', 1)
            ->where('service_permission', 3)
            ->whereHas('enterprise', function ($q) {
                $q->where('enable', '=', 1);
            })
            ->first();
    }
}
