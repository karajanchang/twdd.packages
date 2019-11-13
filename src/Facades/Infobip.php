<?php

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class Infobip extends Facade {
	protected static function getFacadeAccessor() { return 'Infobip'; }
}
/*
 *
 * bool Infobip::sms()->send($to, $text, $from = null);

         ps: $from 預設是用TWDD. env('INFOBIP_FROM', 'TWDD')
 */