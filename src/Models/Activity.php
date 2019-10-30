<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Activity
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ActivityRange[] $activityRanges
 * @property-read \App\Couponword $couponword
 * @mixin \Eloquent
 */
class Activity extends Model {
	public $timestamps = true;
	protected $table = 'activities';

    public function couponword(){
        return $this->HasOne(Couponword::class);
    }
    public function activityRanges(){
        return $this->HasMany(ActivityRange::class);
    }
}