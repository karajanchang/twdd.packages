<?php


namespace Twdd\Mail\System;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InfoAdminMail extends Mailable
{
    use Queueable, SerializesModels;
    public $subject;
    public $msg;
    public $obj;

    public function __construct(string $subject, string $msg, $obj = null)
    {
        $this->subject = $subject;
        $this->msg = $msg;
        $this->obj = $obj;
    }

    public function build(){

        return $this->view('emails.system.info')
            ->subject($this->subject)
            ->with([
                'msg' => $this->msg,
                'obj' => $this->obj,
            ]);
    }
}