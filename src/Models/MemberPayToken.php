<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class MemberPayToken extends Model
{
    protected $table = 'member_pay_tokens';

    protected $guarded = ['id', 'created_at'];

}