<?php

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class Infobip extends Facade {
	protected static function getFacadeAccessor() { return 'Infobip'; }
}