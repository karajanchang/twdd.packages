<?php


namespace Twdd\Services\Match\CallTypes;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Jyun\Mapsapi\TwddMap\Geocoding;
use Twdd\Facades\SettingExtraPriceService;
use Twdd\Facades\TaskService;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\MemberRepository;
use Twdd\Facades\CouponValid;
use Twdd\Services\ServiceAbstract;
use Illuminate\Support\Facades\Schema;
use Twdd\Repositories\EnterpriseStaffRepository;

class AbstractCall extends ServiceAbstract
{
    protected $coupon = null;

    protected $member = null;
    protected $callMember = null;

    protected $user = null;

    //指定駕駛
    protected $callDriver = null;

    //邀請會員
    protected $inviteMember = null;

    //BIND DRIVER （OtherInviteCode 獲客計劃輸入DriverID時）
    protected $bindDriver = null;

    //---傳進來的參數
    protected $params;

    //---CalldriverService
    protected static $calldriverService = null;

    protected $call_type = 1;

    /*
     * 這些是要不要檢查的
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
        'HavePrematch' =>'error',
    ];

    public function __construct()
    {

    }

    public function cancel(int $calldriverTaskMapId, array $other_params = []){}

    /*
     * 是否不要檢查該媒合前功能
     */
    protected function noCheckList(string $check_name){
        if(isset($this->check_lists[$check_name])){
            if($check_name=='CheckParams' || method_exists($this, $check_name)) {

                return $this->check_lists[$check_name];
            }
        }

        return false;
    }
    /*
     * 從check()的第二個param去判斷是否移除檢查該媒合前功能
     */
    protected function setCheckListsByRemoveLists(array $remove_lists = []){
        if(count($remove_lists)){
            $new_lists = [];
            array_walk($this->check_lists, function($val, $key) use($remove_lists, &$new_lists){
                if(!in_array($key, $remove_lists)){
                    $new_lists[$key] = $val;
                }
            });
            $this->check_lists = $new_lists;
        }
    }

    public function check(array $params, array $remove_lists = [])
    {
        $this->setCheckListsByRemoveLists($remove_lists);
        //--檢查app版本
        $res = $this->noCheckList('AppVer');
        if ($res!==false && $this->AppVer($params) === false) {

            return $this->{$res}('目前的APP版本太舊，請更新你的台灣代駕APP');
        }

        //--停權
        $res = $this->noCheckList('MemberCanMatch');
        if ($res!==false && $this->MemberCanMatch() === false) {

            return $this->{$res}('你目前停權無法呼叫');
        }

        //--暫時停止可以呼叫，得到endTS
        $res = $this->noCheckList('MemberCanNotCall');
        $endTS = $this->MemberCanNotCall();
        if ($res!==false && $endTS > 0) {
            $dt = Carbon::createFromTimestamp($endTS);

            return $this->{$res}("\n此帳號多次取消，系統暫停呼叫權限，".$dt->addDay()->format('n月j號')."重新開啟\n\n");
        }



        //--該會員是否為永久黑名單
        $res = $this->noCheckList('AlwaysBlackList');
        if ($res!==false && $this->AlwaysBlackList() === true) {

            return $this->{$res}('你目前無法呼叫');
        }

        //2.檢查有沒有在服務區域
        $res = $this->noCheckList('ServiceArea');
        if($res!==false) {
            $serviceArea = $this->ServiceArea($params);
            if ($serviceArea !== true) {
                $msg = isset($serviceArea['error']) ? (string) $serviceArea['error']->getMessage() : '不在服務區內';

                return $this->{$res}($msg, $serviceArea);
            }
        }

        //--檢查參數
        $res = $this->noCheckList('CheckParams');
        if($res!==false) {
            $valid = $this->valid($this->rules(), $params);
            if ($valid !== true) {

                return $valid;
            }
        }

        //--檢查優惠券欄位是不有效
        if (!empty($params['UserCreditCode']) && $this->checkCouponCodeIsValid($params['UserCreditCode']) !== true) {

            return $this->error('優惠券輸入有誤');
        }

        //--檢查 DriverID  指定駕駛
        if (!empty($params['DriverID'])) {
            $check = $this->checkDriverIDIsValid($params['DriverID']);
            if ($check['status'] === false) {

                return $this->error($check['msg']);
            }
        }

        //--檢查OtherInviteCode欄位是不是有效 (可能輸入coupon code, DriverID, Member InviteCode三種)
        if (!empty($params['OtherInviteCode']) && $this->checkOtherInviteCodeIsValid($params['OtherInviteCode']) !== true) {

            return $this->error('邀請碼輸入有誤');
        }

        return true;
    }




    /*
     * 處理 params
     */
    public function processParams(array $params, array $other_params = []) : array{
        if(count($other_params)){
            $params = array_merge($params, $other_params);
        }

        $params['type'] = isset($params['type']) ? $params['type'] : 1;

        $params = $this->parseAddressByParams($params);

        $params['addrKey'] = !empty($params['addrKey']) ? $params['addrKey'] :  $params['address'];
        $params['call_type'] = $this->call_type;

        $params['UserCreditCode'] = !empty($this->coupon->id) ? $this->coupon->code : null;
        $params['UserCreditValue'] = !empty($this->coupon->id) ? $this->coupon->money : null;
        $params['coupon_id'] = !empty($this->coupon->id) ? $this->coupon->id : null;
        $params['IsByUserKeyin'] = !empty($this->user->id) ? 1 : 0;
        $params['call_driver_id'] = !empty($this->callDriver->id) ? $this->callDriver->id : null;
        $params['use_jcoin'] = !empty($params['use_jcoin']) ? (int)(bool)$params['use_jcoin'] : 0;

        $checkIsEntepriseStaff = self::checkMemberIsEnterpriseStaff($this->member->id);

        //怕calldriver的enterprise_id還沒更新上去, 所以不用三元運算的寫法
        if($checkIsEntepriseStaff){
            $params['enterprise_id'] = $checkIsEntepriseStaff['enterprise_id'];
        }

        return $params;
    }

    private function parseAddressByParams(array $params) : array{
        $city = !empty($params['city']) ? trim($params['city']) : '';
        $district = !empty($params['district']) ? trim($params['district']) : '';
        $addr = !empty($params['addr']) ? trim($params['addr']) : '';
        $address = $city.$district.$addr;

        $is_have_transfer_success = false;
        if(!empty($params['lat']) && !empty($params['lon'])){
            $lat_lon = $params['lat'].','.$params['lon'];
            $location = Geocoding::reverseGeocode($lat_lon)['data'] ?? [];
            $params['city'] = $location['city'] ?? null;
            $params['city_id'] = $location['city_id'] ?? null;
            $params['district'] = $location['district'] ?? null;
            $params['district_id'] = $location['district_id'] ?? null;
            $params['zip'] = $location['zip'] ?? null;
            $params['addr'] = $location['addr'] ?? null;
            $params['address'] = $location['address'] ?? null;

            //--轉換成功
            if($params['district_id']>0 && $params['addr'] && $params['address']) {
                $is_have_transfer_success = true;
            }
        }
        //--如果都沒有轉換成功再改用address來換換
        if($is_have_transfer_success===false && strlen($address)>0) {
            $location = Geocoding::geocode($address)['data'] ?? [];
            $params['city'] = $location['city'] ?? null;
            $params['city_id'] = $location['city_id'] ?? null;
            $params['district'] = $location['district'] ?? null;
            $params['district_id'] = $location['district_id'] ?? null;
            $params['zip'] = $location['zip'] ?? null;
            $params['addr'] = $address;
            $params['address'] = $location['address'] ?? null;
        }

        //--從城市去拿加價資訊
        if(isset($location['city_id'])) {
            $extra_price = SettingExtraPriceService::getByCity($location['city_id']);
            $params['extra_price'] = $extra_price['sum'];
        }

        return $params;
    }



    /*
    * 檢查CouponCode
    */
    public function checkCouponCodeIsValid(string $coupon_code) : bool{
        if(!empty($coupon_code)){

            $coupon = CouponValid::member($this->member)->check($coupon_code);
            if(isset($coupon['error'])){

                return false;
            }
            $this->coupon = $coupon;
        }

        return true;
    }

    /*
    * 檢查 DriverID  指定駕駛
    */
    public function checkDriverIDIsValid(string $DriverID = null, string $type = 'CallDriver') : array{
        if(!empty($DriverID)){
            $callDriver = app(DriverRepository::class)->findByDriverID($DriverID, ['id', 'is_online', 'DriverState']);
            if(empty($callDriver->id)){

                return ['status' => false, 'msg' => '指定駕駛錯誤: 無此位駕駛'];
            }
            if($callDriver->DriverState==0){

                return ['status' => false, 'msg' => '指定駕駛錯誤: 此駕駛並未上線'];
            }
            if($callDriver->DriverState==2){

                return ['status' => false, 'msg' => '指定駕駛錯誤: 此駕駛正在服務中'];
            }
            if(app(CalldriverTaskMapRepository::class)->isInMatchingByDriverID($callDriver->id)===true){

                return ['status' => false, 'msg' => '指定駕駛錯誤: 此駕駛正在媒合中'];
            }
            if($type=='BindDriver') {
                $this->bindDriver = $callDriver;
            }else{
                $this->callDriver = $callDriver;
            }

            return ['status' => true, 'msg' => '可以指定此駕駛'];
        }


        return ['status' => true, 'msg' => '沒有指定駕駛'];
    }

    /*
     * 檢查OtherInviteCode是否正確
     */
    public function checkMemberInviteCodeIsValid(string $OtherInviteCode = null) : bool{
        if(empty($OtherInviteCode)) return true;

        $inviteMember = app(MemberRepository::class)->byInviteCode($OtherInviteCode, ['id', 'InviteCode']);
        if(!empty($inviteMember->id)){
            $this->inviteMember = $inviteMember;

            return true;
        }

        return false;
    }

    /*
     * 從OtherInviteCode來用該code來檢查
     */
    public function checkOtherInviteCodeIsValid(string $OtherInviteCode = null) : bool{
        if(empty($OtherInviteCode)) return true;

        $check_member = $this->checkMemberInviteCodeIsValid($OtherInviteCode);
        if($check_member===true){

            return true;
        }

        if(is_null($this->callDriver)) {
            $check_driver = $this->checkDriverIDIsValid($OtherInviteCode, 'BindDriver');
            if ($check_driver['status'] === true) {

                return true;
            }
        }

        if(is_null($this->coupon)) {
            $check_coupon = $this->checkCouponCodeIsValid($OtherInviteCode);
            if ($check_coupon === true) {

                return true;
            }
        }

        return false;
    }



    /**
     * @return null
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param null $member
     */
    public function setMember($member): AbstractCall
    {
        $this->member = $member;

        return $this;
    }

    /**
     * @return null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null $user
     */
    public function setUser($user): AbstractCall
    {
        $this->getCalldriverServiceInstance()->setUser($user);
        $this->user = $user;

        return $this;
    }

    /**
     * @return null
     */
    public function getCallMember()
    {
        return $this->callMember;
    }

    /**
     * @param null $callMember
     */
    public function setCallMember(Model $callMember): AbstractCall
    {
        $this->getCalldriverServiceInstance()->setCallMember($callMember);
        $this->callMember = $callMember;

        return $this;
    }

    /**
     * @return null
     */
    public function getCallDriver()
    {
        return $this->callDriver;
    }

    /**
     * @param null $callDriver
     */
    public function setCallDriver(Model $callDriver): AbstractCall
    {
        $this->callDriver = $callDriver;

        return $this;
    }




    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params): void
    {
        $this->params = $params;
    }


    public function getCalldriverServiceInstance(){
        if(static::$calldriverService===null){

            static::$calldriverService = TaskService::Calldriver($this->member);

            return static::$calldriverService;
        }

        return static::$calldriverService;
    }

    private function checkMemberIsEnterpriseStaff($id)
    {
        //只要該會員是合作企業的員工, 多塞enterprise_id給calldriver
        if (Schema::hasColumns('enterprise_staffs', ['enable'])){
            $repo = app(EnterpriseStaffRepository::class);
            return $repo->checkMemberIsStaffByID($id);
        }

        return false;
    }

}
