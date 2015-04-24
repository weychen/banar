<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/14
 * Time: 下午7:36
 */

namespace Api\Controller;
use Api\Model\UsersModel;
use Think\Controller\RestController;
require_once MODULE_PATH. "aliyun-php/aliyun.php";
use \Aliyun\OSS\OSSClient;
use JPush\JPushClient;
class MerchantController extends RestController {
    protected $userFields = 'id,mobile,password,name,avatar,isValid,created_at,updated_at';

    /**
     * 商户注册
     */
    public function  merchantRegister()
    {
        $result = array();
        $avatar_data = $this->put_pic_to_oss('avatar');
        $user_data = array(
            'mobile' => I('post.mobile'),
            'password' => I('post.password'),
            'name' => I('post.name'),
            'avatar' => $avatar_data,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),

        );
        $user = D('users');
        //判断该用户是否已经被注册
        if(get_user('mobile',$user_data['mobile'])){
            $result['status'] = 'ERROR';
            $result['content'] = '该手机号码已经注册';
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
            //添加jPush信息
            $jPush_data = array(
                'registrationID' => I('registrationid'),
                'user_id' => $user_id,
                'user_type' => 'merchant',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
//            D('j_push_users')->add($jPush_data);
            $registration_id = I('post.registrationid');
            $this->bindJPushRegistrationID($token_data,$registration_id);
            $result['status'] = 'OK';
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
        $registration_id = I('registrationid');
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
                $user = new UserController();
                $user->bindJPushRegistrationID($token_data,$registration_id);
                $result['content'] = $token_data;
                $result['status'] = 'OK';
            }
        }
        $this->response($result,'json');


    }

    /**
     * 获取司机
     */
    public function getDriversByCateId()
    {
        $result['status'] = ERROR;
        // 获得token 相对应的usertpye, user_id
        $token = I('post.token');
        $token_data = $this->validate_token($token);
        $cate_id = I('post.cate_id');
        $merchant = $token_data['user_id'];
        // 用join 查询来查找出所需要的信息
        //收藏的司机的id
        $favorites_id = M('merchant_favorites')
            ->where(array('merchant_id' => $token_data[user_id]))->getField('driver_id',true);
//        //查询收藏的结果集
        if($favorites_id) { //该商户有收藏
            $map1['lb_drivers.id'] = array('in', $favorites_id);
            //  dump($favorites_id);
            $data1 = M('drivers')->field('lb_drivers.id, lb_users.name, lb_users.mobile, lb_drivers.isFree')
                ->join('lb_trucks on lb_drivers.id = lb_trucks.driver_id')
                ->join('lb_users on lb_drivers.user_id = lb_users.id')
                ->where("lb_trucks.cate_id = $cate_id")->where($map1)->order()
                ->select();
            foreach ($data1 as $key => $value) {
                $data1[$key]['isFavorite'] = 1;
            }

            //查询非收藏的结果集
            $map2['lb_drivers.id'] = array('not in', $favorites_id);
            $data2 = M('drivers')->field('lb_drivers.id, lb_users.name, lb_users.mobile, lb_drivers.isFree')
                ->join('lb_trucks on lb_drivers.id = lb_trucks.driver_id')
                ->join('lb_users on lb_drivers.user_id = lb_users.id')
                ->where("lb_trucks.cate_id = $cate_id")->where($map2)->order()
                ->select();
            foreach ($data2 as $key => $value) {
                $data2[$key]['isFavorite'] = 0;
            }
            $data = array_merge($data1, $data2);

            $result['status'] = "OK";
            $result['content'] = $data;
            $this->response($result, 'json');
        } else {//如果没有收藏的手机
            $data = M('drivers')->field('lb_drivers.id, lb_users.name, lb_users.mobile, lb_drivers.isFree')
                ->join('lb_trucks on lb_drivers.id = lb_trucks.driver_id')
                ->join('lb_users on lb_drivers.user_id = lb_users.id')
                ->where("lb_trucks.cate_id = $cate_id")->order()
                ->select();
            foreach ($data as $key => $value) {
                $data[$key]['isFavorite'] = 0;
            }
            $result['status'] = 'OK';
            $result['content'] = $data;
            $this->response($result, 'json');
        }
    }

    /**
     * 商户发起用车请求,商户指定
     */
    public function postATransportDemand()
    {
        //获得参数
        $token = I('post.token');//商户信息
        $this->validate_token($token);
        $cate_id = I('post.cate_id');//车型id
        $driver_id = I('post.driver_id');//司机id
        $isPointed = 1;
        $user_id = M('tokens')->field('user_id')->where(array('token' => $token))->select()[0]['user_id'];
        $merchant_id = M('merchants')->where(array('user_id' => $user_id))->getField('id');
        $status = '未确认';

        $data['cate_id'] = $cate_id;
        $data['driver_id'] = $driver_id;
        $data['ispoint'] = $isPointed;
        $data['merchant_id'] = $merchant_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = $status;

        $id = M('transport_demands')->add($data);

	    $data['demand_id'] = $id;

        //返回数据
        if(intval($id) != 0)
        {
            $result['status'] = 'OK';
            $result['content'] = $data;
            $result['demand_id'] = $id;
        }else{

            $result['status'] = 'ERROR';
            $result['content'] = '添加失败';
        }
        $this->response($result,'json');
    }
    /*
    *取消订单（如何保证操作安全）
    */
    public function cancelTransportDemandById()
    {
        $response = array();
        $token = I('token');
        $this->validate_token($token);
        $id = I('id');
        $demand = M('tokens')->field('user_id')->where(array('token'=>$token))->select();
        if($demand[0]['user_id']!='')
        {
            $update_at = date('y-m-s h:m:s');
            $result = M('transport_demands')->where("id = {$id}")->setField(array('status'=>'已取消','updated_at'=>$update_at));
            if($result)
            {
                $response['status'] = 'OK';
            }else
            {
                $response['status'] = 'ERROR';
                $response['content'] = '取消订单失败';
            }
        }else
        {
            $response['status'] = 'ERROR';
            $response['content'] = 'token is out_of_time';
        }
        $this->response($response,'json');
    }



    /**
     * 商户发起用车,司机自动选取
     */
    public function postATransportDemandByAuto()
    {
        $token = I('post.token');//商户信息
        $this->validate_token($token);
        $cate_id = I('post.cate_id');//车型id
        $isPointed = 0;
        $user_id = M('tokens')->field('user_id')->where(array('token' => $token))->select()[0]['user_id'];
        $merchant_id = M('merchants')->where(array('user_id' => $user_id))->getField('id');
        $status = '未确认';
        #获取空闲司机列表

        $restDrivers = M('drivers')->join("lb_trucks on lb_drivers.user_id = lb_trucks.driver_id")
            ->where("lb_drivers.isFree = 1 AND lb_trucks.cate_id = $cate_id")->select();
     // $restDrivers = M('drivers')->where(array('isFree'=>'1', 'cate_id' => $cate_id))->select();
        #随机生成一个数字
        $count = count($restDrivers);
        if($count > 0 )
        {
            $index = rand(0,$count-1);
            //选取司机的driver_id
            $driver_id = $restDrivers[$index]['user_id'];

            $data['cate_id'] = $cate_id;
            $data['driver_id'] = $driver_id;
            $data['ispoint'] = $isPointed;
            $data['merchant_id'] = $merchant_id;
            $data['status'] = $status;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            //echo $data['driver_id'];
            $id = M('transport_demands')->add($data);

	        $data['demand_id'] = $id;
            //返回数据
            if(intval($id) != 0)
            {
                $result['status'] = 'OK';
                $result['content'] = $data;
            }else{
                $result['status'] = 'ERROR';
                $result['content'] = '添加失败';
            }
        }else{
            $result['status'] = 'ERROR';
            $result['content'] = '没有空闲司机，请换个车型';
        }

        $this->response($result,'json');
    }

    /**
     * 商户确认订单
     */
    public function completeOrder_merchant()
    {

        $token = I('post.token');
        $token_data = $this->validate_token($token); //用户验证

        $Order = M('transport_orders');
        $condition['id'] = I('post.id');//查询条件
        $driver_ok = $Order->where($condition)->getField('driver_ok');

        if($driver_ok) {
            // 更改的内容
            $data['merchant_ok'] = 1;
            $data['status'] = '已完成';
            $data['updated_at'] = date('Y-m-d H:i:s');
            $Order->where($condition)->save($data);
            if ($Order) {
                $result['status'] = 'OK';
                $this->auto_completeOrder();//触发自动保存函数
                //$JPush = new JPushController();
                $merchant_user_id = $token_data['user_id'];
                $merchant_registration_id = M('j_push_users')->where(array('user_id'=>$merchant_user_id))
                    ->getField('registrationID'); //得到registrationid
                //$JPush->sendToMerchantByRegistrationID($merchant_registration_id,'双方已经确认订单完成',);

                $driver_id = $Order->where($condition)->getField('driver_id');
                $driver_user_id = M('drivers')->where(array('id'=>$driver_id))->getField('user_id');
                $driver_registration_id =M('j_push_users')->where(array('user_id'=>$driver_user_id))
                    ->getField('registrationID'); //得到registrationid
                //$JPush->sendToDriverByRegistrationID($driver_registration_id,'订单已经确认');

                $result['content'] = '订单已完成';
                $this->response($result, 'json');
            }
        }else {
            $result['status'] = 'ERROR';
            $result['content'] = '司机未确认完成订单，不能完成';
            $this->response($result, 'json');
        }
    }

    /**
     * 自动完成订单
     */
    public function auto_completeOrder()
    {
        $Order = M('transport_orders');
        $condition['driver_ok'] = 1;
        $condition['merchant_ok'] = 0;

        $ensure_time = date("Y-m-d H:i:s",strtotime("-1 day"));
        $map['updated_at'] = array('elt',$ensure_time);
        $data['merchant_ok'] = 1;
        $data['status'] = '已完成';
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data = $Order->where($condition)->where($map)->save($data);

    }

    /**
     * @param $token    token 值
     * @return mixed
     * 用于验证token 是否正确
     * 如果token 错误，则返回错误信息
     */
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
    /**
     * @return string
     * 把图像上传到云端
     */
    public function put_pic_to_oss($avatar_name)
    {

        $token = generate_token();
        if(!move_uploaded_file($_FILES[$avatar_name]['tmp_name'],"./upload/".$token.".png" ))
        {
            $result['status'] = "ERROR";
            $result['content'] = "图片上传失败";
            $this->response($result, 'json');
        }
        $client = OSSClient::factory(array(
            'AccessKeyId' => 'PdUWUlXoZ0iS05hF',
            'AccessKeySecret' => 'nsMLg5QRScXirbW6UGL9Ec6VGqP2VV',
        ));

        $client->putObject(array(
            'Bucket' => 'banar-image',
            'Key' => $token.".png",
            'Endpoint' => 'http://oss-cn-beijing.aliyuncs.com',
            'Content' => fopen("./upload/".$token.".png", 'r'),
            'ContentLength' => filesize("./upload/".$token.".png"),
        ));

        $avatar_data = "http://banar-image.oss-cn-beijing.aliyuncs.com/".$token. ".png";
        return $avatar_data;
    }
    /**
     * 为用户绑定极光推送账号
     * @param $token
     * @param $registrationID
     */
    public function bindJPushRegistrationID($token,$registrationID)
    {
        $response['status'] = ERROR;
        if (!empty($token) && !empty($registrationID)) {

            $Token = M('tokens');
            $map['token'] = $token;
            $user_data = $Token->field('user_id,userType')->where($map)->select();
            $user_id = $user_data['0']['user_id'];
            $userType = $user_data['0']['usertype'];

            $j_push_user = M('j_push_users');//实例化极光推送模型


            $result = $j_push_user->where(array('$user_id'=>$user_id))->select();
            if ($result!=null) {   //如果已存在记录
                # code...
                $j_push_user->where(array('user_id'=>$user_id))->setField(array('registrationID'=>$registrationID,'updated_at'=>date('Y-m-d H:i:s')));

                $response['status'] = OK;
            }
            else {  //如果不存在记录
                $j_push_user->user_id = $user_id;
                $j_push_user->registrationID = $registrationID;
                $j_push_user->userType = $userType;
                $j_push_user->created_at = date('Y-m-d H:i:s');
                $j_push_user->updated_at = date('Y-m-d H:i:s');
                $j_push_user->add();

                $response['status'] = OK;
            }
            return $response;
        }

    }
}
