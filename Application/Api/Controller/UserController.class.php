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

    protected $driverFields = 'id,mobile,password,name,avatar,isValid,created_at,updated_at';

    /**
     * 司机注册
     */
    public function driverRegister()
    {
        $data = array();
        $data['mobile'] = I('post.mobile');
        $data['password'] = I('post.password');
        $data['name'] = I('post.name');
        $data['avatar'] = I('post.avatar');

        $data['Trucks'] = array(
            'platedId' => I('post.truck_plateId'),
            'cate_id' => I('post.truck_cate_id'),
            'avatar' => I('post.truck_avatar')

        );
//        $user = new UsersModel();
//        $data = $user->relation(true)->find(1);
        $User = D('Users');
        print_r($data);
        $id = $User->relation(true)->add($data);
        echo $id;
        print_r($data);

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
            $user = $User->field($this->driverFields)
                ->where("mobile = '%s' AND password = '%s'",array($account,md5($password)))
                ->limit(1)
                ->select()[0];
            if($user){
                session('user_id',$user['id']);
                ////
                $response['status'] = true;
                $response['user'] = $user;
            }
        }
        $this->response($response,'json');
    }

    /**
     * 获取个人的消息
     */
    public function getMyProfile()
    {
        // 获得token 相对应的usertpye, user_id
        $token = I('post.token');
        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];

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
}