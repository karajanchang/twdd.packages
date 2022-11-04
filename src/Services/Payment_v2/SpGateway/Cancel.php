<?php

namespace Twdd\Services\Payment_v2\SpGateway;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Cancel extends SpGatewayAbstract
{
    private $version;

    public function __construct()
    {
        parent::__construct();
        $this->version = env('SPGATEWAY_PAY_VERSION', '1.1');
    }

    public function exec(array $postDataArr, string $merchantID, string $merchantHashKey, string $merchantIvKey)
    {
        try {

            $postDataArr = $this->appendFixData($postDataArr);

            $client = new Client();
            $formParams = [
                'MerchantID_' => $merchantID,
                'Pos_' => 'JSON',
                'PostData_' => $this->encrypt($postDataArr, $merchantHashKey, $merchantIvKey)
            ];
            $res = $client->request('POST', $this->cancelUrl, [
                'form_params' => $formParams,
            ]);

            $resBody = json_decode($res->getBody()->getContents(), true);

            Log::info('Cancel Response', [
                'creditUrl' => $this->cancelUrl,
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
        $postData['Version'] = '1.1';
        $postData['TimeStamp'] = Carbon::now()->timestamp;
        $postData['RespondType'] = 'JSON';
        $postData['IndexType'] = 1;

        return $postData;
    }
}
