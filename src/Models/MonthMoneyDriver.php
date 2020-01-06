<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class MonthMoneyDriver extends Model
{
    protected $table = 'MonthMoneyDriver';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
