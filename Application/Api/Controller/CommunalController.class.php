<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/14
 * Time: 下午6:53
 * ps: 公用接口类
 */

namespace Api\Controller;
use Think\Controller\RestController;

class CommunalController extends RestController{

    public function getAllCates()
    {
        $Cates = M('cates');
        $data = $Cates->field('id,name')->order('id asc')->select();
        $this->response($data,'json');
    }

    public function getAllMarkets()
    {
        $Market = M('markets');
        $data = $Market->field('id,name,address')->order('id asc')->select();
        $this->response($data, 'json');
    }
}