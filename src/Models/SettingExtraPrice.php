<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

class SettingExtraPrice extends Model
{
    protected $table = 'setting_extra_price';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
    
    public function citys(){
    	return $this->belongsToMany('App\City', 'setting_extra_price_city');
    }
}
