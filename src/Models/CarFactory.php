<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class CarFactory extends Model
{
    protected $table = 'car_factories';

    protected $guarded = ['id'];

    //--得到服務廠
    public function parent(){

        return $this->belongsTo(CarFactory::class, 'parent_id', 'id');
    }

}
