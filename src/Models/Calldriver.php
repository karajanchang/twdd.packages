<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

class Calldriver extends Model
{
    protected $table = 'calldriver';
    public $timestamps = false;

    protected $guarded = ['id'];

    public function calldriver_task_map()
    {
        return $this->hasMany(CalldriverTaskMap::class);
    }

}
