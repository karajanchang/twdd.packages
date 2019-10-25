<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class MemberCreditcard extends Model
{
    protected $table = 'member_creditcard';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = ['id'];

}
