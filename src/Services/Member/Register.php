<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 12:09
 */

namespace Twdd\Services\Member;


use Twdd\Errors\MemberErrors;
use Twdd\Repositories\MemberRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;



class Register extends ServiceAbstract
{
    use AttributesArrayTrait;

    protected $repository;
    protected $error;
    private $is_verify_mobile = true;

    public function __construct(MemberRepository $repository, MemberErrors $memberErrors)
    {
        $this->repository = $repository;
        $this->error = $memberErrors;
    }

    public function init(array $params = [], $is_verify_mobile = true){
        $this->is_verify_mobile = $is_verify_mobile;
        $error = $this->validate($params);
        if($error!==true){
            return $error;
        }
        $params = $this->filter($params);

        $count = $this->repository->countByUserPhone($params['UserPhone']);
        if($count==0) {
            $params['InviteCode'] = app(InviteCode::class)->init();
            $this->repository->create($params);
        }

        return $this->repository->findBy('UserPhone', $params['UserPhone']);
    }

    private function filter(array $params){
        if(!isset($params['UserPassword'])){
            $params['UserPassword'] = '123456789';
        }
        $params['createtime'] = date('Y-m-d H:i:s');
        $params['updatetime'] = date('Y-m-d H:i:s');
        $params['UserPassword'] = md5($params['UserPassword']);

        return $params;
    }

    public function rules(){
        $UserPhoneRule = $this->is_verify_mobile===false ? 'required' : 'required|regex:/^09\d{2}-?\d{3}-?\d{3}$/';
        return [
            'UserName'              =>  'nullable',
            'UserGender'            =>  'nullable|integer|between:1,2',
            'UserPhone'             =>  $UserPhoneRule,
            'UserEmail'             =>  'nullable|email',
            'UserPassword'          =>  'nullable',
            'from_source'           =>  'required|between:1,7',
            'is_mobile_verify'      =>  'required|boolean'
        ];
    }



}
