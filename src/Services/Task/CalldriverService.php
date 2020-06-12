<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-13
 * Time: 14:00
 */

namespace Twdd\Services\Task;


use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Errors\TaskErrors;
use Twdd\Facades\LatLonService;
use Twdd\Models\Member;
use Twdd\Repositories\CalldriverRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;
use Twdd\Models\Calldriver;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

class CalldriverService extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $call_member = null;
    private $call_driver = null;
    private $members = [];
    private $mapRepository;
    private $user = null;

    public function __construct(CalldriverRepository $repository, TaskErrors $taskErrors, CalldriverTaskMapRepository $mapRepository)
    {
        $this->repository = $repository;
        $this->error = $taskErrors;
        $this->mapRepository = $mapRepository;
    }

    public function checkIfDuplicate(){
        $res = $this->checkIfHaveCallMember();
        if($res!==true){

            return $res;
        }

        $call_member = $this->getCallMember();
        $res = $this->checkDuplicateByMember($call_member);
        if($res!==true){

            return $res;
        }

        return true;
    }

    private function checkDuplicateByMember($member){
        if($this->mapRepository->numsOfDuplcateByMember($member)>0){
            $this->error->setReplaces('seconds', $this->calucateLastSeconds());

            return $this->error['1005'];
        }
        return true;
    }

    private function checkIfHaveCallMember(){
        $call_member = $this->getCallMember();
        if(is_null($call_member)){

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


    public function currentCall(int $calldriver_id){
        $call = $this->mapRepository->currentCall($calldriver_id);

        $driver = InitalObject::parseDriverFromCall($call);

        $task = InitalObject::parseTaskFromCall($call);

        return [
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

    private function calucateLastSeconds(){
        $key = 'CALLDRIVER_CHECK_INITAL'.$this->getCallMember()->id;
        $seconds = env('CALLDRIVER_CHECK_SECONDS', 60);
        if(!Cache::has($key)){
            Cache::put($key, time(), Carbon::now()->addSeconds($seconds));
        }else{
            $time = Cache::get($key);
            $seconds = $seconds - (time() - $time);
        }

        return $seconds;
    }


    public function create(array $params, bool $do_not_create_map = false){
        //---檢查會員是否有效
        $res = $this->checkIfHaveCallMember();
        if($res!==true){

            return $res;
        }
        /*
        if($this->checkIfDuplicate()!==true){
            return $this->error['1005'];
        }*/

        $error = $this->validate($params);
        if($error!==true){

            return $error;
        }

        //---lat lon 代0時要 地址轉latlon
        $this->ifMmemberIsNullThenEqalCallMember();

        if(empty($params['city']) || empty($params['district'])) {
            if (isset($params['zip'])) {
                $cityDistricts = LatLonService::locationFromZip($params['zip']);
                if (isset($cityDistricts) && count($cityDistricts)) {
                    $cityDistrict = $cityDistricts->first();
                    $params['city'] = $cityDistrict->city;
                    $params['district'] = $cityDistrict->district;
                }
            }
        }

        if(empty($params['city_det']) || empty($params['district_det'])) {
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
            $params = $this->filter($params);
            $calldriver = $this->repository->create($params);

            if($do_not_create_map===false) {
                $this->insertMap($calldriver, $params);
            }

            return $calldriver;
        }catch(\Exception $e){
            Bugsnag::notifyException($e);
            $msg = env('APP_DEBUG')===true ? $e->getMessage() : null;
            Log::error('twdd CalldriverService error'.$msg, [$e]);

            return $this->error->_('500');
        }

    }

    private function insertMap(Calldriver $calldriver, array $params){
        $paras = $this->filterMap($calldriver, $params);

        return $this->mapRepository->insert($paras);
    }


    private function filter(array $params){
        $call_member_id = $this->determineCallMemberId();
        $members = $this->getMembers();
        if(!is_null($call_member_id)){
            $params['call_type'] = 3;
        }
        $params['call_member_id'] = $call_member_id;

        $params['call_driver_id'] = !empty($this->call_driver->id) ? $this->call_driver->id : null;
        if(is_null($params['call_driver_id'])) {
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
        $params['user_id'] = isset($this->user->id) ? $this->user->id : null;

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
                'TS' => time(),
                'MatchTimes' => 0,
                'IsMatchFail' => 0,
                'is_push' => 0,
            ];
            array_push($paras, $pp);
        }

        return $paras;
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
    public function setCallDriver($call_driver): void
    {
        $this->call_driver = $call_driver;
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
