<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;


use Twdd\Facades\TwddCache;
use Twdd\Repositories\MemberCreditcardRepository;
use Twdd\Repositories\MemberRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\ModelToolTrait;

class MemberService extends ServiceAbstract
{
    use ModelToolTrait;

    public function register(array $params, $is_verify_mobile = true){
        $register = app()->make(\Twdd\Services\Member\Register::class);

        return $register->init($params, $is_verify_mobile);
    }

    public function profile(int $id, array $columns = ['*'], $clear_cache = false){
        $repository = app()->make(MemberRepository::class);

        $default_profile = TwddCache::member($id)->MemberProfile()->key('MemberProfile', $id)->get();
        if(!$default_profile || $clear_cache===true){
            $default_profile = $repository->profile($id);
            TwddCache::member($id)->MemberProfile()->key('MemberProfile', $id)->put($default_profile);
        }

        $all_columns = ['*'];
        if(count(array_diff($columns, $all_columns))==0){

            return $default_profile;
        }

        if($this->checkColumnsIsExistsInThisModel($columns, $default_profile)===false){

            return $repository->find($id, $columns);
        }

        return $default_profile;
    }

    public function defaultCreditCard(int $memberId)
    {
        $repository = app()->make(MemberCreditcardRepository::class);
        return $repository->defaultCreditCard($memberId);
    }
}
