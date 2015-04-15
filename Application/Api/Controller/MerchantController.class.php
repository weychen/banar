<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/14
 * Time: 下午7:36
 */

namespace Api\Controller;
use Think\Controller\RestController;

class MerchantController extends RestController {
    protected $userFields = 'id,mobile,password,name,avatar,isValid,created_at,updated_at';

    /**
     * 商户注册
     */
    public function  merchantRegister()
    {
        $result = array();
        $user_data = array(
            'mobile' => I('post.mobile'),
            'password' => I('post.password'),
            'name' => I('post.name'),
            'avatar' => I('post.avatar'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),

        );
        $user = D('users');
        //判断该用户是否已经被注册
        if(get_user('mobile',$user_data['mobile'])){
            $result['error'] = '该手机号码已经注册';
        } else {
            $user_id = $user->add($user_data);
            $merchant_data = array(
                'user_id' => $user_id,
                'market_id' => I('post.market_id'),
                'address' => I('post.address'),
                'telephone' => I('post.telephone'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $merchant_id = D('merchants')->add($merchant_data);
            //生成token并写入
            $token_data = generate_token();
            $result['token'] = $token_data;
            $token_data = array(
                'token' => $token_data,
                'user_type' => 'merchant',
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );
            D('tokens')->add($token_data);
        }
        $this->response($result,'json');

    }

    /**
     * 商户登录
     */
    public function merchantLogin()
    {
        $account = I('post.mobile');
        $password = I('post.password');
        $response['status'] = false;
        if(!empty($account) && !empty($password)){

            $User = M('Users');
            $user = $User->field($this->userFields)
                ->where("mobile = '%s' AND password = '%s'",array($account,$password))
                ->limit(1)
                ->select()[0];
            if($user){
                session('user_id',$user['id']);
                ////
                $response['status'] = true;
                //$response['user'] = $user;
                $response['token'] = generate_token();
            }
        }
        $this->response($response,'json');
    }
}