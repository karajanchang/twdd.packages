<?php


namespace Twdd\Services;


use Twdd\Facades\TwddCache;
use Twdd\Repositories\SettingExtraPriceRepository;

class SettingExtraPriceService extends ServiceAbstract
{
    public function __construct(SettingExtraPriceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getByCity(int $city_id){
        $all = $this->getFromCacheOrDb();

        $results = $all->where('city_id', $city_id)->toArray();
        $sum = $all->sum('price');

        return [
            'sum' => $sum,
            'results' => $results,
        ];
    }

    private function getFromCacheOrDb(){
        if(TwddCache::key('SettingExtraPriceAll')->has()===false){
            $all = $this->repository->allOpen();
            TwddCache::key('SettingExtraPriceAll')->put($all);
        }else{
            $all = TwddCache::key('SettingExtraPriceAll')->get();
        }

        return $all;
    }
}