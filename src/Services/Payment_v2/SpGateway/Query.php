<?php

namespace Twdd\Services\Payment_v2\SpGateway;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Query extends SpGatewayAbstract
{
    private $version;

    public function __construct()
    {
        parent::__construct();
        $this->version = env('SPGATEWAY_PAY_VERSION', '1.1');
    }


    /**
    * @param array $postDataArr
    * @param string $merchantID
    * @param string $merchantHashKey
    * @param string $merchantIvKey
    * @return bool|mixed
    */
    public function exec(array $postDataArr, string $merchantID, string $merchantHashKey, string $merchantIvKey)
    {
        try {
            $postDataArr['MerchantID'] = $merchantID;
            $formParams = $this->appendFixData($postDataArr, $merchantHashKey, $merchantIvKey);

            $client = new Client();
            $res = $client->request('POST', $this->queryUrl, [
                'form_params' => $formParams,
            ]);

            $resBody = json_decode($res->getBody()->getContents(), true);

            Log::info('Query Response', [
                'creditUrl' => $this->queryUrl,
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

    private function appendFixData(array $postData = [], string $merchantHashKey, string $merchantIvKey)
    {
        ksort($postData);
        $check_str = http_build_query($postData);
        $CheckCodeStr = "IV=" . $merchantIvKey . '&' . $check_str . "&Key=" . $merchantHashKey;
        $CheckValue = strtoupper(hash("sha256", $CheckCodeStr));
        $postData['Version'] = $this->version;
        $postData['RespondType'] = 'JSON';
        $postData['TimeStamp'] = time();
        $postData['CheckValue'] = $CheckValue;

        return $postData;
    }
}
