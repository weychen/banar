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
     * 获取收藏夹信息 现在不能使用,因为三张表的问题还没有解决
     */
    public function getFavorites()
    {
        $token = I('post.token');
        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];

        $data = M('merchant_favorites')->field('id,merchant_id,driver_id')
            ->where(array('merchant_id' => $tokenData[user_id]))->select();

        $this->response($data,'json');
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

        $MerchantFavorite = D('Merchant_favorites');
        $id = $MerchantFavorite->add($data);
        //返回数据
        if(intval($id) != 0)
        {
            $result['status'] = 'ok';
            $result['content'] = '添加成功';
        }else{
            $result['status'] = 'error';
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
            $result['status'] = 'ok';
            $result['content'] = '删除成功';
        }else{
            $result['status'] = 'error';
            $result['content'] = '删除失败,车主已经被删除';
        }
        $this->response($result,'json');
    }
}