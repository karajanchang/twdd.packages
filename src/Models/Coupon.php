<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class Coupon extends Model implements InterfaceModel
{
    protected $table = 'coupon';
    public $timestamps = false;

    protected $guarded = ['id'];



}
