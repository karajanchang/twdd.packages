<?php


namespace Twdd\Criterias\Calldriver;


use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WherePayTypeNot4 extends Criteria
{
    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver.pay_type', '!=', 4);

        return $model;
    }
}