<?php
namespace Twdd\Listeners;

Trait TraitPayTime{
    private function payTime(){
        $log = $this->task->paylogs->last();

        return !empty($log->created_at) ? $log->created_at->format('Y-m-d H:i') : date('Y-m-d H:i');
    }
}
