<?php


namespace Twdd\Services\Price;


use Twdd\Facades\TwddCache;
use Twdd\Repositories\SettingServicePriceRepository;
use Twdd\Services\ServiceAbstract;

class SettingServicePriceService extends ServiceAbstract
{
    public function __construct(SettingServicePriceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all(int $city_id = 1){
        $rows = TwddCache::price()->common($city_id)->get();
        if(!$rows) {
            $rows = $this->repository->allByCityId($city_id);
            TwddCache::price()->common($city_id)->put($rows);
        }

        return $rows;
    }

    public function fetchByHour(int $city_id = 1, int $hour = null){
        $rhour = is_null($hour) ? date('G') : $hour;
        $rows = $this->all($city_id);

        return $rows->where('hour_start', '<=', $rhour)->where('hour_end', '>', $rhour)->first();
    }
}