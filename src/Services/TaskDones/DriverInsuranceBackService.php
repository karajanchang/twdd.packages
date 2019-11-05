<?php


namespace Twdd\Services\TaskDones;


use Illuminate\Database\Eloquent\Model;
use Twdd\Models\DriverInsuranceBack;
use Twdd\Models\DriverInsuranceBackLog;
use Twdd\Repositories\DriverInsuranceBackRepository;

class DriverInsuranceBackService
{
    private $repository;
    private $task;

    public function __construct(DriverInsuranceBackRepository $repository)
    {
        $this->repository = $repository;
    }

    public function task(Model $task){
        $this->task = $task;

        return $this;
    }

    public function cost(){
        $driverInsuranceBack = $this->repository->fetchLastByDriver($this->task->driver_id);

        $money = round($this->task->TaskFee * 0.1);
        if(isset($driverInsuranceBack->id) && $driverInsuranceBack->id>0 && $money>0){
            $money_diff = $driverInsuranceBack->money - $driverInsuranceBack->money_last;
            //--還沒還的錢比10%少
            if($money_diff < $money){
                $money = $money_diff;
            }
            $driverInsuranceBack->money_last = $driverInsuranceBack->money_last + $money;
            $driverInsuranceBack->save();

            $this->log($driverInsuranceBack, $money);

            return [
                'InsuranceBack' => (0 - $money),
            ];
        }

        return [
            'InsuranceBack' => 0,
        ];
    }

    private function log(DriverInsuranceBack $driverInsuranceBack, $money){
        DriverInsuranceBackLog::create([
            'driver_insurance_back_id' => $driverInsuranceBack->id,
            'money' => $money,
        ]);
    }
}