<?php
namespace Twdd\Jobs\Driver;

use App\Jobs\Job;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Twdd\Repositories\MongoDriverLatLonRepository;


class MogoDriverLatLonJob extends Job
{

    private $driver;
    private $params = [];
    private $type = 1;
    /*
     * 從哪裡執行此job  從  $is_offline_by_others 代過來的
     *   0駕駛自己  1被客人下線 2被客服下線
     */
    private $by_source = 0;

    public function __construct(Model $driver, array $params, array $attributes, int $type = 1, int $by_type = 0)
    {
        $this->driver = $driver;
        $this->attributes = $attributes;
        $params['device_token'] = $attributes['ID'];
        $this->params = $params;
        $this->type = $type;
        $this->by_type = $by_type;
    }

    public function handle(){
        $mongo_db_host = env('MONGO_DB_HOST', null);
        $mongo_db_port = env('MONGO_DB_PORT', null);
        if(is_null($mongo_db_host) || is_null($mongo_db_port)){

            return false;
        }
        try {
            $repository = app()->make(MongoDriverLatLonRepository::class);

            $lut = [
                1 => 'online',
                2 => 'offline',
                3 => 'updateLocation',
            ];
            if (!isset($lut[$this->type])) {
                throw new Exception('MogoDriverLatLon does not have this type: ' . $this->type);
            }
            $method = $lut[$this->type];

            $res = $repository->$method($this->driver, $this->params);
            Log::info('Mogo '.$method.' ('.$this->driver->id.') res: ', [$res]);

            return $res;
        }catch (\Exception $e){

            Log::notice('Mongo database fails!!!', [$e]);
            return false;
        }
    }
}