<?php


namespace Twdd\Services\Match\CallTypes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\Driver;
use Twdd\Models\BlackhatDetail;
use Twdd\Models\DriverGroupCallCity;
use Twdd\Models\BlackhatDriverSchedule;
use Twdd\Facades\LatLonService;
use Twdd\Facades\PayService;
use Twdd\Models\TaskPayLog;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\TaskRepository;
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
//        if ($this->isDuplicate($params['member_id'])) {
//            return $this->error('重複預約', null, 2002);
//        }
        $driverId = $this->matchDriver([
            'lat' => $params['lat'],
            'lon' => $params['lon'],
            'zip' => $params['zip'],
            'black_hat_type' => $params['black_hat_type'],
            'start_date' => $params['start_date'],
            'maybe_over_time' => $params['maybe_over_time']
        ]);

        if (!$driverId) {
            return $this->error('目前無駕駛承接', null, 2001);
        }

        // 若找不到要建立單？

        $callDriver = app(DriverRepository::class)->find($driverId, ['id']);
        $blackHatDetail = $this->getCalldriverServiceInstance()->setCallDriver($callDriver)->create($params);

        if(isset($blackHatDetail['error'])) {
            $msg = !is_null($blackHatDetail['msg']) ? $blackHatDetail['msg']->first() : '系統發生錯誤';
            Log::info(__CLASS__.'::'.__METHOD__.'error: ', [$blackHatDetail]);
            return $this->error($msg, $blackHatDetail);
        }

        return $this->matchPay($blackHatDetail->calldriver_task_map_id);
    }

    public function matchPay(int $calldriverTaskMapId)
    {
        $blackHatDetail = BlackhatDetail::query()->where('calldriver_task_map_id', $calldriverTaskMapId)->first();
        if (empty($blackHatDetail)) {
            return $this->error('查無此單', null, 2004);
        }
        if ($blackHatDetail->pay_status == 1) {
            return $this->error('已付款，不需再次付款', null,2003);
        }

        $calldriverTaskMap = $blackHatDetail->calldriver_task_map;
        $config = $this->getTypeConfig($blackHatDetail->type);
        $taskFee = $config['price'];
        $payParams['money'] = floor($taskFee / 2);

        $payResult = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->pay($payParams);

        if (isset($payResult['error'])) {

            $blackHatDetail->pay_status = 2; # 預約成功 刷卡失敗
            $blackHatDetail->prematch_status = 1;
            $blackHatDetail->deposit = $payParams['money'];
            $blackHatDetail->save();
            $msg = !is_null($payResult['msg']) ? $payResult['msg'] : '系統發生錯誤';

            return $this->error($msg, $calldriverTaskMap, 2002);
        } else {

            $blackHatDetail->pay_status = 1; # 預約成功 刷卡成功
            $blackHatDetail->prematch_status = 1;
            $blackHatDetail->deposit = $payParams['money'];
            $blackHatDetail->save();

            return $this->success('付款成功', $calldriverTaskMap);
        }
    }

    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []) : array
    {
        $params = parent::processParams($params, $other_params);
        $startDt = Carbon::parse($params['start_date']);
        $config = $this->getTypeConfig($params['black_hat_type']);
        $params['type_price'] = $config['price'];
        $params['end_date'] = $startDt->copy()->addHours($config['hour']);
        // TS
        $params['TS'] = $startDt->copy()->timestamp;
        $params['pay_type'] = 2;
        $params['call_type'] = 5;

        return $params;
    }

    public function cancel(int $calldriverTaskMpId, array $other_params = [])
    {
        $calldriverTaskMap = CalldriverTaskMap::where('id', $calldriverTaskMpId)->first();
        $blackhatDetail = $calldriverTaskMap->blackhat_detail;

        if (!$blackhatDetail) {
            return $this->error('沒有此預約單');
        }

        $taskMapParams = [
            'is_cancel' => 1,
            'cancel_by' => 1, // 1客人 2駕駛 3客服 4車廠
        ];
        $detailParams = [
            'prematch_status' => -1
        ];

        if (isset($other_params['cancel_status'])) {
            $cancelStatus = $other_params['cancel_status'];
        } else {
            $cancelStatus = $this->getCancelStatus($blackhatDetail->start_date);
        }

        Log::info('call_type 5 cancel:', [$cancelStatus, $calldriverTaskMap]);
        if ($cancelStatus == 3) {
            return $this->error('已過任務開始時間，無法取消任務');
        }

        switch ($cancelStatus) {
            case 1:
                if ($blackhatDetail->pay_status == 1) {
                    $refundRes = $this->refund($calldriverTaskMap);
                    if (!$refundRes) {
                        return $this->error('取消失敗');
                        // Todo::退刷失敗 => 寄信通知客服
                    }
                }

                break;
            case 2:
                $fee = $blackhatDetail->deposit;
                $res = $this->createViolationTask($calldriverTaskMap, $fee);
                if (!$res) {
                    return $this->error('取消失敗');
                }
                break;
        }
        $this->cancelTaskState($blackhatDetail, $taskMapParams, $detailParams);

        return $this->success('取消成功');
    }

//    public function isDuplicate($memberId, Carbon $startDate1, Carbon $endDate1, Carbon $startDate2, Carbon $endDate2)
//    {
//        // 黑帽客的單
//        if (!($endDate1->isBefore($startDate2) || $startDate1->isAfter($endDate2) )) {
//
//        }
//
//        return false;
//    }

    /*
     * cancelStatus: 1 => 免費取消(退50%訂金)
     * cancelStatus: 2 => 不退款(不退50%訂金)
     * cancelStatus: 3 => 不退款(額外收剩餘的50%訂金)
     */
    public function getCancelStatus($startDate)
    {
        $taskDt = Carbon::parse($startDate);
        $nowDt = Carbon::now();

        if ($nowDt->isBefore($taskDt->copy()->subHours(24))) {
            return 1;
        }

        if ($nowDt->isBefore($taskDt)) {
            return 2;
        }

        // 過了任務開始時間，無法取消
        if ($nowDt->isAfter($taskDt)) {
            return 3;
        }

        return 1;
    }

    private function cancelTaskState($blackhatDetail, $taskMapParams, $detailParams)
    {
        DB::transaction(function () use ($blackhatDetail, $taskMapParams, $detailParams) {
            BlackhatDetail::query()->where('calldriver_task_map_id', $blackhatDetail->calldriver_task_map_id)->update($detailParams);
            CalldriverTaskMap::query()->where('id', $blackhatDetail->calldriver_task_map_id)->update($taskMapParams);
        }, 3);
    }

    private function refund($calldriverTaskMap)
    {
        $payQuery = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->query();
        Log::info(__METHOD__ . 'pay_query', [$payQuery]);
        $backCreditCardResult = false;
        if (isset($payQuery['error'])) {
            $msg = $payQuery['msg'] ?? '系統發生錯誤';
            Log::error(__METHOD__ . 'payQuery:', [$msg]);
            return $this->error('系統發生錯誤');
        }

        if ($payQuery['result']['Result']['TradeStatus'] == 3) {
            $backCreditCardResult = true;
        }

        # 取消授權
        if ($payQuery['result']['Result']['TradeStatus'] == 1 && $payQuery['result']['Result']['CloseStatus'] < 2) {
            $payCancel = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->cancel();

            if (isset($payCancel['error'])) {
                $msg = $payCancel['msg'] ?? '系統發生錯誤';
                Log::error(__METHOD__ . '取消授權失敗:', [$msg]);
            } else {
                $backCreditCardResult = true;
            }
        }

        # 退刷
        if ($payQuery['result']['Result']['TradeStatus'] == 1 && $payQuery['result']['Result']['CloseStatus'] >= 2) {
            $payBack = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->back();

            if (isset($payBack['error'])) {
                $msg = $payBack['msg'] ?? '系統發生錯誤';
                Log::error(__METHOD__ . '刷退失敗:', [$msg]);
            } else {
                $backCreditCardResult = true;
            }
        }

        return $backCreditCardResult;
    }

    private function createViolationTask($map, $fee)
    {
        try {
            DB::beginTransaction();
            $calldriver = $map->calldriver;
            $parmas = [
                'type' => $calldriver->type,
                'call_type' => $calldriver->call_type,
                'pay_type' => $calldriver->pay_type,
                'TaskFee' => $fee,
                'twddFee' => round($fee * 0.2),
                'member_id' => $map->member_id,
                'driver_id' => $map->call_driver_id,
                'TaskState' => 7,
                'user_violation_id' => 1,
                'createtime' => Carbon::now(),

                'TaskRideTS' => $map->TS,
                'TaskCancelTS' => Carbon::now()->timestamp,
                'TaskStartLat' => $calldriver->lat,
                'TaskStartLon' => $calldriver->lon,
                'start_zip' => $calldriver->zip,
                'TaskEndLat' => $calldriver->lat_det,
                'TaskEndLon' => $calldriver->lon_det,
                'end_zip' => $calldriver->zip_det,
                'UserAddress' => $calldriver->addr,
                'UserAddressKey' => $calldriver->addrKey,
                'UserRemark' => $calldriver->UserRemark,
                'UserCity' => $calldriver->city,
                'UserDistrict' => $calldriver->district,
                'DestCity' => $calldriver->city_det,
                'DestDistrict' => $calldriver->district_det,
                'DestAddress' => $calldriver->addr_det,
                'DestAddressKey' => $calldriver->addrKey_det,
            ];

            $task = app(TaskRepository::class)->create($parmas);
            CalldriverTaskMap::query()->where('id', $map->id)->update(['task_id' => $task->id,]);
            TaskPayLog::query()->where('calldriver_task_map_id', $map->id)->update(['task_id' => $task->id]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getTypeConfigs()
    {
        return [
            1 => [
                'match_title' => '5小時 鐘點代駕（尊榮黑帽客）', // 媒合方案startpage使用
                'title' => '尊榮黑帽客(5小時)',
                'price' => 1980,
                'hour' => 5,
            ],
            2 => [
                'match_title' => '8小時 鐘點代駕（尊榮黑帽客）', // 媒合方案startpage使用
                'title' => '尊榮黑帽客(8小時)',
                'price' => 2680,
                'hour' => 8,
            ]
        ];
    }

    public function getTypeConfig($type)
    {
        $configs = $this->getTypeConfigs();
        return $configs[$type] ?? null;
    }

    public function rules() : array {

        return [
            'lat'                       =>  'required|numeric',
            'lon'                       =>  'required|numeric',
            'addr'                      =>  'nullable|string',
            'zip'                       =>  'nullable|string',

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
        $lat = $params['lat'];
        $lon = $params['lon'];
        // 抓取縣市
        $location = LatLonService::citydistrictFromLatlonOrZip($lat, $lon, $zip);
        $cityId = $location->city_id;

        // 透過縣市抓取區域司機群
        $driverGroup = DriverGroupCallCity::select('drivergroup_id')->where('city_id', $cityId)->get()->pluck('drivergroup_id')->all();

        // type
        $blackHatType = $params['black_hat_type'];
        // 取得所有方案
        $typeConfigs = $this->getTypeConfigs();
        $blackHatHour = $typeConfigs[$blackHatType]['hour'];

        $blackHatMaybeOverTime = $params['maybe_over_time'];
        $reserveDateStart = Carbon::parse($params['start_date']);
        $reserveDateEnd = $reserveDateStart->copy()->addHours($blackHatHour);

        // 抓取排班司機
        $scheduleRangeStart = $reserveDateStart->copy()->floorHour();
        $scheduleRangeEnd = $reserveDateStart->copy()->ceilHour()->addHours($blackHatHour);
        $driverIdGroups = BlackhatDriverSchedule::query()
            ->select('blackhat_driver_schedule.driver_id', 'blackhat_driver_group_map.blackhat_driver_group_id', DB::raw('COUNT(*) as cnt'))
            ->join('driver', 'blackhat_driver_schedule.driver_id', '=', 'driver.id')
            ->join('blackhat_driver_group_map', 'driver.id', '=', 'blackhat_driver_group_map.driver_id')
            ->whereBetween('blackhat_driver_schedule.date_hour', [$scheduleRangeStart, $scheduleRangeEnd])
            ->where('driver.is_online', 1)
            ->where('driver.is_out', 0)
            ->whereIn('driver.driver_group_id', $driverGroup)
            ->groupBy('driver_id', 'blackhat_driver_group_id')
            ->having('cnt', '>=',  $blackHatHour)
            ->get()
            ->keyBy('driver_id');
        Log::info('black_hat 有排班駕駛:', [$driverIdGroups]);
        // 當月黑帽客任務
        $blackHatDetail = BlackhatDetail::query()
            ->whereRaw('1=1')
            ->with('calldriver_task_map')
            ->where('prematch_status', 1)
            ->whereBetween('start_date', [$reserveDateStart->copy()->startOfMonth(), $reserveDateStart->copy()->endOfMonth()])
            ->get();

        //- 一日一人僅接收1張8H單、兩張5H，其中兩張5H的判斷為乘客是否預計會超時，
        //- 兩個五小時，若前一單有超時需求，中間需隔3小時
        //- 兩個五小時，前一單預計不超時，中間需隔 1.5小時
        foreach($blackHatDetail as $history) {
            $driverId = $history->calldriver_task_map->call_driver_id;
            $config = $typeConfigs[$history['type']];
            $bufferHour = $history['maybe_over_time'] ? 1.5 : 0;
            $historyReverseDate = Carbon::parse($history['start_date']);

            if (!$driverIdGroups->has($driverId)) {
                continue;
            }
            if (!isset($driverIdGroups[$driverId]['day_hour'])) {
                $driverIdGroups[$driverId]['day_hour'] = 0; // 跟預約單同一天時數加總
                $driverIdGroups[$driverId]['month_hour'] = 0; // 跟預約單同月時數加總
            }
            if ($historyReverseDate->isSameDay($reserveDateStart)) {
                $driverIdGroups[$driverId]['day_hour'] += ($config['hour'] + $bufferHour);
            }
            if ($historyReverseDate->isSameMonth($reserveDateStart)) {
                $driverIdGroups[$driverId]['month_hour'] += ($config['hour'] + $bufferHour);
            }

            // 拒絕 當天接單上限含 8 小時的司機
            if ($driverIdGroups[$driverId]['day_hour'] >= 8) {
                Log::info('black_hat 因超過8小時排除:', ['driver_id' => $driverId, 'total_hour' => $driverIdGroups[$driverId]['day_hour']]);
                $driverIdGroups->forget($driverId);
            }
        }

        foreach ($blackHatDetail as $history)
        {
            $driverId = $history['calldriver_task_map']['call_driver_id'];

            $bufferMinutes = $blackHatMaybeOverTime ? 3 * 60 : 1.5 * 60;
            $startDateWithBuffer = Carbon::parse($history['start_date'])->subMinutes($bufferMinutes);
            $endDateWithBuffer = Carbon::parse($history['end_date'])->addMinutes($bufferMinutes);
            // 時間交集 https://www.twblogs.net/a/5db28472bd9eee310d9fd37d
            if (!($endDateWithBuffer->isBefore($reserveDateStart) || $startDateWithBuffer->isAfter($reserveDateEnd) )) {
                Log::info('black_hat 預約單有交集:', [
                    'driver_id' => $driverId,
                    '預約單' => [$reserveDateStart, $reserveDateEnd],
                    '上下單' => [$startDateWithBuffer, $endDateWithBuffer],
                ]);
                $driverIdGroups->forget($driverId);
            }
        }

        if ($driverIdGroups->count() == 0) {
            return null;
        }

        // 避免同秒重複派單
        foreach ($driverIdGroups as $driverId => $driver) {
            if (Cache::has('black_hat_match_driverId_' . $driverId)) {
                $driverIdGroups->forget($driverId);
            }
        }

        if ($driverIdGroups->count() == 1) {
            $driverId = $driverIdGroups->first()->driver_id;
            Cache::put('black_hat_match_driverId_' . $driverId, $driverId, Carbon::now()->addMinute());
            return $driverIdGroups->first()->driver_id;
        }

        // 排序
        // 派單順序是A(正職)->B(兼職)，同組同條件依據該駕駛當月黑帽客任務時數的多寡
        Log::info('black_hat名單清單(未排序)', [$driverIdGroups]);
        $matchDriver = null;
        foreach ($driverIdGroups as $driver) {
            if (empty($matchDriver)) {
                $matchDriver = $driver;
                continue;
            }

            if ($driver->blackhat_driver_group_id < $matchDriver->blackhat_driver_group_id) {
                $matchDriver = $driver;
                continue;
            }

            if ($driver->blackhat_driver_group_id == $matchDriver->blackhat_driver_group_id
                && $driver->month_hour < $matchDriver->month_hour) {
                $matchDriver = $driver;
                continue;
            }
        }

        Cache::put('black_hat_match_driverId_' . $matchDriver->driver_id, $matchDriver->driver_id, Carbon::now()->addMinute());

        return $matchDriver->driver_id;
    }
}
