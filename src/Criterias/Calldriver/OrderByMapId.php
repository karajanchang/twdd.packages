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

class OrderByMapId extends Criteria
{
    private $sort;

    public function __construct(string $sort = 'asc')
    {
        $this->sort = $sort;
    }

    public function apply($model, RepositoryInterface $repository){
        $model = $model->orderby('calldriver_task_map.id', $this->sort);

        return $model;
    }
}
