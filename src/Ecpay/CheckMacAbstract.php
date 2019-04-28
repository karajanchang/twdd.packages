<?php

namespace Twdd\Ecpay;

use ECPay_CheckMacValue;

abstract class CheckMacAbstract {
	private $use_md5 = true;
	private $params = [];
	private $HashKey = '';
	private $HashIV = '';
	private $sMacValue = '';
	
	public function __construct($HashKey, $HashIV) {
		$this->HashKey = $HashKey;
		$this->HashIV = $HashIV;
	}
	
	/**
	 * @return array
	 */
	public function getParams(): array {
		return $this->params;
	}
	
	/**
	 * @param array $params
	 */
	public function setParams($params) {
		$this->params = $params;
		
		return $this;
	}
	

	public function fire(){
		$this->sMacValue = $this->generate();
	}
	
	private function generate(){
		$params = $this->filter();
		$sMacValue = '' ;
		if(is_array($params)) {
			unset($params['CheckMacValue']);
			uksort($params, [$this, 'merchantSort']);
            //dump($params);
			// 組合字串
			$sMacValue = 'HashKey=' . $this->HashKey;
			foreach ($params as $key => $value) {
				$sMacValue .= '&' . $key . '=' . $value;
			}
			$sMacValue .= '&HashIV=' . $this->HashIV;
			
			// URL Encode編碼
			$sMacValue = urlencode($sMacValue);
			// 轉成小寫
			$sMacValue = strtolower($sMacValue);
			// 取代為與 dotNet 相符的字元
			$sMacValue = $this->replaceSymbol($sMacValue);
			//dump($sMacValue);
			
			// 編碼
			if($this->use_md5 === true){
				$sMacValue = md5($sMacValue); 	// MD5 編碼
			}else {
				$sMacValue = hash('sha256', $sMacValue);    // SHA256 編碼
			}
			
			$sMacValue = strtoupper($sMacValue);
			
			return $sMacValue;
		}
		
		return $sMacValue;
	}
	
	private static function merchantSort($a,$b){
		return strcasecmp($a, $b);
	}
	
	/**
	 * 參數內特殊字元取代
	 * 傳入	$sParameters	參數
	 * 傳出	$sParameters	回傳取代後變數
	 */
	protected function replaceSymbol($sParameters){
		if(!empty($sParameters)){
			
			$sParameters = str_replace('%2D', '-', $sParameters);
			$sParameters = str_replace('%2d', '-', $sParameters);
			$sParameters = str_replace('%5F', '_', $sParameters);
			$sParameters = str_replace('%5f', '_', $sParameters);
			$sParameters = str_replace('%2E', '.', $sParameters);
			$sParameters = str_replace('%2e', '.', $sParameters);
			$sParameters = str_replace('%21', '!', $sParameters);
			$sParameters = str_replace('%2A', '*', $sParameters);
			$sParameters = str_replace('%2a', '*', $sParameters);
			$sParameters = str_replace('%28', '(', $sParameters);
			$sParameters = str_replace('%29', ')', $sParameters);
		}
		
		return $sParameters ;
	}
	
	/**
	 * 4處理需要轉換為urlencode的參數
	 */
	protected function urlencode_process(array $params = []){
		$urlencode_fields = $this->urlencodes();

        array_walk($params, function($val, $key) use(&$params, $urlencode_fields){
            if(in_array($key, $urlencode_fields)){
                $params[$key] = $this->replaceSymbol(urlencode($val));
            }
        });

		return $params ;
	}
	
	private function filter(){
	    if(count($this->params)==0){
	        throw new \Exception('Please fill params first!');
        }
	    //dump($this->params);
		$params = [];
		if(count($this->params)){
			$excepts = $this->excepts();
			foreach($this->params as $key => $param){
				if(!in_array($key, $excepts)) {
					$params[ $key ] = $param;
				}
			}
		}
		//dump($params);
		$params = $this->urlencode_process($params);
		return $params;
	}
	
	public function __toString() {
		return $this->sMacValue;
	}
	
	/*設定urlencode的欄位*/
	abstract public function urlencodes();
	
	/*在計算checkMacValue要排除的欄位*/
	abstract public function excepts();
}