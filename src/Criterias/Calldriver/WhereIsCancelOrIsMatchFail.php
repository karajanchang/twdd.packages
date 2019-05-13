<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-07
 * Time: 12:07
 */

namespace Twdd\Criterias\Calldriver;


use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereIsCancelOrIsMatchFail extends Criteria
{

    public function apply($model, RepositoryInterface $repository){
        $model->where(function($query){
           $query->where('is_cancel', 0);
           $query->where('IsMatchFail', 0);
        });

        return $model;
    }
}
