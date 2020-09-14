<?php


namespace Twdd\Services\Payments\TapPlay;


use Illuminate\Support\Facades\Log;
use Twdd\Repositories\DriverMerchantRepository;
use Zhyu\Facades\ZhyuCurl;

trait TapPlayTrait
{
    private $partner_key = 'partner_4AUdpqvDirCyje2wvyWC3jMTKxqqOzFh2B0Y8w3aUrdpmlU4fM8mCTDL';
    private $details = '台灣代駕費用';

    private function createMerchants(array $driver_ids = []){
        if(count($driver_ids)==0) return false;

        $merchants = [];
        foreach($driver_ids as $driver_id) {
            $driverMerchant = app(DriverMerchantRepository::class)->findByDriverId($driver_id);

            if(!empty($driverMerchant->MerchantID)) {
                $merchant = new Merchant($driverMerchant->MerchantID, $driverMerchant->MerchantHashKey, $driverMerchant->MerchantIvKey);
            }
            array_push($merchants, $merchant->toArray());
        }

        if(count($merchants)==0) return false;

        $params = [
            'partner_key' => $this->partner_key,
            'merchants' => $merchants,
            'request_type' => $merchant->getRequestType(),
        ];

        try {
            $res = $this->post($merchant->getUrl(), $params);

           return [ 'res' => $res, 'merchants' => $merchants ];
        }catch (\Exception $e){
            Log::error(__CLASS__.'::'.__METHOD__.' 建立商店失敗', [$e]);

            return false;
        }
    }

    /*
     * 送出資料到TapPay
     */
    private function post(string $url, array $postData){
        $res = ZhyuCurl::url($url)
                                ->auth([
                                    'x-api-key ' => $this->partner_key
                                ])
                                ->json($postData, true, (int) env('APPLEPAY_TIMEOUT', 30));

        Log::info(__CLASS__.'::'.__METHOD__.' : ', [$res]);

        return $res;
    }
}