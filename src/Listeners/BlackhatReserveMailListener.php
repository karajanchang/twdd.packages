<?php

namespace Twdd\Listeners;

use Twdd\Events\BlackhatReserveMailEvent;
use Illuminate\Support\Facades\Mail;
use Twdd\Mail\Blackhat\BlackhatReserveMail;
use Twdd\Models\DriverTaskExperience;
use Twdd\Models\Driver;

class BlackhatReserveMailListener
{

    /**
     * Handle the event.
     *
     * @param BlackhatReserveMailEvent $event
     * @return void
     */
    public function handle(BlackhatReserveMailEvent $event)
    {
        $params = $event->params;

        if(is_numeric($params['driver'])){
            $params['driver'] = Driver::find($params['driver'],['id','DriverName']);
        }

        if ($params['status'] == 1) {
            $params['driver']['stars'] = round(DriverTaskExperience::where('driver_id',$params['driver']['id'])->avg('ExperienceRating'),2);
        }

        Mail::to($params['email'])->send(new BlackhatReserveMail($params['driver'],$params['calldriverTaskMap'],$params['status']));
    }
}