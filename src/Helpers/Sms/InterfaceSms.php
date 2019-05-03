<?php

namespace Twdd\Helpers\Sms;

interface InterfaceSms {
	public function to($to);
	public function body();
	public function send($to = null, $body = null, $from = null);
}