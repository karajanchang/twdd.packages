<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 23:55
 */

namespace Twdd\Criterias\Calldriver;

use Twdd\Models\Calldriver;
use Zhyu\Repositories\Criterias\Join\JoinAbstract;


class JoinCalldriver extends JoinAbstract
{
    public function joinModel()
    {
        return Calldriver::class;
    }

}
