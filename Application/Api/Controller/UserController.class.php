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
        $token = I('token');
        $registrationID = I('registrationID');
        $response['status'] = false;
        if (!empty($token) && !empty($registrationID)) {
            
            $Token = M('tokens');
            $map['token'] = $token;
            $user_data = $Token->field('user_id,userType')->where($map)->select();
            $user_id = $user_data['0']['user_id'];
            $userType = $user_data['0']['usertype'];
            
            $j_push_user = M('j_push_users');//实例化极光推送模型
            $j_push_user->user_id = $user_id;
            $j_push_user->registrationID = $registrationID;
            $j_push_user->userType = $userType;
            $j_push_user->created_at = date('Y-m-d H:i:s');
            $j_push_user->updated_at = date('Y-m-d H:i:s');
            
            if ($j_push_user->add()) {
               
                $response['status'] = true;
            }  
        }
        $this->response($response,'json');
    }

    public function getAllMyTransportOrder()
    {
        $TransportOrder = M('transport_orders');
        $TransportDemand = M('transport_demands');
        $Users = M('users');
        $Token = M('tokens');//实例化token对象
        $token = I('token');
        // 
        //用户传入token
        // $token = "AovMsrjlvQnV0SqYNdKiDFLKfFt0horf";
        // $token = "WN6uZTE3KJgbMDawa5QhmoqRrig9Qe80";
        $map['token'] = $token;//使用数组的where
        $user_data = $Token->field('user_id,usertype')->where($map)->limit(1)->select();
        $user_id = $user_data['0']['user_id'];//user_id
        $user_type = $user_data['0']['usertype'];//userType
        
        if ($user_type == 'driver') {

            $Driver = M('drivers');
            $driver_id = (int)$Driver->field('id')->where('user_id=%d',$user_id)->select()['0']['id'];
            $Model = M('transport_orders')
            ->join('lb_transport_demands ON lb_transport_orders.transportDemand_id = lb_transport_demands.id')
            ->join('lb_merchants ON lb_transport_demands.merchant_id = lb_merchants.id')
            ->join('lb_cates ON lb_transport_demands.cate_id = lb_cates.id')
            ->join('lb_users ON lb_merchants.user_id = lb_users.id')
            ->field(array('lb_users.name'=>'merchant_name',
                'lb_users.avatar'=>'merchant_avatar',
                'lb_cates.name'=>'cate_name',
                'lb_transport_orders.created_at'=>'time'))
            ->where('lb_transport_orders.driver_id=%s',$driver_id)
            ->select();
            $this->response($Model,'json');
        }
        elseif($user_type == 'merchant'){

            $Merchant = M('merchants');
            $merchant_id = (int)$Merchant->field('id')->where('user_id=%s',$user_id)->select()['0']['id'];
            $Model = M("transport_demands")
            ->join('lb_transport_orders ON lb_transport_demands.id = lb_transport_orders.transportDemand_id')
            ->join('lb_drivers ON lb_transport_orders.driver_id = lb_drivers.id')
            ->join('lb_users ON lb_drivers.user_id = lb_users.id')
            ->field(array('lb_transport_demands.id'=>'demand_id',
                'lb_transport_demands.status'=>'demand_status',
                'lb_transport_orders.id'=>'order_id',
                'lb_transport_orders.status'=>'order_status',
                'lb_users.mobile'=>'driver_mobile'))
            ->where('lb_transport_demands.merchant_id=%s',$merchant_id)
            ->select();
            $this->response($Model,'json');
        }
        else{
            echo "token is wrong";
        }
        
        
        
    }

    public function takeoverByTransportDemandId()
    {
        $response['status'] = false;
        $token = I('token');
        $transportDemand_id = I('transportDemandId');

        if (!empty($token) && !empty($transportDemand_id)) {

            $Token = M('tokens');
            $map['token'] = $token;
            $user_data = $Token->field('user_id,usertype')->where($map)->select();
            $user_id = $user_data['0']['user_id'];
            $user_type = $user_data['0']['usertype'];
            $driver = M('drivers');
            $driver_id = $driver->field('id')->where('user_id=%s',$user_id)->select()['0']['id'];
            dump($driver_id);
                # code...
            $demand = M('transport_demands');
            $maps['id'] = $transportDemand_id;
            $demand_status = $demand->field('status')->where($maps)->select()['0']['status'];
            dump($demand_status);
            if ($user_type == 'driver') {
                $orders = M('transport_orders');
                
                $orders->transportDemand_id = $transportDemand_id;
                $orders->driver_id = $driver_id;
                $orders->status = '未完成';
                $orders->created_at = date('Y-m-d H:i:s');
                $orders->updated_at = date('Y-m-d H:i:s');
                if ($demand_status == '未确认') {
                    if ($orders->add()) {
                    $response['status'] = true;
                    }
                }
                elseif ($demand_status == '已取消') {
                    echo "订单已取消";
                }
                elseif ($demand_status == '已确认') {
                    echo "订单已存在";
                }
                
            }
        }
        $this->response($response,'json');
    }

 
}