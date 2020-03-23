<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';
    public $timestamps = false;

    protected $guarded = ['id'];

    public function creditCards(){

        return $this->hasMany(MemberCreditcard::class, 'member_id', 'id');
    }

    public function memberGrade(){

        return $this->belongsTo(MemberGrade::class);
    }

    public function memberpush(){

        return $this->hasOne(MemberPush::class);
    }

}