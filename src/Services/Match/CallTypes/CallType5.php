<?php


namespace Twdd\Services\Match\CallTypes;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\Driver;
use Twdd\Models\Calldriver;
use Twdd\Models\BlackhatDetail;
use Twdd\Models\DriverGroupCallCity;
use Twdd\Models\BlackhatDriverSchedule;
use Twdd\Facades\LatLonService;
use Twdd\Facades\PayService;
use Twdd\Models\TaskPayLog;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
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

        $driverID = $this->matchDriver([
            'zip' => $params['zip'],
            'black_hat_type' => $params['black_hat_type'],
            'start_date' => $params['start_date'],
            'maybe_over_time' => $params['maybe_over_time']
        ]);

        if (!$driverID) {
            return $this->error('目前無駕駛承接', null, 2001);
        }

        // 若找不到要建立單？

        $callDriver = app(DriverRepository::class)->findByDriverID($driverID, ['id']);
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
        $calldriver = $calldriverTaskMap->calldriver;
        $config = $this->getTypeConfig($blackHatDetail->type);
        $taskFee = $config['price'];
        $payParams['money'] = floor($taskFee / 2);

        $payResult = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->pay($payParams);

        if (isset($payResult['error'])) {

            $blackHatDetail->pay_status = 2; # 預約成功 刷卡失敗
            $blackHatDetail->prematch_status = 1;
            $blackHatDetail->save();
            $msg = !is_null($payResult['msg']) ? $payResult['msg'] : '系統發生錯誤';

            return $this->error($msg, null, 2002);
        } else {

            $blackHatDetail->pay_status = 1; # 預約成功 刷卡成功
            $blackHatDetail->prematch_status = 1;
            $blackHatDetail->save();

            return $this->success('付款成功', $calldriver);
        }
    }

    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []) : array
    {
        $params = parent::processParams($params, $other_params);
        $config = $this->getTypeConfig($params['black_hat_type']);
        $params['type_price'] = $config['price'];
        $params['end_date'] = Carbon::parse($params['start_date'])->addHours($config['hour']);
        // TS
        $params['TS'] = time();
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
        $this->cancelTaskState($blackhatDetail, $taskMapParams, $detailParams);
        switch ($cancelStatus) {
            case 1:
                if ($blackhatDetail->pay_status == 1) {
                    $refundRes = $this->refund($calldriverTaskMap);
                    if (!$refundRes) {
                        // Todo::退刷失敗 => 寄信通知客服
                    }
                }

                break;
            case 2:
                $fee = ($blackhatDetail->type_price) / 2;
                $this->createViolationTask($calldriverTaskMap, $fee);
                break;
        }

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
        $payQuery = PayService::callType(5)->by(2)->calldriverTaskMap($calldriverTaskMap)->query();

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
    }

    public function getTypeConfigs()
    {
        return [
            1 => [
                'title' => '5小時 鐘點代駕（尊榮黑帽客）',
                'price' => 1980,
                'hour' => 5,
            ],
            2 => [
                'title' => '8小時 鐘點代駕（尊榮黑帽客）',
                'price' => 2680,
                'hour' => 8,
            ]
        ];
    }

    public function getTypeConfig($type)
    {
        $configs = $this->getTypeConfigs();
        return $configs[$type];
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

        // 抓取縣市
        $location = LatLonService::locationFromZip($zip)->first();
        $cityId = $location->city_id;

        // 透過縣市抓取區域司機群
        $driverGroup = DriverGroupCallCity::select('drivergroup_id')->where('city_id', $cityId)->get()->pluck('drivergroup_id')->all();

        // 區域司機群
        $drivers = Driver::whereIn('driver_group_id', $driverGroup)->where('is_online', 1)->get()->pluck('id')->toArray();

        // type
        $blackHatType = $params['black_hat_type'];
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
        $blackHatDetail = BlackhatDetail::query()
            ->whereRaw('1=1')
            ->with('calldriver_task_map')
            ->where('prematch_status', 1)
            ->whereBetween('start_date', [$blackHatStartBeforeDate, $blackHatStartAfterDate])
            ->get()->toArray();

        //- 一日一人僅接收1張8H單、兩張5H，其中兩張5H的判斷為乘客是否預計會超時，
        //- 兩個五小時，若前一單有超時需求，中間需隔3小時
        //- 兩個五小時，前一單預計不超時，中間需隔 1.5小時

        $rejectDriverId = [];
        foreach($blackHatDetail as $row) {
            $_blackHatType = $row['type'];
            $_driverId = $row['calldriver_task_map']['call_driver_id'];
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
            $_driverId = $row['calldriver_task_map']['call_driver_id'];
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
