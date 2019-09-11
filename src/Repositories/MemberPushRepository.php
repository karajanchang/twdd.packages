<?php


namespace Twdd\Repositories;


use Twdd\Models\MemberPush;
use Zhyu\Repositories\Eloquents\Repository;

class MemberPushRepository extends Repository
{
    public function model(){

        return MemberPush::class;
    }
}