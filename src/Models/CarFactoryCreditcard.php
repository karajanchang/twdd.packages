<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class CarFactoryCreditcard extends Model
{
    protected $table = 'car_factory_creditcards';

    protected $guarded = ['id'];

    public $timestamps = true;


}
