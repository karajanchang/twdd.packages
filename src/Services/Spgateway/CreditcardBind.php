<?php


namespace Twdd\Services\Spgateway;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Errors\CreditcardError;
use Twdd\Facades\PayService;
use Twdd\Models\CarFactory;
use Twdd\Models\CarFactoryCreditcard;
use Twdd\Models\DriverMerchant;
use Twdd\Models\Member;
use Twdd\Repositories\CarFactoryCreditcardRepository;
use Twdd\Repositories\MemberCreditcardRepository;
use Twdd\Services\Payments\Traits\SpgatewayTrait;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;
use Zhyu\Facades\Ip;

class CreditcardBind extends ServiceAbstract
{
    use AttributesArrayTrait;
    use SpgatewayTrait;
    protected $error;


    private $MerchantOrderNo = null;
    private $types = [ 'member' => Member::class, 'car_factory' => CarFactory::class];
    private $cardHolder = null;

    public function __construct(CreditcardError $error)
    {
        $this->getDriverMerchant();
        $this->error = $error;
    }

    public function getDriverMerchant(){
        $this->driverMerchant = DriverMerchant::find(env('SPGATEWAY_BIND_DRIVER_MERCHANT_ID', 1443));
    }

    public function type(string $type, int $id){
        try {
            $this->cardHolder = app($this->types[$type])->find($id, ['id']);
        }catch (\Exception $e){
            ErrorLogDetail($e);
            throw new \Exception('無法初始化: '.$this->types[$type]);
        }

        return $this;
    }


    public function init(array $params){
        if(empty($this->cardHolder->id)) throw new \Exception('請先執行 method type()');

        //--檢查參數
        $this->validate($params);

        //--檢查是否有預設商店
        if($this->checkIfDriverMerchantExists() === false){

            //return $this->error('沒有設定預設的商店');
            return $this->error->_('0001');
        }

        $key = env('APP_ENV') . 'SpgatewayBind' . $params['CardNo'];

        try{
            $lock = Cache::lock($key, $this->seconds);
            if($lock->get()) {
                $key = env('APP_ENV'). 'SpagetwayBindTimestamp'.$params['CardNo'];
                Cache::put($key, time(), 60);
                $url = env('SPGATEWAY_URL', 'https://core.spgateway.com/API/CreditCard');
                $datas = $this->prepareBindPostData($params);
                $res = $this->post($url, $datas);
//                dump('11111111111111111111111', $res);
                Log::info('111111111111111111111', [$res]);

                if (isset($res->Status) && $res->Status == 'SUCCESS') {
                    //--寫入到資料表
                    $this->write2db($params, $res->Result);

                    //--退刷1元
                    $this->bindCancel1Dollor();
//                    dump('AAAAAAAAAAAAAAAAAAAAAAAAAAa');
                    return $this->success('操作成功', $res);
                } else {
                    Log::info(__CLASS__.'::'.__METHOD__, [$res]);
                    $msg = isset($res->Message) ? $res->Message : '';
//                    dump('BBBBBBBBBBBBBBBBBBBBBBBBB', $res);

                    return $this->error($res->Message, $res);
                }
            }

            return $this->error('請稍後再試');
        }catch(\Exception $e){
            $msg = '綁卡異常 (會員：'.$params['PayerEmail'].'): '.$e->getMessage();
            Log::info(__CLASS__.'::'.__METHOD__.' exception: ', [$msg, $e]);

            return $this->error($msg);
        }
    }

    //--加入到資料庫內
    private function write2db(array $params, $Result){
        $params = $this->prepareWriteParmas($params, $Result);

        if($this->cardHolder instanceof Member){
            $params['member_id'] = $this->cardHolder->id;

            return app(MemberCreditcardRepository::class)->createAndSetOthersNoDefault($this->cardHolder->id, $params);
        }

        if($this->cardHolder instanceof CarFactory){
            $params['car_factory_id'] = $this->cardHolder->id;
            try {
//                app(CarFactoryCreditcardRepository::class)->crate($params);
                CarFactoryCreditcard::create($params);
            }catch (\Exception $e){
                Log::error('CarFactoryCreditcardRepository create error: ', [$e]);
            }

        }

    }

    //--準備要寫到資料庫的值
    private function prepareWriteParmas(array $params, $Result){

        return [
            'PayerEmail' => $params['PayerEmail'],
            'CardHolder' =>  $params['CardHolder'],
            'Card6No' => substr($params['CardNo'], 0, 6),
            'Card4No' => substr($params['CardNo'], 12, 4),
            'Exp' => $params['Exp'],
            'TokenValue' => $Result->TokenValue,
            'TokenLife' => $Result->TokenLife,
            'IP' => Ip::get(),
            'EscrowBank' => $Result->EscrowBank,
        ];
    }

    private function bindCancel1Dollor() : bool{
        $type = 2;
        if($this->cardHolder instanceof CarFactory){
            $type = 4;
        }
        $res = PayService::by($type)->cancel($this->MerchantOrderNo, 1);
        if(isset($res['error'])){
            Log::info(__CLASS__.'::'.__METHOD__.' error: ', $res['error']);

            return false;
        }

        return true;
    }



    private function prepareBindPostData(array $params){
        $this->MerchantOrderNo = uniqid();
        $array = [
            'TimeStamp'         =>  time(),
            'Version'           =>  '1.0',
            'MerchantOrderNo'   =>  $this->MerchantOrderNo,
            'Amt'               =>  1,
            'ProdDesc'          =>  '約定信用卡',
            'PayerEmail'        =>  $params['PayerEmail'],
            'CardNo'            =>  $params['CardNo'],//16
            'Exp'               =>  $params['Exp'],   //4
            'CVC'               =>  $params['CVC'],   //3
            'TokenSwitch'       =>  'get',
            'TokenTerm'         =>  $this->cardHolder->id,
            'TokenLife'         =>  $params['Exp'],
        ];

        return $this->preparePostData($array);
    }

    private function checkIfDriverMerchantExists(){
        if(empty($this->driverMerchant->MerchantID) || empty($this->driverMerchant->MerchantHashKey) || empty($this->driverMerchant->MerchantIvKey)){

            return false;
        }

        return true;
    }


    public function rules(){

        return [
            'PayerEmail' => 'required|email',
            'CardNo' => 'required|string',
            'Exp' => 'required|string',
            'CVC' => 'required|string',
        ];
    }
}