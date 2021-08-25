<?php


namespace Twdd\Criterias\Calldriver;


use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereTypeNot10 extends Criteria
{
    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver.type', '!=', 10);

        return $model;
    }
}