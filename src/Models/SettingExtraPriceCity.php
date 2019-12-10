<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

class SettingExtraPriceCity extends Model
{
    protected $table = 'setting_extra_price_city';
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
    
}
