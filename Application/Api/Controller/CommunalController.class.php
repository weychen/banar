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

    /**
     * 获取车型
     */
    public function getAllCates()
    {

        $Cates = M('cates');
        $result = array();
        $data = $Cates->field('id,name')->order('id asc')->select();
        if($data)
        {
            $result['status'] = 'OK';
            $result['content'] = $data;
        }else
        {
            $result['status'] = 'ERROR';
            $result['content'] = '车型为空';
        }
        $this->response($result,'json');
    }

    /**
     * 获取所有的市场
     */
    public function getAllMarkets()
    {
        $Market = M('markets');
        $data = $Market->field('id,name,address')->order('id asc')->select();
        if($data)
        {
            $result['status'] = 'OK';
            $result['content'] = $data;
        }else{
            $result['status'] = 'ERROR';
            $result['content'] = '市场为空';
        }


        $this->response($result, 'json');
    }
}