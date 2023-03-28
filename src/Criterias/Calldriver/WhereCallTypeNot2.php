<?php


namespace Twdd\Criterias\Calldriver;


use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereCallTypeNot2 extends Criteria
{
    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver.call_type', '!=', 2);

        return $model;
    }
}
