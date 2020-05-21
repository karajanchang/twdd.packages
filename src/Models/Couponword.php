<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class Couponword extends Model
{
    protected $table = 'couponword';
    public $timestamps = false;

    protected $guarded = ['id'];

    public function activity(){

        return $this->belongsTo(Activity::class);
    }

}
