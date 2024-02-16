<?php


namespace Twdd\Services\Match\CallTypes;


use Illuminate\Support\Facades\Log;
use Twdd\Repositories\EnterpriseStaffRepository;
use Twdd\Services\Match\CallTypes\Traits\TraitAlwaysBlackList;
use Twdd\Services\Match\CallTypes\Traits\TraitAppVer;
use Twdd\Services\Match\CallTypes\Traits\TraitCallNoDuplicate;
use Twdd\Services\Match\CallTypes\Traits\TraitHaveNoRuningTask;
use Twdd\Services\Match\CallTypes\Traits\TraitHavePrematch;
use Twdd\Services\Match\CallTypes\Traits\TraitMemberCanMatch;
use Twdd\Services\Match\CallTypes\Traits\TraitMemberCanNotCall;
use Twdd\Services\Match\CallTypes\Traits\TraitServiceArea;
use Twdd\Models\Driver;
use Twdd\Services\PushNotificationService;
use Twdd\Facades\PushNotification;

class CallType1 extends AbstractCall implements InterfaceMatchCallType
{
    use TraitAppVer;
    use TraitMemberCanMatch;
    use TraitMemberCanNotCall;
    use TraitAlwaysBlackList;
    use TraitServiceArea;
    use TraitCallNoDuplicate;
    use TraitHaveNoRuningTask;
    use TraitHavePrematch;


    protected $call_type = 1;
    protected $title = '一般';

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
    public function check(array $params, array $remove_lists = [])
    {
        $check = parent::check($params, $remove_lists);
        // 如果是企業簽單
        if ($params['pay_type'] == 3) {
            $repository = app(EnterpriseStaffRepository::class);
            $enterprise = $repository->getStaff($this->member->UserPhone);
            if (empty($enterprise)) {
                return $this->error('查無對應企業，無法使用企業簽單付款');
            }
            $params['enterprise_id'] = $enterprise->enterprise_id;
        }
        if ($check === true) {
            $this->setParams($params);

            return true;
        }

        return $check;
    }

    public function match(array $other_params = [])
    {

        //--檢查有沒有重覆呼叫
        $res = $this->noCheckList('CallNoDuplicate');
        if ($res !== false && $this->CallNoDuplicate() !== true) {

            return $this->{$res}('重覆呼叫，請等候上一呼叫結束');
        }

        //---檢查有沒有進行中任務
        $res = $this->noCheckList('HaveNoRuningTask');
        if ($res !== false && $this->HaveNoRuningTask() !== true) {

            if ($res == 'error') {

                return $this->error('你有一進行中的任務，無法呼叫');
            }

            return $this->success('你有一進行中的任務，請稍後');
        }

        //---擋下在1.5小時內有預約呼叫的人
        $res = $this->noCheckList('HavePrematch');
        if ($res !== false && $this->HavePrematch() === true) {
            $msg = trans('messages.you_can_not_call_match_before_15_hour', ['hour' => env('MATCH_CANNOT_ACCEPT_WHEN_IN_PREMATH_HOUR', 1.5)]);

            return $this->error($msg);
        }

        $params = $this->processParams($this->params, $other_params);
        $calldriver = $this->getCalldriverServiceInstance()->setCallDriver($this->callDriver)->create($params);
        if (isset($calldriver['error'])) {
            $msg = !is_null($calldriver['msg']) ? $calldriver['msg']->first() : '系統發生錯誤';
            Log::info(__CLASS__ . '::' . __METHOD__ . 'error: ', [$calldriver]);

            return $this->error($msg, $calldriver);
        }

        if (isset($params['is_volunteer_extra_price']) && $params['is_volunteer_extra_price'] > 0) {
            $this->markupNotification($params);
        }
        return $this->success('呼叫成功', $calldriver);
    }

    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []): array
    {
        $params = parent::processParams($params, $other_params);

        //--app呼叫且呼叫方式不為預約呼叫把TS改成
        if ($params['type'] == 1) {
            $params['TS'] = time();
        }

        return $params;
    }


    public function rules(): array
    {

        return [
            'TS'                        =>  'required|integer',
            'lat'                       =>  'required|numeric',
            'lon'                       =>  'required|numeric',
            'city'                      =>  'nullable|string',
            'district'                  =>  'nullable|string',
            'addr'                      =>  'nullable|string',
            'zip'                       =>  'nullable|string',
            'address'                   =>  'nullable|string',
            'UserCreditCode'            =>  'nullable|string',
            'UserCreditValue'           =>  'nullable|integer',
            'UserRemark'                =>  'nullable|string',
            'DriverID'                  =>  'nullable|string',
            'OtherInviteCode'           =>  'nullable|string',
            'call_member_id'            =>  'nullable|integer',
            'type'                      =>  'nullable|integer',
            'pay_type'                  =>  'required|integer',
            'call_type'                 =>  'required|integer',
            'people'                    =>  'nullable|integer',
        ];
    }

    private function markupNotification($params)
    {

        $location = app(\Twdd\Helpers\LatLonService::class)->citydistrictFromLatlonOrZip($params['lat'], $params['lon']);

        $group = $this->locationToDriverGroup($location);

        $driverList = Driver::leftjoin('driver_push', 'driver_push.driver_id', 'driver.id')
            ->where("DriverState", "<>", "2")
            ->where("is_online", 1)
            ->where("is_out", 0)
            ->whereIn('driver_group_id', $group)
            ->get(['driver.id', 'DriverState', 'driver_group_id', 'DeviceType', 'PushToken'])->toArray();

        $price = $params['is_volunteer_extra_price_value'] ?? 0;
        foreach ($driverList as $driver) {

            //基隆本地接到雙北桃園推播要額外顯示縣市訊息
            if (($location['city_id'] == 1 || $location['city_id'] == 3 || $location['city_id'] == 8) && $driver['driver_group_id'] == 9) {
                $city = mb_substr($location['city'], 0, 2, 'utf-8');
                $body = $city . '【' . $params['district'] . '】';
            } else {
                $body = '【' . $params['district'] . '】';
            }

            if ($driver['DriverState'] == 0) {
                $body .= '有乘客加價' . $price . '元，請附近夥伴上線接單。';
            } else {
                $body .= '區域內有乘客加價' . $price . '元，請夥伴移動接單。';
            }

            if ($driver['DeviceType'] == 'iPhone') {
                PushNotification::driver('ios')->tokens($driver['PushToken'])->action('PushMsg')->title('乘客加價任務💰')->body($body)->send();
            } else {
                PushNotification::driver('android')->tokens($driver['PushToken'])->action('PushMsg')->title('乘客加價任務💰')->body($body)->send();
            }
        }
    }

    private function locationToDriverGroup($location)
    {
        $group = [];
        switch ($location['city_id']) {
            case 1:
            case 3:
                array_push($group, 1);
                array_push($group, 9); //雙北要多推給基隆
                break;
            case 2:
                array_push($group, 9);
                break;
            case 6:
            case 7:
                array_push($group, 4);
                break;
            case 8:
                array_push($group, 3);
                break;
            case 10:
                array_push($group, 5);
                break;
            case 11:
                array_push($group, 8);
                break;
            case 16:
                array_push($group, 6);
                break;
            case 17:
                array_push($group, 7);
                break;
            case 20:
                array_push($group, 10);
                break;
            default:
                break;
        }

        switch ($location['district_id']) {

            case 87:
                //龜山在桃園, 多推給雙北基隆
                array_push($group, 1);
                array_push($group, 9);
                break;
            case 36:
            case 37:
            case 38:
            case 42:
                //林口、鶯歌、三峽、樹林是新北, 多給桃園
                array_push($group, 3);
            case 270:
            case 272:
            case 278:
            case 292:
                //高雄的湖內、茄錠、路竹、阿連 -> 多給台南
                array_push($group, 6);
                break;
            default:
                break;
        }

        return $group;
    }
}
