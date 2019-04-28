<?php

namespace Twdd\Ecpay\Invoice\Types\Issue;

use Twdd\Ecpay\CheckMacAbstract;
class IssueCheckMac extends CheckMacAbstract {
	public function urlencodes() {
		return [
			'CustomerName',
			'CustomerAddr',
			'CustomerEmail',
			'InvoiceRemark',
			'ItemName',
			'ItemWord',
			'ItemRemark',
		];
	}
	
	public function excepts() {
		return [
			'InvoiceRemark',
			'ItemName',
			'ItemWord',
			'ItemRemark',
			'CheckMacValue'
		];
	}
}