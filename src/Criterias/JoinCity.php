<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 23:55
 */

namespace Twdd\Criterias;

use Twdd\Models\City;
use Zhyu\Repositories\Criterias\Join\JoinAbstract;


class JoinCity extends JoinAbstract
{
    public function joinModel()
    {
        return City::class;
    }

}
