<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class BlacklistMember4Driver extends Model
{
    protected $table = 'blacklist_member4driver';
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