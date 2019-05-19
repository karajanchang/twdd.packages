<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-07
 * Time: 12:07
 */

namespace Twdd\Criterias\Calldriver;


use App\User;
use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereUser extends Criteria
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver.user_id', $this->user->id);

        return $model;
    }
}
