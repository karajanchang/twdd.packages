<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-13
 * Time: 14:00
 */

namespace Twdd\Services\Task;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Errors\TaskErrors;
use Twdd\Facades\LatLonService;
use Twdd\Models\Member;
use Twdd\Repositories\CalldriverRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\BlackHatDetailRepository;
use Twdd\Repositories\MemberPayTokenRepository;
use Twdd\Repositories\TaskHabitRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;
use Twdd\Models\Calldriver;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Twdd\Repositories\EnterpriseStaffRepository;

class CalldriverService extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $call_member = null;
    private $call_driver = null;
    private $members = [];
    private $mapRepository;
    private $taskHabitRepository;
    private $blackhatDetailRepository;
    private $user = null;
    private $enterpriseStaffRepository;

    public function __construct(
        TaskErrors $taskErrors,
        CalldriverRepository $repository,
        CalldriverTaskMapRepository $mapRepository,
        BlackHatDetailRepository $blackhatDetailRepository,
        TaskHabitRepository $taskHabitRepository,
        EnterpriseStaffRepository $enterpriseStaffRepository
    )
    {
        $this->repository = $repository;
        $this->error = $taskErrors;
        $this->mapRepository = $mapRepository;
        $this->taskHabitRepository = $taskHabitRepository;
        $this->blackhatDetailRepository = $blackhatDetailRepository;
        $this->enterpriseStaffRepository = $enterpriseStaffRepository;
    }

    public function checkIfDuplicate()
    {
        $res = $this->checkIfHaveCallMember();
        if ($res !== true) {

            return $res;
        }

        $call_member = $this->getCallMember();
        $res = $this->checkDuplicateByMember($call_member);
        if ($res !== true) {

            return $res;
        }

        return true;
    }

    private function checkDuplicateByMember($member)
    {
        if ($this->mapRepository->numsOfDuplcateByMember($member) > 0) {
            $this->error->setReplaces('seconds', $this->calucateLastSeconds());

            return $this->error['1005'];
        }
        return true;
    }

    private function checkIfHaveCallMember()
    {
        $call_member = $this->getCallMember();
        if (is_null($call_member)) {

            return $this->error['1004'];
        }

        return true;
    }

    /**
     * @return null
     */
    public function getCallMember()
    {
        return $this->call_member;
    }

    /**
     * @param null $call_member
     */
    public function setCallMember(Member $call_member): CalldriverService
    {
        $this->call_member = $call_member;

        return $this;
    }


    public function currentCall(int $calldriver_id)
    {
        $call = $this->mapRepository->currentCall($calldriver_id);

        $driver = InitalObject::parseDriverFromCall($call);

        $task = InitalObject::parseTaskFromCall($call);

        return [
            'calldriver_id' => $call->calldriver_id,
            'member_id' => $call->member_id,
            'DriverID' => $driver->DriverID,
            'DriverName' => $driver->DriverName,
            'DriverPhoto' => $driver->DriverPhoto,
            'DriverPhone' => $driver->DriverPhone,
            'DriverRating' => $driver->DriverRating,
            'DriverDrivingYear' => $driver->DriverDrivingYear,
            'DriverServiceTime' => $driver->DriverServiceTime,
            'TaskNo' => TaskNo::make($task->id),
            'TaskState' => $task->TaskState,
            'address' => isset($call->addr) ? $call->addr : null,
            'addressKey' => isset($call->addrKey) ? $call->addr : null,
            'UserCreditCode' => isset($call->UserCreditCode) ? $call->UserCreditCode : null,
            'UserCreditValue' => isset($call->UserCreditValue) ? $call->UserCreditValue : null,
            'address_det' => isset($call->addr_det) ? $call->addr_det : null,
            'addressKey_det' => isset($call->addrKey_det) ? $call->addrKey_det : null,
            'UserLatlon' => InitalObject::parseLatLonFromCall($call),
            'DriverLatlon' => InitalObject::parseLatLonFromCall($call),
            'IsMatchFail' => isset($call->IsMatchFail) ? $call->IsMatchFail : null,
        ];
    }

    private function calucateLastSeconds()
    {
        $key = 'CALLDRIVER_CHECK_INITAL' . $this->getCallMember()->id;
        $seconds = env('CALLDRIVER_CHECK_SECONDS', 60);
        if (!Cache::has($key)) {
            Cache::put($key, time(), Carbon::now()->addSeconds($seconds));
        } else {
            $time = Cache::get($key);
            $seconds = $seconds - (time() - $time);
        }

        return $seconds;
    }


    public function create(array $params, bool $do_not_create_map = false)
    {
        //---檢查會員是否有效
        $res = $this->checkIfHaveCallMember();
        if ($res !== true) {

            return $res;
        }
        /*
        if($this->checkIfDuplicate()!==true){
            return $this->error['1005'];
        }*/

        $error = $this->validate($params);
        if ($error !== true) {

            return $error;
        }

        // 檢查會員是否為企業員工
        $staff = $this->enterpriseStaffRepository->checkMemberIsStaffByID($this->call_member->id);
        if($staff && $staff->enterprise_id){
            $params['enterprise_id'] = $staff->enterprise_id;
        }

        //---lat lon 代0時要 地址轉latlon
        $this->ifMmemberIsNullThenEqalCallMember();

        if (empty($params['city']) || empty($params['district'])) {
            if (isset($params['zip'])) {
                $cityDistricts = LatLonService::locationFromZip($params['zip']);
                if (isset($cityDistricts) && count($cityDistricts)) {
                    $cityDistrict = $cityDistricts->first();
                    $params['city'] = $cityDistrict->city;
                    $params['district'] = $cityDistrict->district;
                }
            }
        }

        if (empty($params['city_det']) || empty($params['district_det'])) {
            if (isset($params['zip_det'])) {
                $cityDistricts_det = LatLonService::locationFromZip($params['zip_det']);
                if (isset($cityDistricts_det) && count($cityDistricts_det)) {
                    $cityDistrict_det = $cityDistricts_det->first();
                    $params['city_det'] = $cityDistrict_det->city;
                    $params['district_det'] = $cityDistrict_det->district;
                }
            }
        }
        Log::info('CalldriverService create params: ', $params);

        try {
            $callType = $params['call_type'] ?? 0;
            $originParams = $params;
            $params = $this->filter($params);
            $calldriver = $this->repository->create($params);

            $this->insertMemberPayToken($calldriver, $params);

            if ($do_not_create_map === false) {
                $calldriverTaskMaps = $this->insertMap($calldriver, $params);

                // 黑帽客建單明細
                if ($callType == 5) {
                    $calldriver = $this->insertBlackHatDetail($calldriverTaskMaps, $originParams);
                }

                foreach ($calldriverTaskMaps as $calldriverTaskMap) {
                    $this->insertTaskHabits($calldriverTaskMap->id, $params);
                }
            }

            return $calldriver;
        } catch (\Exception $e) {
            Bugsnag::notifyException($e);
            $msg = env('APP_DEBUG') === true ? $e->getMessage() : null;
            Log::error('twdd CalldriverService error' . $msg, [$e]);

            return $this->error->_('500');
        }

    }

    /*
     * 塞入apple pay / line pay 或其他付款方式的token
     */
    private function insertMemberPayToken(Calldriver $calldriver, array $params = [])
    {
        $members = $this->getMembers();
        Log::info(__CLASS__ . '::' . __METHOD__ . ' members: ', [$members]);
        if (!isset($params['pay_token']) || empty($params['pay_token'])) return;

        $res = false;
        if (count($members)) {
            foreach ($this->members as $member) {
                if (isset($member->id) && !empty($member->id)) {
                    $res = app(MemberPayTokenRepository::class)->createByMemberId($member->id, $params['pay_token'], $params['pay_type']);
                }
                break;
            }
        }

        return $res;
    }

    private function insertMap(Calldriver $calldriver, array $params)
    {
        $paras = $this->filterMap($calldriver, $params);
        $calldriverTaskMaps = [];
        foreach ($paras as $para) {
            $calldriverTaskMaps[] = $this->mapRepository->create($para);
        }

        return $calldriverTaskMaps;
    }

    private function insertBlackHatDetail(array $calldriverTaskMaps, array $params)
    {
        $nowDt = Carbon::now();
        $calldriverTaskMap = $calldriverTaskMaps[0];

        $data = [
            'calldriver_task_map_id' => $calldriverTaskMap->id,
            'type' => $params['black_hat_type'],
            'type_price' => $params['type_price'],
            'start_date' => $params['start_date'],
            'end_date' => $params['end_date'],
            'maybe_over_time' => $params['maybe_over_time'],
            'tax_id_number' => $params['tax_id_number'] ?? "",
            'tax_id_title' => $params['tax_id_title'] ?? "",
            'created_at' => $nowDt,
            'updated_at' => $nowDt,
            'pay_status' => $params['pay_status'] ?? "",
            'prematch_status' => $params['prematch_status'] ?? "",
        ];
        Log::info('create detail data', $data);
        return $this->blackhatDetailRepository->create($data);
    }


    private function filter(array $params){
        $call_member_id = $this->determineCallMemberId();
        $members = $this->getMembers();
        if(!is_null($call_member_id)){
            $params['call_type'] = 3;
        }
        $params['call_member_id'] = $call_member_id;

        $params['call_driver_id'] = null;
        if(isset($this->call_driver->id) && !empty($this->call_driver->id)){
            $params['call_driver_id'] = $this->call_driver->id;
        }else{
            $params['call_driver_id'] = !empty($params['call_driver_id']) ? $params['call_driver_id'] : null;
        }

        $params['createtime'] = date('Y-m-d H:i:s');
        $params['IsMatch'] = 0;
        $params['IsByUserKeyin'] = 0;
        $params['IsDelete'] = 0;
        $params['tyear'] = date('Y');
        $params['tmonth'] = date('n');
        $params['tday'] = date('j');
        $params['IsApi'] = isset($params['IsApi']) ? (int) $params['IsApi'] : 0;
        $params['people'] = count($members);
        $params['extra_price'] = isset($params['extra_price']) ? (int) $params['extra_price'] : 0;
        $params['is_push'] = 0;
        $params['is_receive_money_first'] = isset($params['is_receive_money_first']) ? (int) $params['is_receive_money_first'] : 0;
        if(isset($this->user->id) && !empty($this->user->id)){
            $params['user_id'] = $this->user->id;
        }else{
            $params['user_id'] = !empty($params['user_id']) ? $params['user_id'] : null;
        }
        $params['DeviceTypeMember'] = isset($params['DeviceType']) ? $params['DeviceType'] : null;
        $params['AppVerMember'] = isset($params['AppVer']) ? $params['AppVer'] : null;
        $params['OSVerMember'] = isset($params['OSVer']) ? $params['OSVer'] : null;
        $params['DeviceModelMember'] = isset($params['DeviceModel']) ? $params['DeviceModel'] : null;

        Log::info(__CLASS__.'::'.__METHOD__.': ', $params);

        return $params;
    }

    private function filterMap(Calldriver $calldriver, array $params){
        $paras = [];
        $members = $this->getMembers();
        $call_member_id = isset($params['call_member_id']) ? $params['call_member_id'] : 0;
        foreach($members as $member) {
            $pp = [
                'member_id' => $member->id,
                'call_member_id' => $call_member_id,
                'calldriver_id' => $calldriver->id,
                'is_done' => 0,
                'is_cancel' => 0,
                'call_type' => $params['call_type'],
                'TS' => $params['TS'],
                'MatchTimes' => 0,
                'IsMatchFail' => 0,
                'is_push' => 0,
                'call_driver_id' => $params['call_driver_id'],
                'is_match_female_driver' => $params['match_female_driver'] ?? 0,
                'call_by_driver_id' => $params['call_by_driver_id'] ?? null,
            ];
            array_push($paras, $pp);
        }

        return $paras;
    }

    private function insertTaskHabits(int $calldriverTaskMapId, array $params)
    {
        if (!isset($params['habit_ids']) || empty($params['habit_ids'])) {
            return ;
        }
        $dtNow = Carbon::now();
        $insertParams = [];
        $defaultParams = [
            'calldriver_task_map_id' => $calldriverTaskMapId,
            'created_at' => $dtNow,
            'updated_at' => $dtNow,
        ];
        foreach ($params['habit_ids'] as $habitId) {
            $defaultParams['habit_id'] = $habitId;
            $insertParams[] = $defaultParams;
        }

        $this->taskHabitRepository->insert($insertParams);
    }

    /**
     * @return mixed
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param array $members
     */
    public function setMembers(array $members): void
    {
        $this->members = $members;
    }

    private function ifMmemberIsNullThenEqalCallMember():void{
        $members = $this->getMembers();
        if(count($members)==0){
            $this->addMember($this->getCallMember());
        }
    }

    /**
     * @param mixed $member
     */
    public function addMember(Member $member): CalldriverService
    {
        if(!array_key_exists($member->id, $this->members)) {
            $this->members[$member->id] = $member;
        }

        return $this;
    }


    public function user($user = null){
        $this->user = $user;

        return $this;
    }

    /**
     * @return null
     */
    public function getCallDriver()
    {
        return $this->call_driver;
    }

    /**
     * @param null $call_driver
     */
    public function setCallDriver(Model $call_driver = null): CalldriverService
    {
        $this->call_driver = $call_driver;

        return $this;
    }



    public function rules(){

        return [
            'lat' => 'required',
            'lon' => 'required',
            'zip' => 'required|integer',
            'addr' => 'required|string',
            'addrKey' => 'required|string',
            'type' => 'required|integer',
            'call_type' => 'required|integer',
            'pay_type' => 'required|integer',
            'zip_det' => 'nullable|integer',
        ];
    }

    /**
     * @return mixed
     */
    private function determineCallMemberId()
    {
        $call_member = $this->getCallMember();
        $call_member_id = $call_member->id;
        $members = $this->getMembers();
        if (count($members) == 1) {
            $member = array_pop($members);
            if ($member->id == $call_member->id) {
                $call_member_id = null;
            }
        }

        return $call_member_id;
    }
}
