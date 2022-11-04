<?php


namespace Twdd\Criterias\Calldriver;


use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereCallTypeNot5 extends Criteria
{
    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver.call_type', '!=', 5);

        return $model;
    }
}
