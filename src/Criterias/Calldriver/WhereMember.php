<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-07
 * Time: 12:07
 */

namespace Twdd\Criterias\Calldriver;


use Twdd\Models\Member;
use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class WhereMember extends Criteria
{
    private $member;

    public function __construct(Member $member)
    {
        $this->member = $member;
    }

    public function apply($model, RepositoryInterface $repository){
        $model = $model->where('calldriver_task_map.member_id', $this->member->id);

        return $model;
    }
}
