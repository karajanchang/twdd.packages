<?php

namespace Twdd\Helpers;

use Twdd\Infobip\Sms;

class Infobip {
	public function sms(){
		return app()->make(Sms::class);
	}
}