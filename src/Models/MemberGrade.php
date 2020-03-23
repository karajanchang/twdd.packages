<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class MemberGrade extends Model
{
    protected $table = 'member_grade';
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
