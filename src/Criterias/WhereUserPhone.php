<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-07
 * Time: 12:07
 */

namespace Twdd\Criterias;


use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereUserPhone extends Criteria
{
    private $UserPhone = null;

    public function __construct($UserPhone)
    {
        $this->UserPhone = $UserPhone;
    }

    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('UserPhone', $this->UserPhone);

        return $model;
    }
}