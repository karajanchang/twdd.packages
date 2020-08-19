<?php


namespace Twdd\Services\Match\CallTypes;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Twdd\Facades\TaskService;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\MemberRepository;
use Twdd\Facades\CouponValid;
use Twdd\Facades\GoogleMap;
use Twdd\Services\ServiceAbstract;

class AbstractCall extends ServiceAbstract
{
    protected $coupon = null;

    protected $member = null;
    protected $callMember = null;

    protected $user = null;

    //指定司機
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

        //--檢查 DriverID  指定司機
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
        if(!empty($params['addrKey']) && !empty($params['addr'])){
            $params['addr'] = str_replace($params['city'], '', $params['addrKey']);
            $params['addr'] = str_replace($params['district'], '', $params['addr']);
        }
        if(!empty($params['addrKey_det']) && !empty($params['addr_det'])){
            $params['addr_det'] = str_replace($params['city_det'], '', $params['addrKey_det']);
            $params['addr_det'] = str_replace($params['district_det'], '', $params['addr_det']);
        }

        $params = $this->parseAddressByParams($params);

        $params['addrKey'] = !empty($params['addrKey']) ? $params['addrKey'] :  $params['address'];
        $params['call_type'] = $this->call_type;

        $params['UserCreditCode'] = !empty($this->coupon->id) ? $this->coupon->code : null;
        $params['UserCreditValue'] = !empty($this->coupon->id) ? $this->coupon->money : null;
        $params['coupon_id'] = !empty($this->coupon->id) ? $this->coupon->id : null;
        $params['IsByUserKeyin'] = !empty($this->user->id) ? 1 : 0;
        $params['call_driver_id'] = !empty($this->callDriver->id) ? $this->callDriver->id : null;

        return $params;
    }

    private function parseAddressByParams(array $params) : array{
        $city = !empty($params['city']) ? trim($params['city']) : '';
        $district = !empty($params['district']) ? trim($params['district']) : '';
        $addr = !empty($params['addr']) ? trim($params['addr']) : '';
        $address = $city.$district.$addr;

        $is_have_transfer_success = false;
        if(!empty($params['lat']) && !empty($params['lon'])){
            if(intval($params['lat'])!=0 && intval($params['lon'])!=0) {
                if (env('APP_DEBUG', false) === true && (bool)app(Request::class)->get('is_testing') === true) {
                    $params['city'] = '台北市';
                    $params['city_id'] = 1;
                    $params['district'] = '中正區';
                    $params['district_id'] = 1;
                    $params['zip'] = '100';
                    $params['addr'] = '臨沂街51號';
                    $params['address'] = '台北市中正區臨沂街51號';
                    $is_have_transfer_success = true;
                } else {
                    $location = GoogleMap::latlon($params['lat'], $params['lon']);
                    $params['city'] = $location['city'];
                    $params['city_id'] = $location['city_id'];
                    $params['district'] = $location['district'];
                    $params['district_id'] = $location['district_id'];
                    $params['zip'] = $location['zip'];
                    $params['addr'] = $location['addr'];
                    $params['address'] = $location['address'];

                    //--轉換成功
                    if($location['district_id']>0) {
                        $is_have_transfer_success = true;
                    }
                }
            }
        }
        //--如果都沒有轉換成功再改用address來換換
        if($is_have_transfer_success===false && strlen($address)>0) {
            $location = GoogleMap::address($address);
            $params['city'] = $location['city'];
            $params['city_id'] = $location['city_id'];
            $params['district'] = $location['district'];
            $params['district_id'] = $location['district_id'];
            $params['zip'] = $location['zip'];
            $params['addr'] = $address;
            $params['address'] = $location['address'];
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
    * 檢查 DriverID  指定司機
    */
    public function checkDriverIDIsValid(string $DriverID = null, string $type = 'CallDriver') : array{
        if(!empty($DriverID)){
            $callDriver = app(DriverRepository::class)->findByDriverID($DriverID, ['id', 'is_online', 'DriverState']);
            if(empty($callDriver->id)){

                return ['status' => false, 'msg' => '指定司機錯誤: 無此位司機'];
            }
            if($callDriver->DriverState==0){

                return ['status' => false, 'msg' => '指定司機錯誤: 此司機並未上線'];
            }
            if($callDriver->DriverState==2){

                return ['status' => false, 'msg' => '指定司機錯誤: 此司機正在服務中'];
            }
            if(app(CalldriverTaskMapRepository::class)->isInMatchingByDriverID($callDriver->id)===true){

                return ['status' => false, 'msg' => '指定司機錯誤: 此司機正在媒合中'];
            }
            if($type=='BindDriver') {
                $this->bindDriver = $callDriver;
            }else{
                $this->callDriver = $callDriver;
            }

            return ['status' => true, 'msg' => '可以指定此司機'];
        }


        return ['status' => true, 'msg' => '沒有指定司機'];
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



}
