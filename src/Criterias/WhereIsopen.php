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

class WhereIsopen extends Criteria
{
    private $prefix = null;

    public function __construct($prefix = null)
    {
        $this->prefix = $prefix;
    }

    public function apply($model, RepositoryInterface $repository){
        $col = is_null($this->prefix) ? 'isopen' : $this->prefix.'.isopen';
        $model = $model->where($col, 1);

        return $model;
    }
}
