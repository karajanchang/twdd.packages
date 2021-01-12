<?php


namespace Twdd\Repositories;


use Illuminate\Database\Eloquent\Model;
use Twdd\Models\CarFactoryCreditcard;
use Zhyu\Repositories\Eloquents\Repository;

class CarFactoryCreditcardRepository extends Repository
{
    public function model()
    {
        return CarFactoryCreditcard::class;
    }

    public function findByTask(Model $task){
        $models = $this->findWhereCache(['car_factory_id' => $task->car_factory_id], ['*'], 'CarFactoryCreditcardRepository'.$task->car_factory_id, 600);
        $model = $models->where('is_default', 1)->first();
        if(!empty($model->id)){

            return $model;
        }

        return $models->first();
    }

}