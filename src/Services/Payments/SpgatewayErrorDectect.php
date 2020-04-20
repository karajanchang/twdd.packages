<?php


namespace Twdd\Services\Payments;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Twdd\Models\MemberCreditcard;

class SpgatewayErrorDectect
{
    private $map = [];
    public function __construct()
    {
        $this->map = [
            'TRA10016' => [
                '卡片過期',
                '拒絕交易',
                '掛失卡',
                '非正常卡',
                '卡號保護中',
            ],
        ];
    }

    private function getPayFailType($msg){

        return new Collection([
            '掛失卡' => 1,
            '卡片過期' => 2,
        ]);
    }

    public function init(MemberCreditcard $memberCreditcard, string $status, string $message) : void{
        $pay_fail_type = $this->check($status, $message);
        //---要註記mark
        if($pay_fail_type > 0){
            try{
                $memberCreditcard->pay_fail_type = $pay_fail_type;
                $memberCreditcard->save();
                Log::info('SpgatewayErrorDectect 註記 memberCreditcard 付款失敗 id: ('.$memberCreditcard->id.')');
            }catch (\Exception $e){
                Log::error('SpgatewayErrorDectect 修改 memberCreditcard 付款失敗 id: ('.$memberCreditcard->id.') 錯誤', [$e]);
            }
        }
    }

    private function check(string $status, string $message) : int{
        $maps = new Collection($this->map);
        $array = $maps->get(strtoupper($status), []);
        if(count($array)){
            foreach($array as $msg){
                if(preg_match('/'.$msg.'/', $message)){

                    return $this->getPayFailType($msg)->get($msg, 0);
                }
            }
        }

        return 0;
    }

}