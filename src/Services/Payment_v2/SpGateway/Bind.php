<?php

namespace Twdd\Services\Payment_v2\SpGateway;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Twdd\Models\DriverMerchant;

class Bind extends SpGatewayAbstract
{
    private $version;

    public function __construct()
    {
        parent::__construct();
        $this->version = env('SPGATEWAY_VERSION', '1.1');
    }

    public function exec(array $postDataArr, DriverMerchant $merchant)
    {
        try {
            $postDataArr = $this->appendFixData($postDataArr);
            $client = new Client();
            $formParams = [
                'MerchantID_' => $merchant->MerchantID,
                'Pos_' => 'JSON',
                'PostData_' => $this->encrypt($postDataArr, $merchant->MerchantHashKey, $merchant->MerchantIvKey)
            ];
            $res = $client->request('POST', $this->creditUrl, [
                'form_params' => $formParams,
            ]);

            $resBody = json_decode($res->getBody()->getContents(), true);

            unset($postDataArr['CardNo']); // no log credit card number
            Log::info('bind Response', [
                'creditUrl' => $this->creditUrl,
                'response' => $resBody,
                'postData' => $postDataArr,
                'formParams' => $formParams,
            ]);

            return $resBody;
        } catch (\Exception $e) {
            Log::info(__METHOD__, [$e->getMessage(), $e->getLine()]);
            return false;
        }
    }

    private function appendFixData(array $postData = [])
    {
        $postData['Version'] = $this->version;
        $postData['TimeStamp'] = Carbon::now()->timestamp;
        $postData['TokenSwitch'] = 'get';

        return $postData;
    }
}
