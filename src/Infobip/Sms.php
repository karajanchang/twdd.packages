<?php

namespace Twdd\Infobip;

use Illuminate\Support\Facades\Log;
use infobip\api\client\SendSingleTextualSms;
use infobip\api\configuration\BasicAuthConfiguration;
use infobip\api\model\sms\mt\send\textual\SMSTextualRequest;

class Sms {
	private $SMSTextualRequest;
	public function __construct(SMSTextualRequest $SMSTextualRequest)
	{
		$this->SMSTextualRequest = $SMSTextualRequest;
		$this->config = new BasicAuthConfiguration(env('INFOBIP_ACCOUNT', 'TWDD'), env('INFOBIP_PASSWORD', '42745349twdd'));
		$this->config->baseUrl = env('INFOBIP_API_URL', 'https://api.infobip.com/');
	}
	
	public function send($to, $text, $from = null){
		$rfrom = is_null($form) ? env('INFOBIP_FROM', 'TWDD') : $from;
		$this->SMSTextualRequest->setFrom($rfrom);
		$this->SMSTextualRequest->setTo(MobileNumber::encode($to));
		$this->SMSTextualRequest->setText($text);
		$client = new SendSingleTextualSms($this->config);
		$response = $client->execute($this->SMSTextualRequest);
		
		
		$res = ['msg_id' => 0, 'status' => 'fail', 'code' => 0, 'msg' => '失敗'];
		try {
			$sentMessageInfo = $response->getMessages()[0];
			$msg_id = $sentMessageInfo->getMessageId();
			$status = $sentMessageInfo->getStatus()->getName();
			if (isset($msg_id) && strlen($msg_id)>0 && isset($status) && $status == 'PENDING_ENROUTE') {
				Log::info('infobip Sms: '.$msg_id, [$to]);
				
				return ['msg_id' => $msg_id, 'status' => $status, 'code' => 1, 'msg' => '成功'];
			}
			
			return $res;
		}catch (\Exception $e){
			Log::info('infobip SmsService: error:', [$e->getMessage()]);
			
			return $res;
		}
		
	}
}

