<?php


namespace Twdd\Helpers\CalPrice;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use Jyun\Mapsapi\TwddMap\Geocoding;
use Twdd\Facades\SettingExtraPriceService;
use Twdd\Facades\SettingPriceService;
use Twdd\Facades\TwoPointDistance;

class CalPriceService
{
    const longterm_start_mile = 49;
    private $distance;
    private $duration;
    private $price;
    private $startAddr;
    private $endAddr;
    private $TS;

    public function __construct(string $startAddr, string $endAddr, int $TS)
    {
        $this->startAddr = $this->parseAddr($startAddr);
        $this->endAddr = $this->parseAddr($endAddr);
        $distances =  TwoPointDistance::google($this->startAddr, $this->endAddr);
        $this->setDistance($distances['distance']);
        $this->setDuration($distances['duration']);

        $this->TS = !empty($TS) ? $TS : time();
    }

    private function parseAddr(string $addr){

        return  str_replace(' ', ',', trim($addr));
    }

    public function trial() : array{
        $location = Geocoding::geocode($this->startAddr)['data'] ?? [];

        if(empty($location['city_id'])){

            return [
                'normal' => 0,
                'plus' => 0,
                'extra_price' => 0,
            ];
        }

        $prices = [
            'normal' => $this->calPrice($this->distance, $this->duration, $location),
            'plus' => $this->calPrice($this->distance*1.3, $this->duration*1.3, $location),
            'extra_price' => SettingExtraPriceService::getByLatLonOrZip($location['lat'], $location['lon'], $location['zip']),
        ];

        return $prices;
    }

    private function calPrice(float $distance, float $duration, $location){
        $nowHour = Carbon::createFromTimestamp($this->TS)->hour;
        $settingLongPrice = $this->getSettingPrice(4, $location, $this->TS);

        if (!empty($settingLongPrice) && $distance >= $settingLongPrice->base_mile && $nowHour >= $settingLongPrice->hour_start && $nowHour <= $settingLongPrice->hour_end) {
            $settingPrice = $settingLongPrice;
            $call_type = 4;
        } else {
            $settingPrice = $this->getSettingPrice(1, $location, $this->TS);
            $call_type = 1;
        }

        $className = $this->getCalPriceClass($call_type);
        $prices = app($className, [ 'settingPrice' => $settingPrice, 'distance' => $distance, 'duration' => $duration ])->cal();

        return $prices + ['call_type' => $call_type, 'distance' => $distance, 'duration' => $duration];
    }

    private function getCalPriceClass(int $call_type) : string{
        $lut = [
            1 => CalPriceCommon::class,
            2 => CalPriceCommon::class,
            3 => CalPriceCommon::class,
            4 => CalPriceLongterm::class,
        ];

        return Collection::make($lut)->get($call_type, 1);
    }

    private function getSettingPrice($call_type, $location, $TS){
        $dt = Carbon::createFromTimestamp($TS);

        return SettingPriceService::callType($call_type)->fetchByHour($location['city_id'], $dt->format('G'));
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param mixed $distance
     */
    public function setDistance($distance): void
    {
        $this->distance = $distance / 1000;
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param mixed $duration
     */
    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }


}
