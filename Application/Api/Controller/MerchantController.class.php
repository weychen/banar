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
                'userType' => 'merchant',
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
        $result['status'] = false;
        if(!empty($account) && !empty($password)){

            $User = M('Users');
            $user = $User->field($this->userFields)
                ->where("mobile = '%s' AND password = '%s'",array($account,$password))
                ->limit(1)
                ->select()[0];
            if($user){
                $user_id = $user['id'];
                $token_data = generate_token();
                put_token_into_sql($token_data, 'merchant',$user_id);
                $result['token'] = $token_data;
                $result['status'] = true;
            }
        }
        $this->response($result,'json');


    }

    /**
     * 获取司机
     */
    public function getDriversByCateId()
    {
        // 获得token 相对应的usertpye, user_id
        $token = I('post.token');
        $this->validate_token($token);
        $cate_id = I('post.cate_id');

        $data = M('drivers')->field('lb_drivers.id, lb_users.name, lb_users.mobile, lb_drivers.isFree')
            ->join('lb_trucks on lb_drivers.id = lb_trucks.driver_id')
            ->join('lb_users on lb_drivers.user_id = lb_users.id')
            ->where('lb_trucks.cate_id = 1')
            ->limit(10)->select();
        $result['status'] = "OK";
        $result['content'] = $data;
        $this->response($result,'json');
    }

    /**
     * 商户发起用车请求,商户指定
     */
    public function postATransportDemand()
    {
        //获得参数
        $token = I('post.token');//商户信息
        $cate_id = I('post.cate_id');//车型id
        $driver_id = I('driver_id');//司机id
        $isPointed = 1;
        $merchant_id = M('tokens')->field('user_id')->where(array('token' => $token))->select()[0]['user_id'];
        $status = '未确认';

        $data['cate_id'] = $cate_id;
        $data['driver_id'] = $driver_id;
        $data['isPointed'] = $isPointed;
        $data['merchant_id'] = $merchant_id;
        $data['status'] = $status;

        $id = M('transport_demands')->add($data);
        //返回数据
        if(intval($id) != 0)
        {
            $result['status'] = 'ok';
            $result['content'] = $data;
        }else{
            $result['status'] = 'error';
            $result['content'] = '添加失败';
        }
        $this->response($result,'json');
    }

    /**
     * 商户发起用车请求,商户自动选取
     */
    public function postATransportDemandByAuto()
    {
        $token = I('post.token');//商户信息
        $cate_id = I('post.cate_id');//车型id
        $isPointed = 0;
        $merchant_id = M('tokens')->field('user_id')->where(array('token' => $token))->select()[0]['user_id'];
        $status = '未确认';
        //获取空闲司机列表
        $restDrivers = '';
        //随机生成一个数字
        $index = rand(0, 100);
        //选取司机的driver_id
        $driver_id = $restDrivers[$index]['driver_id'];


        $data['cate_id'] = $cate_id;
        $data['driver_id'] = $driver_id;
        $data['isPointed'] = $isPointed;
        $data['merchant_id'] = $merchant_id;
        $data['status'] = $status;
        $id = M('transport_demands')->add($data);
        //返回数据
        if(intval($id) != 0)
        {
            $result['status'] = 'ok';
            $result['content'] = $data;
        }else{
            $result['status'] = 'error';
            $result['content'] = '添加失败';
        }
        $this->response($result,'json');
    }

    /**
     * @param $token
     * @return mixed
     * 用于验证token 是否正确
     * 如果token 错误，则返回错误信息
     */
    public function validate_token($token)
    {
        $condition['token'] = $token;
        $token_data = M('tokens')->field('userType,user_id,updated_at')
            ->where($condition)->select()[0];

        if(!$token_data) {
            //如果token 错误，则返回错误信息
            $result['status'] = false;
            $result['content']['error'] = 'token is error';
            $this->response($result, 'json');
        }else {
            $token_updated_time = $token_data['updated_at'];
            if(strtotime("$token_updated_time +2 day") - strtotime(date("Y-m-d H:i:s")) < 0)
            {
                //token 已经过期,销毁token
                M('tokens')->where($condition)->delete();
                $result['status'] = false;
                $result['content']['error'] = 'token is out_of_time';
                $this->response($result,'json');
            } else {
                //token 未过期，进行相应的操作
                $token_data['updated_at'] = date('Y-m-d H:i:s');
                return $token_data;
            }
        }
    }
}