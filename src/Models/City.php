<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class City extends Model implements InterfaceModel
{
    protected $table = 'city';
    public $timestamps = false;

    protected $guarded = ['id'];



}
