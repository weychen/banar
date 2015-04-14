<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/14
 * Time: 下午7:32
 */

namespace Api\Controller;
use Think\Controller\RestController;

class UserController extends RestController {
    public function driverRegister()
    {
        $driver = I('post.');
        print_r($driver);

    }

    public function driverLogin()
    {
    }

    public function getMyProfile()
    {

    }

    public function bindJPushRegistrationID()
    {

    }

    public function getAllMyTransportOrder()
    {

    }

    public function takeoverByTransportDemandId()
    {

    }
}