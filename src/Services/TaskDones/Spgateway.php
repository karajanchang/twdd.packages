<?php


namespace Twdd\Services\TaskDones;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Zhyu\Facades\ZhyuTool;

class Spgateway extends TaskDoneAbstract implements TaskDoneInterface
{
    public function done(){
        DB::beginTransaction();
        try {
            //---系統費,黑卡不叩系統費
            $credit = round(ZhyuTool::plusMinusConvert((int)$this->twddFee));
            $this->doCreditChange(1, $credit);

            //---保險費
            $this->doCreditChange(2, -15);

            //---優惠回補
            $this->calucateBackUserCreditValue();

            //--擴大媒合回補
            $this->calcFarTaskCreditReward();

            $this->lastProcess();

            DB::commit();

            return true;
        }catch (\Exception $e){
            Log::error('任務 ('.$this->getTask()->id.') Spgateway 最後處理失敗: '.$e->getMessage(), [$e]);
            DB::rollBack();

            return false;
        }
    }

}