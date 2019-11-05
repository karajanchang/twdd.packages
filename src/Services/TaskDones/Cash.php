<?php


namespace Twdd\Services\TaskDones;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Zhyu\Facades\ZhyuTool;


class Cash extends TaskDoneAbstract implements TaskDoneInterface
{
    public function done(){
        DB::beginTransaction();
        try {
            //---系統費,黑卡不叩系統費
            $credit = round(ZhyuTool::plusMinusConvert($this->twddFee));
            $this->doCreditChange(1, $credit);

            //---保險費
            $this->doCreditChange(1, -15);

            //---優惠回補
            $this->calucateBackUserCreditValue();

            $this->lastProcess();

            DB::commit();

            return true;
        }catch (\Exception $e){
            Log::error('任務 ('.$this->getTask()->id.') cash 最後處理失敗: '.$e->getMessage());
            DB::rollBack();

            return false;
        }
    }

}