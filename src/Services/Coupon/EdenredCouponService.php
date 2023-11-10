<?php
namespace Twdd\Services\Coupon;

use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Twdd\Services\Coupon\CouponCode;
use Twdd\Repositories\CouponRepository;
use Twdd\Repositories\EdenredKeyRepository;
use Twdd\Repositories\EdenredCouponRepository;

class EdenredCouponService
{
    private $EdenredCouponRepository;
    private $EdenredKeyRepository;
    private $CouponRepository;
    private $CouponCode;
    public function __construct(EdenredCouponRepository $EdenredCouponRepository, EdenredKeyRepository $EdenredKeyRepository, CouponRepository $CouponRepository, CouponCode $CouponCode)
    {
        $this->EdenredCouponRepository = $EdenredCouponRepository;
        $this->EdenredKeyRepository = $EdenredKeyRepository;
        $this->CouponRepository = $CouponRepository;
        $this->CouponCode = $CouponCode;
    }

    /**
     * Sign In - 簽入
     * 目的：取得 WorkKey
     * 
     * @return \Twdd\Models\EdenredKey|null  
     */
    public function getWorkKey()
    {
        try {
            DB::beginTransaction();
            $edenred_key = $this->EdenredKeyRepository->getWorkKeyByDate();

            if (!$edenred_key) {
                // 參數設定
                $Channel = env('EDENRED_CHANNEL', 'Test'); // 正式機：POS，測試機：Test
                $MerchantCode = env('EDENRED_MERCHANT_CODE', '000000000000038'); // 宜睿提供
                $ProgramCode = env('EDENRED_PROGRAM_CODR', '00001'); // 宜睿提供
                $ShopCode = env('EDENRED_SHOP_CODE', '0000001028'); // 宜睿提供
                $ManageTerminalDateTime = date('YmdHis'); // 格式：yyyyMMddHHmmss
                $TerminalSSN = $ManageTerminalDateTime . '000001'; // ex:20151015105959 + 000001(流水碼) 
                $ManageType = '101'; // 固定值
                $SecurityKey = env('EDENRED_SECURITY_KEY', 'BF14C03588F8B095033694D785C87416'); // 宜睿提供
                $url = env('EDENRED_URL', 'https://stage-posapi2.tixpress.tw/POSProxyService.svc'); // 正式機：https://posapi2.ticketxpress.com.tw/POSProxyService.svc，測試機：https://stage-posapi2.tixpress.tw/POSProxyService.svc

                $Checksum = md5($Channel . '=' . $MerchantCode . '=' . $ProgramCode . '=' . $ShopCode . '==' . $TerminalSSN . '=' . $ManageTerminalDateTime . '=' . $ManageType . '=' . $SecurityKey); // 格式：Channel=MerchantCode=ProgramCode=ShopCode==TerminalSSN=ManageTerminalDateTime=ManageType=SecurityKey
                $data = [
                    '{Checksum}' => $Checksum,
                    '{Channel}' => $Channel,
                    '{MerchantCode}' => $MerchantCode,
                    '{ProgramCode}' => $ProgramCode,
                    '{ShopCode}' => $ShopCode,
                    '{TerminalSSN}' => $TerminalSSN,
                    '{ManageTerminalDateTime}' => $ManageTerminalDateTime,
                    '{ManageType}' => $ManageType,
                ];
                $xmlTemplate = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" 
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                    xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                    xmlns:m0="http://schemas.datacontract.org/2004/07/eVoucher.Authorization.Common">
                        <SOAP-ENV:Body>
                            <m:ManageTerminal xmlns:m="http://ticketxpress.com.tw/">
                                <m:manageTerminalRequest>
                                    <m0:Channel>{Channel}</m0:Channel>
                                    <m0:Checksum>{Checksum}</m0:Checksum>
                                    <m0:ManageTerminalDateTime>{ManageTerminalDateTime}</m0:ManageTerminalDateTime>
                                    <m0:ManageType>{ManageType}</m0:ManageType>
                                    <m0:MerchantCode>{MerchantCode}</m0:MerchantCode>
                                    <m0:ProgramCode>{ProgramCode}</m0:ProgramCode>
                                    <m0:ShopCode>{ShopCode}</m0:ShopCode>
                                    <m0:TerminalCode></m0:TerminalCode>
                                    <m0:TerminalSSN>{TerminalSSN}</m0:TerminalSSN>
                                </m:manageTerminalRequest>
                            </m:ManageTerminal>
                        </SOAP-ENV:Body>
                    </SOAP-ENV:Envelope>';
                $xmlRequest = strtr($xmlTemplate, $data); // 將值替換到 XML 模板中

                $response = Http::withHeaders([
                    'Content-Type' => 'text/xml',
                    'SOAPAction' => 'http://ticketxpress.com.tw/IPOSProxy/ManageTerminal' // 固定值
                ])->send('POST', $url, ['body' => $xmlRequest]);

                if ($response->status() == 200) {
                    $xmlString = $response->body();
                    preg_match_all('/<a:Checksum>(.*?)<\/a:Checksum>/', $xmlString, $checksum);
                    preg_match_all('/<a:Message>(.*?)<\/a:Message>/', $xmlString, $message);
                    preg_match_all('/<a:ResponseCode>(.*?)<\/a:ResponseCode>/', $xmlString, $responseCode);
                    preg_match_all('/<a:ServerDate>(.*?)<\/a:ServerDate>/', $xmlString, $serverDate);
                    preg_match_all('/<a:ServerTime>(.*?)<\/a:ServerTime>/', $xmlString, $serverTime);
                    preg_match_all('/<a:WorkKey>(.*?)<\/a:WorkKey>/', $xmlString, $workKey);
                    $result = [
                        'Checksum' => $checksum[1][0],
                        'Message' => $message[1][0],
                        'ResponseCode' => $responseCode[1][0],
                        'ServerDate' => $serverDate[1][0],
                        'ServerTime' => $serverTime[1][0],
                        'WorkKey' => $workKey[1][0],
                    ];

                    $WorkKey = self::decodeDES($result['WorkKey'], $SecurityKey); // Sign In 取得的 WorkKey 需使用 SecurityKey 解密

                    // 將 work key 存入資料庫 edenred_key 中
                    $edenred_key = $this->EdenredKeyRepository->create([
                        'key' => $WorkKey,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            \Log::error('EdenredCouponService getWorkKey error' . $th->getMessage());
            DB::rollback();
        }

        return $edenred_key;
    }

    /**
     * Redeem – 電子兌換券兌換
     * 
     * @param string $account_number 票券序號
     * @param integer $member_id 乘客 id
     * @return object {
     *      edenred_coupon: \Twdd\Models\EdenredCoupon|null  
     *      coupon: \Twdd\Models\Coupon|null  
     * }
     */
    public function redeemCoupon($account_number, $member_id)
    {
        $edenred_coupon = null;
        $coupon = null;

        try {
            DB::beginTransaction();
            // 取得今天的work key
            $edenred_key = $this->getWorkKey();
            $WorkKey = $edenred_key->key;
            // 取得今天edenred coupon的最大序號
            $max_ssn = $this->EdenredCouponRepository->getMaxSsnByDate();
            // 預設先建立edenred coupon
            $edenred_coupon = $this->EdenredCouponRepository->create([
                'order_no' => $max_ssn,
                'account_number' => $account_number,
            ]);
            // 參數設定
            $AccountNumber = self::encodeDES($account_number, $WorkKey); // 加密的優惠券號碼，皆經過 3 DES 加密 by work key
            $Channel = env('EDENRED_CHANNEL', 'Test'); // 正式機：POS，測試機：Test
            $MerchantCode = env('EDENRED_MERCHANT_CODE', '000000000000038'); // 宜睿提供
            $ProgramCode = env('EDENRED_PROGRAM_CODR', '00001'); // 宜睿提供
            $ShopCode = env('EDENRED_SHOP_CODE', '0000001028'); // 宜睿提供
            $TranTerminalDateTime = date('YmdHis'); // 格式：yyyyMMddHHmmss
            $TerminalSSN = $TranTerminalDateTime . sprintf('%06d', $max_ssn); // ex:20151015105959 + 000001(流水碼)
            $TranAmount = '1'; // 兌換數量，每次使用一張
            $TranType = '101'; // 固定值
            $Rsv2 = date('Ymd'); // yyyyMMdd 帶入交易歸屬日
            $url = env('EDENRED_URL', 'https://stage-posapi2.tixpress.tw/POSProxyService.svc'); // 正式機：https://posapi2.ticketxpress.com.tw/POSProxyService.svc，測試機：https://stage-posapi2.tixpress.tw/POSProxyService.svc

            $Checksum = md5($account_number . '=' . $Channel . '=' . $MerchantCode . '=' . $ProgramCode . '=' . $ShopCode . '==' . $TerminalSSN . '=' . $TranAmount . '==' . $TranTerminalDateTime . '=' . $TranType . '=' . $WorkKey); // AccountNumber=Channel=MerchantCode=ProgramCode=ShopCode==TerminalSSN=TranAmount==TranTerminalDateTime=TranType=WorkKey
            $data = [
                '{Checksum}' => $Checksum,
                '{AccountNumber}' => $AccountNumber,
                '{Channel}' => $Channel,
                '{MerchantCode}' => $MerchantCode,
                '{ProgramCode}' => $ProgramCode,
                '{ShopCode}' => $ShopCode,
                '{TranTerminalDateTime}' => $TranTerminalDateTime,
                '{TerminalSSN}' => $TerminalSSN,
                '{TranAmount}' => $TranAmount,
                '{TranType}' => $TranType,
                '{Rsv2}' => $Rsv2,
            ];
            $xmlTemplate = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" 
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                xmlns:m0="http://schemas.datacontract.org/2004/07/eVoucher.Authorization.Common">
                    <SOAP-ENV:Body>
                        <m:DoTransaction xmlns:m="http://ticketxpress.com.tw/">
                            <m:req>
                                <m0:AccountNumber>{AccountNumber}</m0:AccountNumber>
                                <m0:Channel>{Channel}</m0:Channel>
                                <m0:Checksum>{Checksum}</m0:Checksum>
                                <m0:Encoding>utf8</m0:Encoding>
                                <m0:MerchantCode>{MerchantCode}</m0:MerchantCode>
                                <m0:ProgramCode>{ProgramCode}</m0:ProgramCode>
                                <m0:Rsv2>&lt;root&gt;&lt;businessday&gt;{Rsv2}&lt;/businessday&gt;&lt;/root&gt;</m0:Rsv2>
                                <m0:ShopCode>{ShopCode}</m0:ShopCode>
                                <m0:TerminalSSN>{TerminalSSN}</m0:TerminalSSN>
                                <m0:TranAmount>{TranAmount}</m0:TranAmount>
                                <m0:TranTerminalDateTime>{TranTerminalDateTime}</m0:TranTerminalDateTime>
                                <m0:TranType>{TranType}</m0:TranType>
                            </m:req>
                        </m:DoTransaction>
                    </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>';
            $xmlRequest = strtr($xmlTemplate, $data); // 將值替換到 XML 模板中

            $response = Http::withHeaders([
                'Content-Type' => 'text/xml',
                'SOAPAction' => 'http://ticketxpress.com.tw/IPOSProxy/DoTransaction' // 固定值
            ])->send('POST', $url, ['body' => $xmlRequest]);

            if ($response->status() == 200) {
                $xmlString = $response->body();
                preg_match_all('/<a:Checksum>(.*?)<\/a:Checksum>/', $xmlString, $Checksum);
                preg_match_all('/<a:ExpireDateTime>(.*?)<\/a:ExpireDateTime>/', $xmlString, $ExpireDateTime);
                preg_match_all('/<a:ExternalProductCode>(.*?)<\/a:ExternalProductCode>/', $xmlString, $ExternalProductCode);
                preg_match_all('/<a:Message>(.*?)<\/a:Message>/', $xmlString, $Message);
                preg_match_all('/<a:ProductName>(.*?)<\/a:ProductName>/', $xmlString, $ProductName);
                preg_match_all('/<a:ResponseCode>(.*?)<\/a:ResponseCode>/', $xmlString, $responseCode);
                preg_match_all('/<a:ServerDate>(.*?)<\/a:ServerDate>/', $xmlString, $serverDate);
                preg_match_all('/<a:ServerTime>(.*?)<\/a:ServerTime>/', $xmlString, $serverTime);
                preg_match_all('/<a:TranCode>(.*?)<\/a:TranCode>/', $xmlString, $TranCode);
                $result = [
                    'Checksum' => $Checksum[1][0],
                    'ExpireDateTime' => $ExpireDateTime[1][0] ?? null,
                    'ExternalProductCode' => $ExternalProductCode[1][0] ?? null,
                    'Message' => $Message[1][0],
                    'ProductName' => $ProductName[1][0] ?? null,
                    'ResponseCode' => $responseCode[1][0],
                    'ServerDate' => $serverDate[1][0],
                    'ServerTime' => $serverTime[1][0],
                    'TranCode' => $TranCode[1][0] ?? null,
                ];

                // 驗證回傳是否正確
                $edenred_coupon_data = [];
                $checksum = md5($result['ResponseCode'] . '=' . $result['TranCode'] . '=' . $result['ExternalProductCode'] . '=' . $result['ProductName'] . '=' . $WorkKey . '=' . $TerminalSSN);
                if ($result['ResponseCode'] == "0000") {
                    // 建立coupon
                    $coupon = $this->CouponRepository->create([
                        'code' => $this->CouponCode->init(),
                        'money' => '6969',
                        'title' => $result['ProductName'],
                        'createtime' => Carbon::now(),
                        'member_id' => $member_id
                    ]);
                }
                $edenred_coupon_data = [
                    'coupon_id' => $coupon ? $coupon->id : null,
                    'res_code' => $result['ResponseCode'],
                    'res_message' => $result['Message'] . (($result['Checksum'] != $checksum) ? '(資料驗證錯誤，資料遭竄改)' : ''),
                    'status' => ($result['ResponseCode'] == "0000" && $result['Checksum'] == $checksum) ? 1 : 0,
                ];
            }
            // 更新edenred coupon
            $this->EdenredCouponRepository->update($edenred_coupon->id, $edenred_coupon_data);
            $edenred_coupon = $edenred_coupon->refresh();
            DB::commit();
        } catch (\Throwable $th) {
            \Log::error('EdenredCouponService redeemCoupon error' . $th->getMessage());
            DB::rollback();
        }

        return [
            'edenred_coupon' => $edenred_coupon,
            'coupon' => $coupon
        ];
    }

    /**
     * 加密 3DES
     * 
     * @param string $string 要編碼的字串
     * @param string $key DES Key
     * @return string
     */
    public function encodeDES($string, $key)
    {
        $keyA = hex2bin(substr($key, 0, 16)); // 前 16 字元作為 A 金鑰
        $keyB = hex2bin(substr($key, -16)); // 後 16 字元作為 B 金鑰
        $iv = $keyB;
        // Step 1: 使用 A Key 進行加密
        $step1 = openssl_encrypt($string, 'des-ede3-cbc', $keyA, OPENSSL_RAW_DATA, $iv);
        // Step 2: 使用 B Key 進行解密
        $step2 = openssl_decrypt($step1, 'des-ede3-cbc', $keyB, OPENSSL_NO_PADDING, $iv);
        // Step 3: 再次使用 A Key 進行加密
        $step3 = openssl_encrypt($step2, 'des-ede3-cbc', $keyA, OPENSSL_NO_PADDING, $iv);

        return base64_encode($step3);
    }

    /**
     * 解密 3DES
     * 
     * @param string $base64_string 要編碼的字串
     * @param string $key DES Key
     * @return string
     */
    public function decodeDES($base64_string, $key)
    {
        $keyA = hex2bin(substr($key, 0, 16)); // 前 16 字元作為 A 金鑰
        $keyB = hex2bin(substr($key, -16)); // 後 16 字元作為 B 金鑰
        $iv = $keyB; // 設置初始向量
        // Step 1: 使用 3DES 進行解密
        $step1 = openssl_decrypt(base64_decode($base64_string), 'des-ede3-cbc', $keyA, OPENSSL_NO_PADDING, $iv);
        // Step 2: 使用 3DES 進行加密
        $step2 = openssl_encrypt($step1, 'des-ede3-cbc', $keyB, OPENSSL_NO_PADDING, $iv);
        // Step 3: 再次使用 3DES 進行解密
        $step3 = openssl_decrypt($step2, 'des-ede3-cbc', $keyA, OPENSSL_RAW_DATA, $iv);

        return $step3;
    }
}
