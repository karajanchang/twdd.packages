<?php

namespace Twdd\Mail\Blackhat;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

use Twdd\Models\Driver;
use Twdd\Models\CalldriverTaskMap;

class BlackhatReserveMail extends Mailable
{
    use Queueable, SerializesModels;
    public $driver;
    public $calldriverTaskMap;
    public $status;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Driver $driver, CalldriverTaskMap $calldriverTaskMap, $status)
    {
        $this->driver = $driver;
        $this->calldriverTaskMap = $calldriverTaskMap;
        $this->status = $status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        switch ($this->status) {
            case 1:
                $subject = "黑帽客預約成功通知";
                $view = 'twdd::emails.blackhat.success';
                break;
            case 2:
                $subject = "黑帽客預約取消通知";
                $view = 'twdd::emails.blackhat.cancel';
                break;
            case 3:
                $subject = "黑帽客預約失敗通知";
                $view = 'twdd::emails.blackhat.fail';
                break;
        }

        return $this->subject($subject)
            ->view($view)
            ->with([
                'driver' => $this->driver,
                'calldriverTaskMap' => $this->calldriverTaskMap
            ]);
    }
}
