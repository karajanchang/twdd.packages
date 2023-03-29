<?php

namespace Twdd\Jobs\Blackhat;

use App\Jobs\Job;

use Twdd\Mail\Blackhat\BlackhatReserveMail;
use Illuminate\Support\Facades\Mail;
use Twdd\Models\DriverTaskExperience;
use Twdd\Models\Driver;

class BlackhatReserveMailJob extends Job
{

    private $params;

    /**
     * Params should include:
     *
     * status 1:預約成功,2:預約取消, 3:預約失敗(30min 未付訂金)
     * driver
     * call_driver_task_map
     * email
     * 
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {

        if (is_numeric($this->params['driver'])) {
            $this->params['driver'] = Driver::find($this->params['driver'], ['id', 'DriverName']);
        }

        if ($this->params['status'] == 1) {
            $star = DriverTaskExperience::where('driver_id', $this->params['driver']['id'])->avg('ExperienceRating');
            $this->params['driver']['stars'] = $star ? round($star, 2) : 0;
        }
        Mail::to($this->params['email'])->send(new BlackhatReserveMail($this->params['driver'], $this->params['calldriverTaskMap'], $this->params['status']));
    }
}
