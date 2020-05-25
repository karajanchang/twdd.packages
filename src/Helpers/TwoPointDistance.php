<?php


namespace Twdd\Helpers;


use Illuminate\Support\Facades\Log;
use Zhyu\Facades\ZhyuCurl;

class TwoPointDistance
{
    public function google($addr1, $addr2) : array{
        $addr1 = str_replace(' ', ',', trim($addr1));
        $addr2 = str_replace(' ', ',', trim($addr2));
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$addr1&destinations=$addr2&sensor=false&language=zh-TW&units=metric&key=".env('GOOGLE_API_KEY');

        $json = ZhyuCurl::url($url)->get();
        $res = \json_decode($json);

        Log::info('Twdd::TwoPointDistance: '.$addr1.' ~ '.$addr2, [$res]);
        if(!isset($res->status) || $res->status!='OK'){

            return [
                'status' => -1,
                'addr1' => $addr1,
                'addr2' => $addr2,
                'distance' => 0,
                'duration' => 0,
            ];
        }

        $distance = 0;
        $duration = 0;

        if(isset($res->rows[0]->elements)){
            if(count($res->rows[0]->elements)){
                foreach($res->rows[0]->elements as $key => $row){
                    if($key=='distance'){
                        $distance = isset($row->distance)  ?   (int) $row->distance->value :   0;

                    }
                    if($key=='duration'){
                        $duration = isset($row->duration)  ?   (int) $row->duration->value :   0;
                    }
                }
            }
        }

        return [
            'status' => 0,
            'addr1' => $addr1,
            'addr2' => $addr2,
            'distance' => $distance,
            'duration' => $duration,
        ];
    }

    public function line(float $lat1, float $lon1, float $lat2, float $lon2){

        return DistanceTwoPointByLine($lat1, $lon1, $lat2, $lon2);
    }
}