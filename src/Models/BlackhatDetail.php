<?php
namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

use Twdd\Models\CalldriverTaskMap;

class BlackhatDetail extends Model
{
    public $timestamps = false;

    protected $table = 'blackhat_detail';

    protected $guarded = ['id'];

    protected $fillable = [
        'calldriver_task_map_id',
        'type',
        'type_price',
        'maybe_over_time',
        'start_date',
        'end_date',
        'prematch_status',
        'pay_status',
        'created_at',
        'updated_at',
    ];

    public function calldriver_task_map()
    {
        return $this->hasOne(CalldriverTaskMap::class, 'id', 'calldriver_task_map_id');
    }
}
