<?php


namespace Twdd\Services\Price;


use Twdd\Facades\TwddCache;
use Twdd\Repositories\SettingLongtermPriceRepository;
use Twdd\Services\ServiceAbstract;

class SettingLongtermPriceService extends ServiceAbstract
{
    public function __construct(SettingLongtermPriceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all(int $city_id = 1){
        $rows = TwddCache::price()->longterm($city_id)->get();
        if(!$rows){
            $rows = $this->repository->allByCityId($city_id);
            TwddCache::price()->longterm($city_id)->put($rows);
        }

        return $rows;
    }

    public function fetchByHour(int $city_id = 1, int $hour = null){
        $rhour = is_null($hour) ? date('G') : $hour;
        $rows = $this->all($city_id);

        return $rows->first();
        //return $rows->where('hour_start', '<=', $rhour)->where('hour_end', '>', $rhour)->first();
    }
}