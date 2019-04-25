<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-23
 * Time: 15:29
 */

namespace Zhyu\Tests\Packages;


use PHPUnit\Framework\TestCase;
use Zhyu\Helpers\ZhyuCurl;

class CurlTest extends TestCase
{
    public function test(){
        $zhyuCurl = new ZhyuCurl('https://www.twdd.tw');
        $output = $zhyuCurl->get();
        print_R($output);
    }

}