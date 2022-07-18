<?php

namespace Twdd\Repositories;

use Twdd\Models\BlackhatDetail;
use Zhyu\Repositories\Eloquents\Repository;

class BlackhatDetailRepository extends Repository
{
    public function model()
    {
        return BlackhatDetail::class;
    }

}
