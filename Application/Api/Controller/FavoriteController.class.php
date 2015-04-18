<?php
/**
 * Created by PhpStorm.
 * User: niuwei
 * Date: 15/4/15
 * Time: 16:02
 * 商户模块:收藏夹信息
 */

namespace Api\Controller;
use Think\Controller\RestController;

class FavoriteController extends RestController{

    /**
     * 商户获取收藏夹信息
     */
    public function getFavorites()
    {
        $result = array();
        $token = I('post.token');
        $this->validate_token($token);
        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];

        $data = M('merchant_favorites')->field('id,merchant_id,driver_id')
            ->where(array('merchant_id' => $tokenData['user_id']))->select();
        if($data)
        {
            $result['status'] = 'OK';
            $result['content'] = $data;
        }
        $this->response($result,'json');
    }

    /**
     * 商户添加收藏夹 可以使用
     */
    public function addFavorite()
    {
        $token = I('post.token');
        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];

        $merchant_id = $tokenData['user_id'];//商户id
        $driver_id = I('driver_id');//司机的id

        $data = array();
        $data['merchant_id'] = $merchant_id;
        $data['driver_id'] = $driver_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $MerchantFavorite = D('Merchant_favorites');
        $id = $MerchantFavorite->add($data);
        //返回数据
        if(intval($id) != 0)
        {
            $result['status'] = 'OK';
            $result['content'] = '添加成功';
        }else{
            $result['status'] = 'ERROR';
            $result['content'] = '添加失败,车主已经被添加';
        }
        $this->response($result,'json');
    }

    /**
     * 商户删除收藏夹 可以使用
     */
    public function deleteFavoriteById()
    {
        $token = I('post.token');
        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];

        $merchant_id = $tokenData['user_id'];//商户id
        $id = I('post.id');

        $MerchantFavorite = D('Merchant_favorites');
        $id = $MerchantFavorite->where(array('merchant_id' => $merchant_id, 'id' => $id))->delete();
        //返回数据
        if (intval($id) != 0)
        {
            $result['status'] = 'OK';
            $result['content'] = '删除成功';
        }else{
            $result['status'] = 'ERROR';
            $result['content'] = '删除失败,车主已经被删除';
        }
        $this->response($result,'json');
    }

    function validate_token($token)
    {

        $condition['token'] = $token; // 查询条件
        $token_data = M('tokens')->field('userType,user_id,updated_at')
            ->where($condition)->select()[0];   //得到token 的数据

        if(!$token_data) {
            //如果token 错误，则返回错误信息
            $result['status'] = 'ERROR';
            $result['content'] = 'token is error';
            $this->response($result, 'json');
        }else {
            $token_updated_time = $token_data['updated_at'];
            if(strtotime("$token_updated_time +2 day") - strtotime(date("Y-m-d H:i:s")) < 0)
            {
                //token 已经过期,销毁token
                M('tokens')->where($condition)->delete();
                $result['status'] = 'ERROR';
                $result['content'] = 'token is out_of_time';
                $this->response($result,'json');
            } else {
                //token 未过期，进行相应的操作
                $token_data['updated_at'] = date('Y-m-d H:i:s');
                return $token_data;
            }
        }
    }
}