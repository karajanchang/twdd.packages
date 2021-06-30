<?php
/*
 * 更改駕駛或個人的PushToken DeviceType
 */

namespace Twdd\Helpers;


use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Twdd\Models\LoginIdentify;
use Twdd\Models\Task;
use Twdd\Repositories\TaskRepository;
use Twdd\Services\Token\DriverPushService;
use Twdd\Services\Token\MemberPushService;

class PushService
{
    private $lut = [
                    'driver' => DriverPushService::class ,
                    'member' => MemberPushService::class ,
                ];
    private $collection;

    private $obj = null;
    private $task = null;

    private $action;
    private $title;
    private $body;
    private $device_type;
    private $push_tokens = [];
    private $taskRepository = null;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->collection = new Collection($this->lut);
        $this->taskRepository = $taskRepository;

        return $this;
    }

    public function app(string $type=null){
        if(is_null($type)){

           throw new Exception('Must provide type value!');
        }
        $class = $this->collection->get($type, '');

        $app = null;
        if(strlen($class) > 0) {
            $app = app()->make($class);
        }
        if(is_null($app)){

            throw new Exception('Please provide correct type: "driver" or "member".');
        }

        return $app;
    }

    /*修改登入者的PushToken和DeviceType*/
    public function createOrUpdateByLoginIdentity(LoginIdentify $loginIdentify){
        $app = $this->app($loginIdentify['type']);
        
        return $app->createOrUpdateByLoginIdentity($loginIdentify);
    }


    /*以下給一般任務推播用*/
    public function task(Model $task){
        if(!$task instanceof Task){
            $task = $this->taskRepository->find($task->id);
        }
        $this->task = $task;

        $this->obj($this->task);

        return $this;
    }

    /**
     * @return Model|Task
     */
    public function getTask()
    {
        return $this->task;
    }

    private function setDeviceType(string $device_type){
        Switch(strtolower($device_type)){
            case 'android':
                $this->device_type = 2;
                break;
            default:
                $this->device_type = 1;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeviceType()
    {
        return $this->device_type;
    }

    /**
     * @param mixed $action
     * @return PushByTaskHelper
     */
    public function action($action): PushService
    {
        $this->action = $action;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $title
     * @return PushByTaskHelper
     */
    public function title($title): PushService
    {
        $this->title = $title;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $body
     * @return PushByTaskHelper
     */
    public function body($body): PushService
    {
        $this->body = $body;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return null
     */
    public function getObj()
    {
        return $this->obj;
    }

    /**
     * @param null $obj
     */
    public function obj($obj): PushService
    {
        $this->obj = $obj;

        return $this;
    }



    public function send2driver(string $action = null, string $title = null, string $body = null, $obj = null){
        if(!is_null($this->task->driver)){
            $driver = $this->task->driver;
        }else{
            throw new \Exception('must have driver');
        }

        if(!is_null($action)){
            $this->action($action);
        }
        if(!is_null($title)){
            $this->title($title);
        }
        if(!is_null($body)) {
            $this->body($body);
        }

        if(!isset($driver->driverpush)){

            return false;
        }
        if(empty($driver->driverpush->DeviceType)){

            return false;
        }

        $this->setDeviceType($driver->driverpush->DeviceType);
        $this->push_tokens = [$driver->driverpush->PushToken];

        if(!empty($this->task->id)) {
            $task = $this->taskRepository->view4push2driver($this->task->id);
            $this->obj($task);
        }
        if(!is_null($obj)){
            $this->obj($obj);
        }

        return $this->send('driver');
    }

    public function send2member(string $action = null, string $title = null, string $body = null, $obj = null){
        if(!is_null($this->task->member)){
            $member = $this->task->member;
        }else{
            throw new \Exception('must have member');
        }

        if(!is_null($action)){
            $this->action($action);
        }
        if(!is_null($title)){
            $this->title($title);
        }
        if(!is_null($body)) {
            $this->body($body);
        }

        if(!isset($member->memberpush)){

            return false;
        }
        if(empty($member->memberpush->DeviceType)){

            return false;
        }

        $this->setDeviceType($member->memberpush->DeviceType);
        $this->push_tokens = [$member->memberpush->PushToken];

        if(!empty($this->task->id)) {
            $task = $this->taskRepository->view4push2member($this->task->id);
            $this->obj($task);
        }
        if(!is_null($obj)){
            $this->obj($obj);
        }

        return $this->send('member');
    }

    private function send($type){
        $app = $this->app($type);

        return $app->send(
                    $this->getDeviceType(),
                    $this->getAction(),
                    $this->getTitle(),
                    $this->getBody(),
                    $this->push_tokens,
                    $this->getObj()
                );

    }
}