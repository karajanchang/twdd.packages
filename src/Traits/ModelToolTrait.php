<?php


namespace Twdd\Traits;


use Illuminate\Database\Eloquent\Model;

trait ModelToolTrait
{
    
    //---檢查這些columns是不是有都在這個model裡
    public function checkColumnsIsExistsInThisModel(array $columns, Model $model){
        $is_check = true;
        array_walk($columns, function($column) use($model, &$is_check){
            if(!isset($obj->$column)){
                $is_check = false;
            }
        });

        return $is_check;
    }

}