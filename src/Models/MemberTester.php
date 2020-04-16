<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class MemberTester extends Model
{
    protected $table = 'member_tester';
    public $timestamps = true;

    protected $guarded = ['id'];

    public function member(){

        return $this->belongsTo(Member::class);
    }

    public function androidPush(){

        return $this->belongsTo(MemberPush::class, 'member_id', 'member_id')->where('DeviceType', 'Android');
    }

    public function iosPush(){

        return $this->belongsTo(MemberPush::class, 'member_id', 'member_id')->where('DeviceType', 'iPhone');
    }
}
