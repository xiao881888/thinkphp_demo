<?php
namespace Integral\Controller;

use Integral\Util\AppException;
use Think\Controller;
use Think\Exception;

class TestController extends GlobalController {


    public function testMode()
    {
        var_dump(get_cfg_var('PROJECT_RUN_MODE'));
    }

    public function sendCoupon()
    {
        (new TurntableController())->grantCoupon(616,235);
    }

}