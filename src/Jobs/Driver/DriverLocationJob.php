<?php
namespace Twdd\Jobs\Driver;

use App\Jobs\Job;
use Illuminate\Database\Eloquent\Model;
use Twdd\Repositories\DriverLocationRepository;


class DriverLocationJob extends Job
{

    private $driver;
    private $params = [];

    public function __construct(Model $driver, array $params)
    {
        $this->driver = $driver;
        $this->params = $params;
    }

    public function handle(){
        $repository = app()->make(DriverLocationRepository::class);

        return $repository->createOrUpdateByDriverId($this->driver->id, $this->params);
    }
}