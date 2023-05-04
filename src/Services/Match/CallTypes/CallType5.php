<?php


namespace Twdd\Services\Match\CallTypes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Jyun\Mapsapi\TwddMap\Directions;
use Twdd\Facades\PushNotification;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\Member;
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
use Twdd\Services\PushNotificationService;
use Twdd\Jobs\Invoice\InvoiceInvalidJob;
use Twdd\Jobs\Blackhat\BlackhatReserveMailJob;

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
    public $title = '鐘點代駕預約';


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
        //--預約一定要用信用卡
        $res = $this->noCheckList('CheckHaveBindCreditCard');
        if ($params['pay_type'] == 2 && $res !== false && $this->CheckHaveBindCreditCard() !== true) {

            return $this->{$res}('預約代駕付款方式限定信用卡');
        }

        if ($check === true) {
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
        $params = $this->processParams($this->params, $other_params);
        //        if ($this->isDuplicate($params['member_id'])) {
        //            return $this->error('重複預約', null, 2002);
        //        }

        $driverIdByAdmin = $params['driverId_by_admin'] ?? null;

        if ($driverIdByAdmin) {
            $driverId = $driverIdByAdmin;
        } else {

            $driverId = $this->matchDriver([
                'lat' => $params['lat'],
                'lon' => $params['lon'],
                'zip' => $params['zip'],
                'black_hat_type' => $params['black_hat_type'],
                'start_date' => $params['start_date'],
                'maybe_over_time' => $params['maybe_over_time']
            ]);
        }

        // 無匹配的駕駛，建立空白單
        if (!$driverId) {
            $callDriver = app(DriverRepository::class)->first();
            $callDriver->id = 0;

            $this->member = Member::find($this->member->id);

            //企業後台的鐘點代駕會預設為1, 沒有匹配駕駛時需要修改為0
            $params['prematch_status'] = 0;
            $blackHatDetail = $this->getCalldriverServiceInstance()->setCallDriver($callDriver)->create($params);
            $calldriverTaskMap = $blackHatDetail->calldriver_task_map;
            $calldriverTaskMap->isMatchFail = 1;
            $calldriverTaskMap->save();
            return $this->error('目前無駕駛承接', null, 2001);
        }

        if ($params['type'] == 2) {
            $this->member = Member::find($this->member->id);
        }

        $callDriver = app(DriverRepository::class)->find($driverId, ['id']);

        $blackHatDetail = $this->getCalldriverServiceInstance()->setCallDriver($callDriver)->create($params);

        if (isset($blackHatDetail['error'])) {
            $msg = !is_null($blackHatDetail['msg']) ? $blackHatDetail['msg']->first() : '系統發生錯誤';
            Log::info(__CLASS__ . '::' . __METHOD__ . 'error: ', [$blackHatDetail]);
            return $this->error($msg, $blackHatDetail);
        }

        if ($params['pay_type'] == 3) {
            dispatch(new BlackhatReserveMailJob([
                'status' => 1,
                'driver' => $driverId,
                'calldriverTaskMap' => $blackHatDetail->calldriver_task_map,
                'email' => $blackHatDetail->calldriver_task_map->member->UserEmail,
            ]));
            return $this->success('預約成功', $blackHatDetail->calldriver_task_map_id);
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
            return $this->error('已付款，不需再次付款', null, 2003);
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

            dispatch(new BlackhatReserveMailJob([
                'status' => 1,
                'driver' => $calldriverTaskMap->call_driver_id,
                'calldriverTaskMap' => $calldriverTaskMap,
                'email' => $calldriverTaskMap->member->UserEmail,
            ]));

            return $this->success('付款成功', $calldriverTaskMap);
        }
    }

    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []): array
    {
        $params = parent::processParams($params, $other_params);
        $startDt = Carbon::parse($params['start_date']);
        $config = $this->getTypeConfig($params['black_hat_type']);
        $params['type_price'] = $config['price'];
        $params['end_date'] = $startDt->copy()->addHours($config['hour']);
        // TS
        $params['TS'] = $startDt->copy()->timestamp;
        $params['pay_type'] = $params['pay_type'] ?? 2;
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
        if ($calldriverTaskMap->is_cancel == 1) {
            return $this->success('取消成功');
        }

        $taskMapParams = [
            'is_cancel' => 1,
            'cancel_by' => $other_params['cancel_by'] ?? 1, // 1客人 2駕駛 3客服 4車廠
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

        $pushService = new PushNotificationService();

        switch ($cancelStatus) {
            case 1:
                //企業簽單無收取訂金, 直接修改狀態
                if ($calldriverTaskMap->calldriver->pay_type == 3){
                    $detailParams['pay_status'] = 2;
                    break;
                }

                if ($blackhatDetail->pay_status == 1) {
                    $refundRes = $this->refund($calldriverTaskMap);
                    if (!$refundRes) {
                        return $this->error('取消失敗');
                        // Todo::退刷失敗 => 寄信通知客服
                    }
                    $detailParams['pay_status'] = 3;
                }
                break;
            case 2:
                $fee = $blackhatDetail->deposit ?: $blackhatDetail->type_price / 2;
                $res = $this->createViolationTask($calldriverTaskMap, $fee);
                if (!$res) {
                    return $this->error('取消失敗');
                }
                $detailParams['pay_status'] = 4;
                break;
        }
        $this->cancelTaskState($blackhatDetail, $taskMapParams, $detailParams);

        // 有退款的情境
        if ($cancelStatus == 1 && $blackhatDetail->pay_status == 1) {
            $memberBody = sprintf('鐘點代駕任務%s訂金已成功退款，敬請您留意，謝謝！', $calldriverTaskMap->id);
            $pushService->push([$calldriverTaskMap->member_id], '預約成功退款通知', $memberBody, 'reserves');
        } else {
            $memberBody = sprintf('鐘點代駕任務%s取消成功，很可惜無法為您服務，如有需求請重新預約。', $calldriverTaskMap->id);
            $pushService->push([$calldriverTaskMap->member_id], '預約取消通知', $memberBody, 'reserves');
        }

        $this->sendingCancelMail($calldriverTaskMap, $blackhatDetail);
        $driverBody = sprintf('鐘點代駕任務%s已取消，敬請留意，辛苦了！', $calldriverTaskMap->id);
        $pushService->push2Driver([$calldriverTaskMap->call_driver_id], '預約取消通知', $driverBody);

        return $this->success('取消成功');
    }

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
        // 先取消授權失敗再刷退
        $payCancel = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->cancel();
        if (isset($payCancel['error'])) {
            $msg = $payCancel['msg'] ?? '系統發生錯誤';
            Log::info('calldriver_task_map:' . $calldriverTaskMap . '取消授權失敗:', [$msg]);

            $payBack = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->back(0); // Back func 實際扣款是在 Payments/CallType5.php

            if (isset($payBack['error'])) {
                Log::info('calldriver_task_map:' . $calldriverTaskMap . '刷退失敗:', [$msg]);
                return false;
            }
        }

        //刷退成功需要作廢發票
        dispatch(new InvoiceInvalidJob([
            "type"=>"B2C",
            "model"=> $calldriverTaskMap
        ]));

        return true;
        /*
        // 由於藍新測試機Query 後給予的 CloseStatus = 0，但取消授權失敗，不準確，所以改以取消授權失敗後打退款
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
        /*
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
        */
    }

    private function createViolationTask($map, $fee)
    {
        try {
            if (!empty($map->calldriver->zip)) {
                $cityDistricts = LatLonService::locationFromZip($map->calldriver->zip);
                $cityDistrict = $cityDistricts->first() ?? null;
            }
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
                'start_city_id' => $cityDistrict->city_id ?? null,
                'start_district_id' => $cityDistrict->district_id ?? null,
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
                'match_title' => '5小時 鐘點代駕', // 媒合方案startpage使用
                'title' => '鐘點代駕(5小時)',
                'price' => 2300,
                'hour' => 5,
            ],
            2 => [
                'match_title' => '8小時 鐘點代駕', // 媒合方案startpage使用
                'title' => '鐘點代駕(8小時)',
                'price' => 3100,
                'hour' => 8,
            ]
        ];
    }

    public function getTypeConfig($type)
    {
        $configs = $this->getTypeConfigs();
        return $configs[$type] ?? null;
    }

    public function rules(): array
    {

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

    public function matchDriver($params, array $refuseDriverIds = [0])
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

        $reserveMaybeOverTime = $params['maybe_over_time'] ?? 0;
        $reserveStartDt = Carbon::parse($params['start_date']);
        $reserveEndDt = $reserveStartDt->copy()->addHours($blackHatHour);

        // 抓取排班司機
        $scheduleRangeStart = $reserveStartDt->copy()->floorHour();
        $scheduleRangeEnd = $reserveStartDt->copy()->ceilHour()->addHours($blackHatHour);
        $driverIdGroups = BlackhatDriverSchedule::query()
            ->select('blackhat_driver_schedule.driver_id', 'blackhat_driver_group_map.blackhat_driver_group_id', DB::raw('COUNT(*) as cnt'))
            ->join('driver', 'blackhat_driver_schedule.driver_id', '=', 'driver.id')
            ->join('blackhat_driver_group_map', 'driver.id', '=', 'blackhat_driver_group_map.driver_id')
            ->whereBetween('blackhat_driver_schedule.date_hour', [$scheduleRangeStart, $scheduleRangeEnd])
            ->where('driver.is_online', 1)
            ->where('driver.is_out', 0)
            ->whereIn('driver.driver_group_id', $driverGroup)
            ->whereNotIn('blackhat_driver_schedule.driver_id', $refuseDriverIds)
            ->groupBy('blackhat_driver_schedule.driver_id', 'blackhat_driver_group_id')
            ->having('cnt', '>=', $blackHatHour)
            ->get()
            ->keyBy('driver_id');

        Log::info('black_hat 有排班駕駛:', [$driverIdGroups]);
        // 當月黑帽客任務
        $blackHatDetail = BlackhatDetail::query()
            ->whereRaw('1=1')
            ->with('calldriver_task_map')
            ->where('prematch_status', 1)
            ->whereBetween('start_date', [$reserveStartDt->copy()->startOfMonth(), $reserveStartDt->copy()->endOfMonth()])
            ->get();

        //- 一日一人僅接收1張8H單、兩張5H，其中兩張5H的判斷為乘客是否預計會超時，
        //- 兩個五小時，若前一單有超時需求，中間需隔3小時
        //- 兩個五小時，前一單預計不超時，中間需隔 1.5小時
        foreach ($blackHatDetail as $history) {
            $driverId = $history->calldriver_task_map->call_driver_id;
            $config = $typeConfigs[$history['type']];
            $overtimeHour = $history['maybe_over_time'] ? 1.5 : 0;
            $historyReverseDate = Carbon::parse($history['start_date']);

            if (!$driverIdGroups->has($driverId)) {
                continue;
            }
            if (!isset($driverIdGroups[$driverId]['day_hour'])) {
                $driverIdGroups[$driverId]['day_hour'] = 0; // 跟預約單同一天時數加總
                $driverIdGroups[$driverId]['month_hour'] = 0; // 跟預約單同月時數加總
                $driverIdGroups[$driverId]['day_overtime_nums'] = 0; // 跟預約單同一天超時單量 // 需求只能有一張超時單
            }
            if ($historyReverseDate->isSameDay($reserveStartDt)) {
                $driverIdGroups[$driverId]['day_hour'] += ($config['hour'] + $overtimeHour);
                if ($history['maybe_over_time']) {
                    $driverIdGroups[$driverId]['day_overtime_nums'] += 1;
                }
            }
            if ($historyReverseDate->isSameMonth($reserveStartDt)) {
                $driverIdGroups[$driverId]['month_hour'] += ($config['hour'] + $overtimeHour);
            }

            // 拒絕 當天接單上限含 8 小時的司機
            if ($driverIdGroups[$driverId]['day_hour'] >= 8) {
                Log::info(
                    'black_hat 因超過10小時排除:',
                    ['driver_id' => $driverId, 'total_hour' => $driverIdGroups[$driverId]['day_hour']]
                );
                $driverIdGroups->forget($driverId);
                continue;
            }

            // 需求只能有一張超時單
            if ($reserveMaybeOverTime && $driverIdGroups[$driverId]['day_overtime_nums'] > 0) {
                Log::info(
                    'black_hat 已有一張超時單不可再接第二張超時:',
                    ['driver_id' => $driverId, 'day_overtime_nums' => $driverIdGroups[$driverId]['day_overtime_nums']]
                );
                $driverIdGroups->forget($driverId);
                continue;
            }
        }

        $bufferMinutes = 60;
        foreach ($blackHatDetail as $history) {
            $driverId = $history['calldriver_task_map']['call_driver_id'];

            $historyStartDt = Carbon::parse($history['start_date']);
            $historyEndDt = Carbon::parse($history['end_date']);
            $reserveWithBufferEndDt = $reserveEndDt->copy();
            $historyWithBufferEndDt = $historyEndDt->copy();
            // 當前預約單在已預約的黑帽客之前，已預約黑帽客結束時間+是否會超時+buffer時間
            if ($historyStartDt->isBefore($reserveStartDt)) {
                if ($history['maybe_over_time']) {
                    $historyWithBufferEndDt->addHours(3);
                } else {
                    $historyWithBufferEndDt->addMinutes(90);
                }
                $historyWithBufferEndDt->addMinutes($bufferMinutes);
            } else {
                if ($reserveMaybeOverTime) {
                    $reserveWithBufferEndDt->addHours(3);
                } else {
                    $reserveWithBufferEndDt->addMinutes(90);
                }
                $reserveWithBufferEndDt->addMinutes($bufferMinutes);
            }

            if ($this->isDtOverLap($reserveStartDt, $reserveEndDt, $historyStartDt, $historyWithBufferEndDt)) {
                Log::info('black_hat 預約單有交集:', [
                    'driver_id' => $driverId,
                    '預約單' => [$reserveStartDt, $reserveEndDt],
                    '上下單' => [$historyStartDt, $historyWithBufferEndDt],
                ]);
                $driverIdGroups->forget($driverId);
            }
        }

        // 排除車廠與黑帽客單重疊時間
        $carFactories = CalldriverTaskMap::query()
            ->leftJoin('calldriver', 'calldriver.id', '=', 'calldriver_task_map.calldriver_id')
            ->where('calldriver.type', 10)
            ->where('calldriver_task_map.call_type', 2)
            ->where('calldriver_task_map.is_cancel', 0)
            ->where('calldriver_task_map.is_done', 0)
            ->where('calldriver_task_map.IsMatchFail', 0)
            ->whereIn('calldriver_task_map.call_driver_id', $driverIdGroups->keys())
            ->whereBetween('calldriver_task_map.TS', [
                // +- 5h 避免沒有重疊，但因buffer會重疊
                $reserveStartDt->copy()->subHours(5)->copy()->timestamp,
                $reserveEndDt->copy()->addHours(5)->copy()->timestamp
            ])
            ->get();
        foreach ($carFactories as $carFactory) {
            $driverId = $carFactory->call_driver_id;
            $carFactoryStartDt = Carbon::createFromTimestamp($carFactory->TS);
            $carFactoryEndDt = $carFactoryStartDt->copy()->addSeconds($carFactory->duration);

            // 計算路程
            $twoPlaceDistance = Directions::directions(
                ($lat . ',' . $lon),
                ($carFactory->lat . ',' . $carFactory->lon),
                'bicycling'
            );
            $duration = $twoPlaceDistance['data']['routes']['legs']['duration'] ?? 0;

            // 黑帽客(預約單) -> 車廠 => 路程 + 可能超時(+3h)|(+1.5h)
            if ($reserveStartDt->isBefore($carFactoryStartDt)) {
                $reserveWithBufferDateEnd = $reserveEndDt->copy()->addMinutes($bufferMinutes)->addSeconds($duration);
                if ($this->isDtOverLap($reserveStartDt, $reserveWithBufferDateEnd, $carFactoryStartDt, $carFactoryEndDt)) {
                    Log::info('黑帽客to車廠單時間重疊:', [
                        'driver_id' => $driverId,
                        '預約單' => [
                            '開始時間' => $reserveStartDt,
                            '結束時間' => $reserveEndDt,
                            '結束時間+Buffer' => $reserveWithBufferDateEnd,
                        ],
                        '車廠單' => [
                            '開始時間' => $carFactoryStartDt,
                            '結束時間' => $carFactoryEndDt,
                        ],
                        'buffer' => [$bufferMinutes . '(m)+' . $duration . '(s)']
                    ]);
                    $driverIdGroups->forget($driverId);
                }
            } else {
                // 車廠 -> 黑帽客(預約單)  => 路程 + buffer(1h)
                $carFactoryWithBufferEndDt = $carFactoryEndDt->copy()->addHour()->addSeconds($duration);
                if ($this->isDtOverLap($reserveStartDt, $reserveEndDt, $carFactoryStartDt, $carFactoryWithBufferEndDt)) {
                    Log::info('車廠to黑帽客重疊:', [
                        'driver_id' => $driverId,
                        '車廠單' => [
                            '開始時間' => $carFactoryStartDt,
                            '結束時間' => $carFactoryEndDt,
                            '結束時間+Buffer(1h)+路程' => $carFactoryWithBufferEndDt
                        ],
                        '預約單' => [
                            '開始時間' => $reserveStartDt,
                            '結束時間' => $reserveEndDt,
                        ],
                        '路程' => [$duration . '(s)'],
                    ]);
                    $driverIdGroups->forget($driverId);
                }
            }
        }

        // 避免同秒重複派單
        foreach ($driverIdGroups as $driverId => $driver) {
            if (Cache::has('black_hat_match_driverId_' . $driverId)) {
                $driverIdGroups->forget($driverId);
            }
        }

        if ($driverIdGroups->count() == 0) {
            return null;
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

            if (
                $driver->blackhat_driver_group_id == $matchDriver->blackhat_driver_group_id
                && $driver->month_hour < $matchDriver->month_hour
            ) {
                $matchDriver = $driver;
                continue;
            }
        }

        Cache::put('black_hat_match_driverId_' . $matchDriver->driver_id, $matchDriver->driver_id, Carbon::now()->addMinute());

        return $matchDriver->driver_id;
    }

    // 時間交集 https://www.twblogs.net/a/5db28472bd9eee310d9fd37d
    private function isDtOverLap($startDt1, $endDt1, $startDt2, $endDt2): bool
    {
        if (!($endDt1->isBefore($startDt2) || $startDt1->isAfter($endDt2))) {
            return true;
        }
        return false;
    }

    private function sendingCancelMail($calldriverTaskMap, $blackhatDetail)
    {
        if ($blackhatDetail->pay_status == 2) {
            $status = 3;
        } else {
            $status = 2;
        }

        dispatch(new BlackhatReserveMailJob([
            'status' => $status,
            'driver' => $calldriverTaskMap->call_driver_id,
            'calldriverTaskMap' => $calldriverTaskMap,
            'email' => $calldriverTaskMap->member->UserEmail,
        ]));

    }
}
