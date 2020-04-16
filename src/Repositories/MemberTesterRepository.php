<?php


namespace Twdd\Repositories;


use Twdd\Models\MemberTester;
use Zhyu\Repositories\Eloquents\Repository;

class MemberTesterRepository extends Repository
{
    public function model(){

        return MemberTester::class;
    }
}