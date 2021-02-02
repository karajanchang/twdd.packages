<?php


namespace Twdd\Services;


use Illuminate\Support\Facades\Log;
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

        $results = $all->where('city_id', $city_id);
        $sum = $results->sum('price');

        return [
            'sum' => $sum,
            'results' => $results->toArray(),
        ];
    }

    private function getFromCacheOrDb(){
        if(TwddCache::key('SettingExtraPriceAll')->has()===false){
            $all = $this->repository->allOpen();
            TwddCache::key('SettingExtraPriceAll')->put($all);
            Log::info('SettingExtraPriceAll 從資料庫拿', [$all]);
        }else{
            $all = TwddCache::key('SettingExtraPriceAll')->get();
            Log::info('SettingExtraPriceAll 從快取拿', [$all]);
        }

        return $all;
    }
}