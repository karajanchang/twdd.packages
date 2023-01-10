<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;


class ReserveDemand extends Model {

    protected $table = 'reserve_demand';

    protected $guarded = ['id'];

    public function startAddress()
    {
        return $this->belongsTo(Address::class, 'start_addr_id');
    }

    public function endAddress()
    {
        return $this->belongsTo(Address::class, 'end_addr_id');
    }

    public function calldriverTaskMap()
    {
        return $this->belongsTo(CalldriverTaskMap::class, 'calldriver_task_map_id');
    }
}
