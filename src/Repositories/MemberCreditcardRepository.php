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
        $models = $this->findWhereCache(['member_id' => $task->member_id], ['*'], 'MemberCreditcardRepository'.$task->member_id, 600);
        $model = $models->where('is_default', 1)->first();
        if(!empty($model->id)){

            return $model;
        }

        return $models->first();
    }

    public function findByMemberId(int $member_id){

        return $this->findby('member_id', $member_id);
    }

    public function numsByMemberId(int $member_id){

        return $this->where('member_id', $member_id)->count();
    }

}