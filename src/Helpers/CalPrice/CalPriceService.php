<?php


namespace Twdd\Helpers\CalPrice;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use Twdd\Facades\GoogleMap;
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
        $location = GoogleMap::address($this->startAddr);

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
        $call_type = $this->setCallTypeByDistance($distance);
        $settingPrice = $this->getSettingPrice($call_type, $location, $this->TS);

        $className = $this->getCalPriceClass($call_type);
        $prices = app($className, [ 'settingPrice' => $settingPrice, 'distance' => $distance, 'duration' => $duration ])->cal();

        return $prices + ['call_type' => $call_type, 'distance' => $distance, 'duration' => $duration];
    }

    private function getCalPriceClass(int $call_type) : string{
        $lut = [
            1 => \Twdd\Helpers\CalPrice\CalPriceCommon::class,
            2 => \Twdd\Helpers\CalPrice\CalPriceCommon::class,
            3 => \Twdd\Helpers\CalPrice\CalPriceCommon::class,
            4 => \Twdd\Helpers\CalPrice\CalPriceLongterm::class,
            //5 => \Twdd\Helpers\CalPrice\CalPriceClock::class,
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

    /*
     * 檢查此時段是否沒有長途代駕
     */
    private function isNoLongTerm() : bool
    {
        $dt = Carbon::createFromTimestamp($this->TS);
        $hour = $dt->format('G');
        if($hour>=0 && $hour<=6){

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function setCallTypeByDistance(float $distance)
    {
        $call_type = ($distance > self::longterm_start_mile) && $this->isNoLongTerm()===false  ? 4 : 1;

        return $call_type;
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
