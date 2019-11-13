<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:22
 */

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class MemberService extends Facade {
    protected static function getFacadeAccessor() { return 'MemberService'; }
}

/*
 * $params = [
        'UserPhone' => '0933123456',
        'UserPassword' => 'password', (可以為null，預設密碼為123456789)
        'UserName' => null,
        'UserGender' => null,
        'UserEmail' => null,
        'from_source' => 'required|between:1,7',

    ];
    $member = MemberService::register($params, $is_verify_mobile = true);
    （$is_verify_mobile： 是否要檢查手機格式的正確）
 */