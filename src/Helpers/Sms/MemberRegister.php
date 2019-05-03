<?php

namespace Twdd\Helpers\Sms;

class MemberRegister implements InterfaceSms {
	protected $infobip;
	protected $to = null;
	protected $code = null;
	protected $body = null;
	protected $from = null;
	public function __construct() {
		$this->infobip = app()->make(\Twdd\Helpers\Infobip::class);
	}
	
	public function to($to){
		$this->to = $to;
		
		return $this;
	}
	
	public function code($code){
		$this->code = $code;	
		
		return $this;
	}
	public function body(){
		$this->body = '你在台灣代駕的手機簡訊認證碼為'.$this->code.' (10分鐘內有效) 時間：'.date('H:i:s');
	}
	
	public function send($to = null, $body = null, $from = null){
		return $this->infobip->sms()->send($this->to, $this->body, $this->from);
	}
	
	
}