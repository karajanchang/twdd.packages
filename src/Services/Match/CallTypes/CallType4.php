<?php

namespace Twdd\Services\Match\CallTypes;


use Twdd\Services\Match\CallTypes\Traits\TraitAlwaysBlackList;
use Twdd\Services\Match\CallTypes\Traits\TraitAppVer;
use Twdd\Services\Match\CallTypes\Traits\TraitCallNoDuplicate;
use Twdd\Services\Match\CallTypes\Traits\TraitHaveNoRuningTask;
use Twdd\Services\Match\CallTypes\Traits\TraitHavePrematch;
use Twdd\Services\Match\CallTypes\Traits\TraitMemberCanMatch;
use Twdd\Services\Match\CallTypes\Traits\TraitMemberCanNotCall;
use Twdd\Services\Match\CallTypes\Traits\TraitServiceArea;

class CallType4 extends AbstractCall implements InterfaceMatchCallType
{
    use TraitAppVer;
    use TraitMemberCanMatch;
    use TraitMemberCanNotCall;
    use TraitAlwaysBlackList;
    use TraitServiceArea;
    use TraitCallNoDuplicate;
    use TraitHaveNoRuningTask;
    use TraitHavePrematch;


    protected $call_type = 4;
    public $title = '長途';

    /*
    * 這些是要不要檢查的,覆載用
    */
    protected $check_lists = [
        'AppVer' => 'error',
        'MemberCanMatch' => 'error',
        'MemberCanNotCall' => 'error',
        'AlwaysBlackList' => 'error',
        'ServiceArea' => 'error',
        'CheckParams' => 'error',
        'CallNoDuplicate' => 'error',
        'HaveNoRuningTask' => 'success',
        'HavePrematch' => 'error',
    ];

    /*
     * 參數檢查
     */
    public function check(array $params, array $remove_lists = []){
        $check = parent::check($params, $remove_lists);
        if($check===true) {
            $this->setParams($params);

            return true;
        }

        return $check;
    }

    public function match(array $other_params = []){
        //--檢查有沒有重覆呼叫
        $res = $this->noCheckList('CallNoDuplicate');
        if($res!==false && $this->CallNoDuplicate()!==true){

            return $this->{$res}('重覆呼叫，諘等候上一呼叫結束');
        }

        //---檢查有沒有進行中任務
        $res = $this->noCheckList('HaveNoRuningTask');
        if($res!==false && $this->HaveNoRuningTask()!==true){

            if($res=='error') {

                return $this->error('你有一進行中的任務，無法呼叫');
            }

            return $this->success('你有一進行中的任務，請稍後');
        }

        //---擋下在1.5小時內有預約呼叫的人
        $res = $this->noCheckList('HavePrematch');
        if($res!==false && $this->HavePrematch()===true){
            $msg = trans('messages.you_can_not_call_match_before_15_hour', ['hour' => env('MATCH_CANNOT_ACCEPT_WHEN_IN_PREMATH_HOUR', 1.5)]);

            return $this->error($msg);
        }

        $params = $this->processParams($this->params, $other_params);
        $calldriver = $this->getCalldriverServiceInstance()->create($params);
        if(isset($calldriver['error'])){

            return $this->error($calldriver['msg']->first(), $calldriver);
        }

        return $this->success('呼叫成功', $calldriver);
    }

    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []) : array{
        $params = parent::processParams($params);


        return $params;
    }

    public function rules() : array{

        return [
            'TS'                        =>  'required|integer',
            'lat'                       =>  'nullable|numeric',
            'lon'                       =>  'nullable|numeric',
            'city'                      =>  'required|string',
            'district'                  =>  'required|string',
            'addr'                      =>  'required|string',

            'zip'                       =>  'nullable|integer',
            'address'                   =>  'nullable|string',

            'lat_det'                       =>  'required|numeric',
            'lon_det'                       =>  'required|numeric',
            'city_det'                      =>  'required|string',
            'district_det'                  =>  'required|string',
            'addr_det'                      =>  'required|string',

            'zip_det'                       =>  'nullable|integer',
            'address_det'                   =>  'nullable|string',

            'UserCreditCode'            =>  'nullable|string',
            'UserCreditValue'           =>  'nullable|integer',
            'UserRemark'                =>  'nullable|string',
            'DriverID'                  =>  'nullable|string',
            'OtherInviteCode'           =>  'nullable|string',
            'call_member_id'            =>  'nullable|integer',
            'type'                      =>  'nullable|integer',
            'pay_type'                  =>  'required|integer',
            'call_type'                 =>  'required|integer',
        ];
    }

}
