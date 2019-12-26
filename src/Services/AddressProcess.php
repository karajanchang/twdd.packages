<?php


namespace Twdd\Services;


use Twdd\Helpers\LatLonService;

class AddressProcess{
    private $lat;
    private $lon;
    private $address;
    private $city;
    private $city_id;
    private $district;
    private $district_id;
    private $zip;
    private $addr;

    public function __construct(string $address, $lat, $lon, LatLonService $location = null)
    {
        $this->setAddress($address);
        $this->lat = $lat;
        $this->lon = $lon;
        if(!is_null($location)){
            $this->setLocation($location);
        }

        $this->getZipFromAddress($this->address);
    }

    public function init(){
        //---檢查最後一個字元是"號"，這筆資料才要
        if($this->checkAddressLastWordIsNo()===true){
            $addr = $this->parseAddressZip($this->address);
            $addr = $this->parseAddressRemoveCityDistrict($addr);
            $this->setAddr($addr);

            $address = $this->getZip().'台灣'.$this->getCity().$this->getDistrict().$this->getAddr();

            $this->setAddress($address);
        }
    }

    private function setLocation(LatLonService $location = null){
        if(isset($location['zip'])){
            $this->setZip($location['zip']);
        }
        if(isset($location['city'])){
            $this->setCity($location['city']);
        }
        if(isset($location['city_id'])){
            $this->setCityId($location['city_id']);
        }
        if(isset($location['district'])){
            $this->setDistrict($location['district']);
        }
        if(isset($location['district_id'])){
            $this->setDistrictId($location['district_id']);
        }
    }

    private function getZipFromAddress(string $address){
        $zip = $this->getZip();
        if(is_null($zip)) {
            $pattern = '/^\d{0,5}/';
            if (preg_match($pattern, $address, $matches)) {
                $zip = (int) $matches[0];
                if($zip>0) {
                    $location = app(LatLonService::class)->citydistrictFromLatlonOrZip($this->lat, $this->lon, $zip);
                    $this->setLocation($location);
                }
            }
        }

        return $zip;
    }

    private function checkAddressLastWordIsNo(){
        $len = mb_strlen($this->address, 'UTF-8');
        $no = mb_substr($this->address, $len-1, 1, 'UTF-8');
        if($no!=='號'){

            return false;
        }

        return true;
    }

    private function parseAddressZip($addr){
        $pattern = '/^\d{0,5}/';
        $addr = preg_replace($pattern, '', $addr);

        return $addr;
    }

    private function parseAddressRemoveCityDistrict($addr){
        $addr = str_replace('台灣'.$this->getCity().$this->getDistrict(), '', $addr);
        $addr = str_replace($this->getCity().$this->getDistrict(), '', $addr);
        if($this->getCity()==$this->getDistrict()) {
            $addr = str_replace($this->getCity(), '', $addr);
        }
        $addr = str_replace('號號', '號', $addr);

        return $addr;
    }


    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * @param mixed $district
     */
    public function setDistrict($district): void
    {
        $this->district = $district;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return substr($this->zip, 0, 3);
    }

    /**
     * @param mixed $zip
     */
    public function setZip($zip): void
    {
        $this->zip = substr($zip, 0, 3);
    }

    /**
     * @return mixed
     */
    public function getAddr()
    {
        return $this->addr;
    }

    /**
     * @param mixed $addr
     */
    public function setAddr($addr): void
    {
        $this->addr = $addr;
    }

    /**
     * @return mixed
     */
    public function getCityId()
    {
        return $this->city_id;
    }

    /**
     * @param mixed $city_id
     */
    public function setCityId($city_id): void
    {
        $this->city_id = $city_id;
    }

    /**
     * @return mixed
     */
    public function getDistrictId()
    {
        return $this->district_id;
    }

    /**
     * @param mixed $district_id
     */
    public function setDistrictId($district_id): void
    {
        $this->district_id = $district_id;
    }

}