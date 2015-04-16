<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/14
 * Time: 下午7:32
 */

namespace Api\Controller;
use Api\Model\UsersModel;
use Think\Controller\RestController;

class UserController extends RestController {

    protected $userFields = 'id,mobile,password,name,avatar,isValid,created_at,updated_at';


    /**
     * 司机注册
     */
    public function driverRegister()
    {
        $result = array();
        $result['status'] = false;
        $user_data = array(
            'mobile' => I('post.mobile'),
            'password' => I('post.password'),
            'name' => I('post.name'),
            'avatar' => I('post.avatar'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),

        );
        $user = D('users');
        if($user->create()) {
            $user_id = $user->add();
        } else {
            $result['error'] = '该手机号码已经注册';
        }

        $driver_data = array(
            'user_id' => $user_id,
            'market_id' => I('post.market_id'),
            'icId' => I('post.icId'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'latestFreeTime' => date('Y-m-d H:i:s'),

        );
        $driver_id = D('drivers')->add($driver_data);

        $truck_data = array(
            'driver_id' => $driver_id,
            'plateId' => I('post.truck_plateId'),
            'cate_id' => I('post.truck_cate_id'),
            'avatar' => I('post.truck_avatar'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),

        );
        $truck_id = D('trucks')->add($truck_data);

        $result['token'] = generate_token();
        $this->response($result,'json');

    }

    /**
     * 返回的token 尚未写
     */
    public function driverLogin()
    {
        $account = I('post.mobile');
        $password = I('post.password');
        $response['status'] = false;
        if(!empty($account) && !empty($password)){

            $User = M('Users');
            $user = $User->field($this->userFields)
                ->where("mobile = '%s' AND password = '%s'",array($account,$password))
                ->limit(1)
                ->select();
            if($user){
                session('user_id',$user['id']);
                ////
                $response['status'] = true;
                $response['user'] = $user;
                $response['token'] = generate_token();
            }
        }
        $this->response($response,'json');
    }

    /**
     * 获取个人信息
     *
     */
    public function getMyProfile()
    {
        // 获得token 相对应的usertpye, user_id
        $token = I('post.token');
        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select();

        $data = M('users')->field('id,name,avatar')
            ->where(array('id' => $tokenData[user_id]))->select();

        $this->response($data,'json');
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
    /*
     * 插入请求 by yellow
     * */
    public function addTransportDemand()
    {
        $demand = array();
        $token = I('token');
        $demand['cate_id'] = I('cate_id');
        $demand['driver_id'] = I('driver_id');
        $condition['token'] = $token;

        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select();
        if(!empty($tokenData))
        {
            $demand['merchant_id'] = $tokenData[0]['user_id'];
            $demand['status'] = "未确认";
            $demand['created_at'] =  date('y-m-d h:i:s',time());
            $model = M('transport_demands');

            $result = $model->add($demand);
            $response = array();
            if($result)
            {
                $response['status'] = 'OK';
                $response['content'] = '添加请求成功';
            }else{
                $response['status'] = 'ERROR';
            }
        }else{
            $response['status'] = 'NOT_LOGIN_IN';
        }

        $this->response($response,'json');
    }
    /*
     *取消订单（如何保证操作安全）
     */
    public function cancelTransportDemandById()
    {
        $response = array();
        $token = I('token');
        $id = I('id');
        $demand = M('tokens')->field('user_id')->where(array('token'=>$token))->select();
        if($demand[0]['user_id']!='')
        {
            $result = M('transport_demands')->where("id = {$id}")->setField('status','已取消');
            if($result)
            {
                $response['status'] = 'OK';
                $response['content'] = '取消订单成功';
            }else
            {
                $response['status'] = 'ERROR';
            }
        }else
        {
            $response['status'] = 'NOT_LOGED_IN';
        }
        $this->response($response,'json');
    }
    public function getAllTransportDemand()
    {
        $response = array();
        $token = I('token');
        $userdata = M('tokens')->field('user_id')->where(array('token'=>$token))->select();
        $user_id = $userdata[0]['user_id'];
        $demands = M('transport_demands')->field('id,cate_id,driver_id')->where(array('user_id'=>$user_id,'status'=>'未确认'))->select();
        foreach($demands as $demand)
        {
            $data = array();
            $data['id'] = $demand['id'];
            $data['cate_id'] = $demand['cate_id'];
            $data['driver_name'] = M('users')->field('user_name')->where(array('user_id'=>$demand['driver_id']))->seleclt();
            array_push($response['content'],$data);
        }
    }
}