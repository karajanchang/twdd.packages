<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class BlacklistDriver4Member extends Model
{
    protected $table = 'blacklist_driver4member';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
