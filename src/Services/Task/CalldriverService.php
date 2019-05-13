<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-13
 * Time: 14:00
 */

namespace Twdd\Services\Task;


use phpDocumentor\Reflection\Types\This;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Twdd\Errors\TaskErrors;
use Twdd\Models\Member;
use App\User;
use Twdd\Repositories\CalldriverRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\DistrictRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;
use Twdd\Models\Calldriver;
use Zhyu\Repositories\Contracts\RepositoryInterface;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

class CalldriverService extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $member;
    private $mapRepository;
    private $districtRepository;
    private $user = null;

    public function __construct(CalldriverRepository $repository, TaskErrors $taskErrors, CalldriverTaskMapRepository $mapRepository, DistrictRepository $districtRepository)
    {
        $this->repository = $repository;
        $this->error = $taskErrors;
        $this->mapRepository = $mapRepository;
        $this->districtRepository = $districtRepository;
    }

    public function checkIfDuplicate(){
        if($this->mapRepository->checkIfDuplcate($this->member)>0){

            $this->error->setReplace($this->calucateLastSeconds());
            return $this->error['1005'];
        }
        return true;
    }

    private function calucateLastSeconds(){
        $key = 'CALLDRIVER_CHECK_INITAL'.$this->member->id;
        $seconds = env('CALLDRIVER_CHECK_SECONDS', 60);
        if(!Cache::has($key)){
            Cache::put($key, time(), Carbon::now()->addSeconds($seconds));
        }else{
            $time = Cache::get($key);
            $seconds = $seconds - (time() - $time);
        }

        return $seconds;
    }


    public function create(array $params){
        if(!isset($this->member->id)){

            return $this->error['1004'];
        }

        $error = $this->validate($params);
        if($error!==true){

            return $error;
        }

        /*
        if($this->checkIfDuplicate()!==true){
            return $this->error['1005'];
        }*/

        $cityDistricts = $this->districtRepository->citydistrictFromZip($params['zip']);
        if(isset($cityDistricts) && count($cityDistricts)){
            $params['city'] = $cityDistricts->first()->city;
            $params['district'] = $cityDistricts->first()->district;
        }

        $cityDistricts_det = $this->districtRepository->citydistrictFromZip($params['zip']);
        if(isset($cityDistricts_det) && count($cityDistricts_det)){
            $params['city_det'] = $cityDistricts_det->first()->city;
            $params['district_det'] = $cityDistricts_det->first()->district;
        }

        try {
            $params = $this->filter($params);
            $calldriver = $this->repository->create($params);

            $this->insertMap($calldriver, $params);

            return $calldriver;
        }catch(\Exception $e){
            Bugsnag::notifyException($e);

            return $this->error['500'];
        }

        return $this->error['1005'];
    }

    private function insertMap(Calldriver $calldriver, array $params){
        $paras = $this->filterMap($calldriver, $params);

        return $this->mapRepository->create($paras);
    }

    private function filter(array $params){
        $params['member_id'] = $this->member->id;
        $params['createtime'] = date('Y-m-d H:i:s');
        $params['IsMatch'] = 0;
        $params['IsByUserKeyin'] = 0;
        $params['IsDelete'] = 0;
        $params['tyear'] = date('Y');
        $params['tmonth'] = date('n');
        $params['tday'] = date('j');
        $params['IsApi'] = 0;
        $params['people'] = 1;
        $params['extra_price'] = 0;
        $params['is_push'] = 0;
        $params['is_receive_money_first'] = 0;
        $params['user_id'] = isset($this->user->id) ? $this->user->id : null;

        return $params;
    }

    private function filterMap(Calldriver $calldriver, array $params){
        $paras = [
            'member_id' => $this->member->id,
            'calldriver_id' => $calldriver->id,
            'is_done' => 0,
            'is_cancel' => 0,
            'call_type' => $params['call_type'],
            'TS' => time(),
            'MatchTimes' => 0,
            'IsMatchFail' => 0,
            'is_push' => 0,
        ];

        return $paras;
    }

    /**
     * @return mixed
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param mixed $member
     */
    public function setMember($member): CalldriverService
    {
        $this->member = $member;

        return $this;
    }


    public function user(User $user){
        $this->user = $user;

        return $this;
    }

    public function rules(){

        return [
            'lat' => 'required',
            'lon' => 'required',
            'zip' => 'required|integer',
            'addr' => 'required|string',
            'addrKey' => 'required|string',
            'type' => 'required|integer',
            'call_type' => 'required|integer',
            'pay_type' => 'required|integer',
            'zip_det' => 'nullable|integer',
        ];
    }
}
