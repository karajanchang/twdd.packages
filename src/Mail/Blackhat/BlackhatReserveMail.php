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
        $finder = new \Illuminate\View\FileViewFinder(app()['files'], array(base_path().'/vendor/twdd/packages/src/resources/views'));
        View::setFinder($finder);
        switch ($this->status) {
            case 1:
                $subject = "預約成功通知";
                $view = 'emails.blackhat.success';
                break;
            case 2:
                $subject = "預約取消通知";
                $view = 'emails.blackhat.cancel';
                break;
            case 3:
                $subject = "預約失敗通知";
                $view = 'emails.blackhat.fail';
                break;
        }

        //TWDD-777 隱藏駕駛姓名
        $this->driver->DriverName = sprintf('%s%s',mb_substr($this->driver->DriverName,0,1),$this->driver->DriverGender == 1 ? '先生' : '小姐');

        return $this->subject($subject)
            ->view($view)
            ->with([
                'driver' => $this->driver,
                'calldriverTaskMap' => $this->calldriverTaskMap
            ]);
    }
}
