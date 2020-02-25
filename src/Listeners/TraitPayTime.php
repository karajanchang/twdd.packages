<?php
namespace Twdd\Listeners;

use Carbon\Carbon;

Trait TraitPayTime{
    private function payTime(){
        $log = $this->task->paylogs->last();

        return !empty($log->created_at) ? Carbon::parse($log->created_at)->format('Y-m-d H:i') : date('Y-m-d H:i');
    }
}
