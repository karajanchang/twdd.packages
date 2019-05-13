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

class WhereTSOver extends Criteria
{

    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver_task_map.TS', '>=', env('CALLDRIVER_CHECK_SECONDS', 60));

        return $model;
    }
}
