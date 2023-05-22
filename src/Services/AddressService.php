<?php


namespace Twdd\Services;


use Illuminate\Support\Facades\DB;
use Jyun\Mapsapi\TwddMap\Geocoding;
use Twdd\Repositories\AddressRepository;

class AddressService {

    private $addressRepository;

    public function __construct(AddressRepository $addressRepository)
    {
        $this->addressRepository = $addressRepository;
    }

    public function storeAddress(string $address)
    {
        $data = $this->getAddressData($address);
        if (!$data) {
            return false;
        }

        return $this->addressRepository->store($data);
    }

    // 只限修改自己資料擁有的ID，不可以修改其他筆資料
    public function modAddress(int $id, string $address)
    {
        $data = $this->getAddressData($address);
        if (!$data) {
            return false;
        }

        return $this->addressRepository->update($id, $data);
    }

    private function getAddressData(string $address)
    {
        $location = Geocoding::geocode($address)['data'] ?? [];
        if (empty($location)) {
            return false;
        }
        $geoText = "(ST_GeomFromText('POINT(%s %s)'))";
        $data = [
            'city_id' => $location['city_id'],
            'district_id' => $location['district_id'],
            'zip' => $location['zip'],
            'addr' => $location['addr'],
            'latlon' => DB::raw(sprintf($geoText, $location['lat'], $location['lon'])),
            'address' => $address,
        ];

        return $data;
    }
}
