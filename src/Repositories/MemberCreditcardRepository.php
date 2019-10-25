<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Illuminate\Database\Eloquent\Model;
use Twdd\Models\MemberCreditcard;
use Zhyu\Repositories\Eloquents\Repository;

class MemberCreditcardRepository extends Repository
{

    public function model()
    {
        return MemberCreditcard::class;
    }

    public function findByTaskId(Model $task){
        $model = $this->findby('member_id', $task->member_id);

        return $model;
    }
}