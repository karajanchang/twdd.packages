<?php


namespace Twdd\Services\Match\CallTypes;

use DB;
use Carbon\Carbon;
use Twdd\Models\Driver;
use Twdd\Models\Calldriver;
use Twdd\Models\BlackhatDetail;
use Twdd\Models\DriverGroupCallCity;
use Twdd\Models\BlackhatDriverSchedule;
use Twdd\Facades\LatLonService;
use Twdd\Facades\PayService;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Services\Match\CallTypes\Traits\TraitAlwaysBlackList;
use Twdd\Services\Match\CallTypes\Traits\TraitAppVer;
use Twdd\Services\Match\CallTypes\Traits\TraitCallNoDuplicate;
use Twdd\Services\Match\CallTypes\Traits\TraitCanPrematchByTS;
use Twdd\Services\Match\CallTypes\Traits\TraitCheckHaveBindCreditCard;
use Twdd\Services\Match\CallTypes\Traits\TraitHaveNoRuningTask;
use Twdd\Services\Match\CallTypes\Traits\TraitHavePrematch;
use Twdd\Services\Match\CallTypes\Traits\TraitMemberCanMatch;
use Twdd\Services\Match\CallTypes\Traits\TraitMemberCanNotCall;
use Twdd\Services\Match\CallTypes\Traits\TraitOnlyOnePrematch;
use Twdd\Services\Match\CallTypes\Traits\TraitServiceArea;

class CallType5 extends AbstractCall implements InterfaceMatchCallType
{
    use TraitAppVer;
    use TraitMemberCanMatch;
    use TraitMemberCanNotCall;
    use TraitAlwaysBlackList;
    use TraitServiceArea;
    use TraitCallNoDuplicate;
    use TraitHaveNoRuningTask;
    use TraitHavePrematch;
    use TraitCanPrematchByTS;
    use TraitOnlyOnePrematch;
    use TraitCheckHaveBindCreditCard;

    protected $call_type = 5;
    public $title = '黑帽客預約';


    protected $check_lists = [
        'AlwaysBlackList' => 'error',
        'ServiceArea' => 'error',
        'CheckParams' => 'error',
        'CheckHaveBindCreditCard' => 'error',
    ];


    # 參數檢查
    public function check(array $params, array $remove_lists = [])
    {
        $check = parent::check($params, $remove_lists);

        if($check === true) {
            $this->setParams($params);
        }

        return $check;
    }

    # 參數檢查
    public function cancel_check(array $params, array $remove_lists = [])
    {
        $valid = $this->valid($this->cancel_rules(), $params);
        if ($valid !== true) {
            return $valid;
        } else {
            $this->setParams($params);
        }

        return true;
    }

    public function match(array $other_params = [])
    {
        //--預約一定要用信用卡
        $res = $this->noCheckList('CheckHaveBindCreditCard');
        if($res !== false && $this->CheckHaveBindCreditCard()!==true){

            return $this->{$res}('預約代駕付款方式限定信用卡');
        }

        $params = $this->processParams($this->params, $other_params);

        $driverID = $this->matchDriver([
            'zip' => $params['zip'],
            'blackHat_type' => $params['blackHat_type'],
            'start_date' => $params['start_date'],
            'maybe_over_time' => $params['maybe_over_time']
        ]);

        if (!$driverID) {
            return $this->error('找不到司機', null, 2001);
        }

        // 若找不到要建立單？

        $callDriver = app(DriverRepository::class)->findByDriverID($driverID, ['id']);
        $blackHatDetail = $this->getCalldriverServiceInstance()->setCallDriver($callDriver)->create($params);

        if(isset($blackHatDetail['error'])) {
            $msg = !is_null($blackHatDetail['msg']) ? $blackHatDetail['msg']->first() : '系統發生錯誤';
            Log::info(__CLASS__.'::'.__METHOD__.'error: ', [$blackHatDetail]);
            return $this->error($msg, $blackHatDetail);
        }

        $calldriverTaskMap = $blackHatDetail->calldriver_task_map;

        $taskFee = ($params['blackHat_type'] == 5) ? 1980 : 2680;
        $payParams['money'] = $taskFee / 2;

        $pay_result = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->pay($payParams);

        if (isset($pay_result['error'])) {

            $blackHatDetail->pay_status = 2; # 預約成功 刷卡失敗
            $blackHatDetail->prematch_status = 1;
            $blackHatDetail->save();

            $msg = !is_null($pay_result['msg']) ? $pay_result['msg'] : '系統發生錯誤';
            return $this->error($msg);

        } else {

            $blackHatDetail->pay_status = 1; # 預約成功 刷卡成功
            $blackHatDetail->prematch_status = 1;
            $blackHatDetail->save();

            return $this->success('呼叫成功', $callDriver);
        }
    }

    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []) : array
    {
        $params = parent::processParams($params, $other_params);

        // TS
        $params['TS'] = time();
        $params['pay_type'] = 2;
        $params['call_type'] = 5;

        return $params;
    }

    public function cancel(array $other_params = [])
    {
        $params = $this->params;

        $calldriver = Calldriver::where('id', $params['calldriver_id'])->first();

        $calldriverTaskMap = $calldriver->calldriver_task_map[0];
        $blackhatDetail = $calldriverTaskMap->blackhat_detail;

        if (!$blackhatDetail) {
            return $this->error('沒有此預約單');
        }

        $payQuery = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->query();

        // 用 Query 判斷走 back or cancel
        //var_dump($payQuery);exit;

        return $this->success('退款成功');
    }


    public function rules() : array {

        return [
            'lat'                       =>  'required|numeric',
            'lon'                       =>  'required|numeric',
            'city'                      =>  'nullable|string',
            'district'                  =>  'nullable|string',
            'addr'                      =>  'nullable|string',
            'zip'                       =>  'nullable|string',
            'address'                   =>  'nullable|string',

            'start_date'                =>  'required|date',
            'maybe_over_time'           =>  'required|integer',

            'UserRemark'                =>  'nullable|string',
            'type'                      =>  'nullable|integer',
        ];
    }

    public function cancel_rules(): array
    {
        return [
            'calldriver_id' => 'required|integer'
        ];
    }

    public function matchDriver($params)
    {

        $zip = $params['zip'];

        // 抓取縣市
        $location = LatLonService::locationFromZip($zip)->first();
        $cityId = $location->city_id;

        // 透過縣市抓取區域司機群
        $driverGroup = DriverGroupCallCity::select('drivergroup_id')->where('city_id', $cityId)->get()->pluck('drivergroup_id')->all();

        // 區域司機群
        $drivers = Driver::whereIn('driver_group_id', $driverGroup)->where('is_online', 1)->get()->pluck('id')->toArray();

        // type
        $blackHatType = $params['blackHat_type'];
        $blackHatHour = ($blackHatType == 1) ? 5 : 8;
        $blackHatMaybeOverTime = $params['maybe_over_time'];
        $blackHatCurrentDate = Carbon::parse($params['start_date'])->format('Y-m-d');
        $blackHatStartBeforeDate = Carbon::parse($params['start_date'])->subDays(1)->format('Y-m-d 00:00:00');
        $blackHatStartAfterDate  = Carbon::parse($params['start_date'])->addDays(1)->format('Y-m-d 23:59:59');
        $blackHatStartDate = Carbon::parse($params['start_date'])->format('Y-m-d H:i:s');
        $blackHatEndDate = Carbon::parse($blackHatStartDate)->addHour($blackHatHour)->format('Y-m-d H:i:s');
        $blackHatStartH = Carbon::parse($params['start_date'])->format('Y-m-d H:00:00');
        $blackHatEndH = Carbon::parse($blackHatStartH)->addHour($blackHatHour - 1)->format('Y-m-d H:i:s');


        // 抓取排班司機
        $driverId = BlackhatDriverSchedule::select('driver_id', DB::raw('COUNT(*) as cnt'))
            ->whereBetween('date_hour', [$blackHatStartH, $blackHatEndH])
            ->whereIn('driver_id', $drivers)
            ->groupBy('driver_id')
            ->havingRaw("cnt = $blackHatHour")->get()->keyBy('driver_id')->toArray();

        // 前後後一天的黑帽客任務
        $blackHatDetail = BlackhatDetail::whereRaw('1=1')
            ->with('calldriver_task_map')
            ->whereBetween('start_date', [$blackHatStartBeforeDate, $blackHatStartAfterDate])
            ->get()->toArray();


        //- 一日一人僅接收1張8H單、兩張5H，其中兩張5H的判斷為乘客是否預計會超時，
        //- 兩個五小時，若前一單有超時需求，中間需隔3小時
        //- 兩個五小時，前一單預計不超時，中間需隔 1.5小時

        $rejectDriverId = [];
        foreach($blackHatDetail as $row) {
            $_blackHatType = $row['type'];
            $_driverId = $row['calldriver_task_map']['driver_id'];
            $_blackHatTypeHour = ($_blackHatType == 1) ? 5 : 8;

            if (!isset($driverId[$_driverId])) {
                continue;
            }

            // 計算 user 當天有幾單
            // 拒絕 當天接單上限含 8 小時的司機
            $_currentDate = Carbon::parse($row['start_date'])->format('Y-m-d');
            if ($_currentDate === $blackHatCurrentDate) {
                $driverId[$_driverId]['current'] = $currentDateFetch[$_driverId]['current'] ?? 0;
                $driverId[$_driverId]['current'] += $_blackHatTypeHour;

                if ($driverId[$_driverId]['current'] >= 8) {
                    $rejectDriverId[] = $_driverId;
                    unset($driverId[$_driverId]);
                }
            }
        }

        foreach ($blackHatDetail as $row)
        {
            $_blackHatType = $row['type'];
            $_driverId = $row['calldriver_task_map']['driver_id'];
            $_blackHatTypeHour = ($_blackHatType == 1) ? 5 : 8;

            if (in_array($_driverId, $rejectDriverId)) {
                continue;
            }

            if ($row['start_date'] >= $params['start_date']) {

                $_subMinutes = $blackHatMaybeOverTime ? 3 * 60 : 1.5 * 60;
                $_startDate = Carbon::parse($row['start_date'])->subMinutes($_subMinutes)->format('Y-m-d H:i:s');
                $_endDate = Carbon::parse($row['start_date'])->addMinutes($_blackHatTypeHour * 60)->format('Y-m-d H:i:s');

            } else {

                $_addMinutes = $row['maybe_over_time'] ? 3 * 60: 1.5 * 60;
                $_addMinutes += $_blackHatTypeHour * 60;
                $_startDate = $row['start_date'];
                $_endDate = Carbon::parse($row['start_date'])->addMinutes($_addMinutes)->format('Y-m-d H:i:s');

            }

            if (!($_endDate < $blackHatStartDate || $blackHatEndDate < $_startDate)) {
                unset($driverId[$_driverId]);
            }
        }

        $driverIds = Driver::whereIn('id', array_keys($driverId))->get()->pluck('DriverID')->toArray();

        $driverId = ($driverIds) ? $driverIds[array_rand($driverIds, 1)] : null;

        return $driverId;

        // TODO
        // 派單順序是A(正職)->B(兼職)，同組同條件依據該駕駛當月黑帽客任務時數的多寡，平均駕駛執勤時數
        // Redis 序列
    }
}
