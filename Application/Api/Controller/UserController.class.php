<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/14
 * Time: 下午7:32
 */

namespace Api\Controller;
use Api\Model\UsersModel;
use JPush\JPushClient;
use Think\Controller\RestController;

require_once MODULE_PATH. "aliyun-php/aliyun.php";
use \Aliyun\OSS\OSSClient;
class UserController extends RestController {

    protected $userFields = 'id,mobile,password,name,avatar,isValid,created_at,updated_at';


    /**
     * 司机注册
     */
    public function driverRegister()
    {
        $result = array();
        $result['status'] = false;
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
        if(get_user('mobile',$user_data['mobile'])){
            $result['content']['error'] = '该手机号码已经注册';
        } else {
            $user_id = $user->add($user_data);
            $driver_data = array(
                'user_id' => $user_id,
                'market_id' => I('post.market_id'),
                'icId' => I('post.icId'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'latestFreeTime' => date('Y-m-d H:i:s'),

            );
            $driver_id = D('drivers')->add($driver_data);

            $avatar_data = $this->put_pic_to_oss('truck_avatar');
            $truck_data = array(
                'driver_id' => $driver_id,
                'plateId' => I('post.truck_plateId'),
                'cate_id' => I('post.truck_cate_id'),
                'avatar' =>  $avatar_data,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $truck_id = D('trucks')->add($truck_data);
            //生成token并写入
            $token_data = generate_token();
            put_token_into_sql($token_data, 'driver', $user_id);
            $data['token'] = $token_data;
            $result['status'] = 'OK';
            $result['content'] = $data;
        }
        $this->response($result,'json');
    }

    /**
     * 返回的token 尚未写
     */
    public function driverLogin()
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
                put_token_into_sql($token_data, 'driver',$user_id);
                $data['token'] = $token_data;
                $this->bindJPushRegistrationID($token_data,$registration_id);
                $result['status'] = 'OK';
                $result['content'] = $data;
            }else {
                $reuslt['content']['error'] = "用户名或密码错误";
            }
        }
        $this->response($result,'json');
    }

    /**
     * 获取个人信息
     *
     */
    public function getMyProfile()
    {
        // 获得token 相对应的usertpye, user_id
        $token = I('post.token');
        $this->validate_token($token);
        $condition['token'] = $token;

        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];
        $data = M('users')->field('id, name, avatar')
            ->where(array('id' => $tokenData['user_id']))->select();
        $result['status'] = 'OK';
        $result['content'] = $data;
        $this->response($result,'json');
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
                echo "绑定新记录成功";
                $response['status'] = OK;
            }
            else {  //如果不存在记录
                $j_push_user->user_id = $user_id;
                $j_push_user->registrationID = $registrationID;
                $j_push_user->userType = $userType;
                $j_push_user->created_at = date('Y-m-d H:i:s');
                $j_push_user->updated_at = date('Y-m-d H:i:s');
                $j_push_user->add();
                echo "更新registrationID成功";
                $response['status'] = OK;
            }       
            return $response;
        }

    }
    /**
     * 获得历史订单
     */
    public function getAllMyTransportOrder()
    {
        $response['status'] = false;
        $response['content'];

        $TransportOrder = M('transport_orders');
        $TransportDemand = M('transport_demands');
        $Users = M('users');
        $Token = M('tokens');//实例化token对象
        $token = I('token');
        $this->validate_token($token);
        // 
        //用户传入token
        // $token = "AovMsrjlvQnV0SqYNdKiDFLKfFt0horf";
        // $token = "WN6uZTE3KJgbMDawa5QhmoqRrig9Qe80";
        if(!empty($token))    //如果token不为空
        {
            $map['token'] = $token;//使用数组的where
            $user_data = $Token->field('user_id,usertype')->where($map)->limit(1)->select();
            $user_id = $user_data['0']['user_id'];//user_id
            $user_type = $user_data['0']['usertype'];//userType
            if (!empty($user_id)) {    #如果user_id不为空，说明用户处在登陆状态
                # code...
                if ($user_type == 'driver') {    #如果为司机

                $Driver = M('drivers');
                $driver_id = (int)$Driver->field('id')->where('user_id=%d',$user_id)->select()['0']['id'];
                $response['content'] = M('transport_orders')
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
                $response['status'] = OK;
                
                }
                elseif($user_type == 'merchant'){

                    $Merchant = M('merchants');
                    $merchant_id = (int)$Merchant->field('id')->where('user_id=%s',$user_id)->select()['0']['id'];
                    $response['content'] = M("transport_demands")
                    ->join('lb_transport_orders ON lb_transport_demands.id = lb_transport_orders.transportDemand_id')
                    ->join('lb_drivers ON lb_transport_orders.driver_id = lb_drivers.id')
                    ->join('lb_users ON lb_drivers.user_id = lb_users.id')
                    ->field(array('lb_transport_demands.id'=>'demand_id',
                        'lb_transport_demands.status'=>'demand_status',
                        'lb_transport_orders.id'=>'order_id',
                        'lb_transport_orders.status'=>'order_status',
                        'lb_users.mobile'=>'driver_mobile',
                        'lb_users.name'=>'driver_name'))
                    ->where('lb_transport_demands.merchant_id=%s',$merchant_id)
                    ->select();
                    $response['status'] = OK;
                    
                }
                
            }
            else{
                $response['status'] = NOT_LOGGED_IN;
            }
        }
        else{
            $response['status'] = ERROR;
            $response['content'] = '空token';
        }
        $this->response($response,'json');
    }


    /*
        接单
     */
    public function takeoverByTransportDemandId()
    {
        $response['status'] = ERROR;
        $response['content'];
        $token = I('token');
        $token_data = $this->validate_token($token);
        $transportDemand_id = I('transportDemandId');
        $isAccept = I('isAccept');
        if (!empty($token) && !empty($transportDemand_id) && !empty($isAccept)) {   //如果三个值都不为空

            $Token = M('tokens');          //查找driver_id
            $map['token'] = $token;
            $user_data = $Token->field('user_id,usertype')->where($map)->select();
            $user_id = $user_data['0']['user_id'];
            if (!empty($user_id)) {     //如果user_id不为空，说明用户已登陆
                # code...
                $user_type = $user_data['0']['usertype'];
                if ($user_type == 'driver') {      #如果类型为司机
                    $driver = M('drivers');
                    $driver_id = $driver->field('id')->where('user_id=%s',$user_id)->select()['0']['id'];
                    $isFree = $driver->field('isFree')->where('user_id=%s',$user_id)->select()['0']['isfree'];

                        # code...
                        if ($isAccept=='true'){    #如果司机接收订单
                        # code...
                            echo "司机同意接收订单";
                            if (intval($isFree)!=0) {   #如果司机空闲
                            # code...

                                echo "司机状态空闲";
                                $demand = M('transport_demands');
                                $maps['id'] = $transportDemand_id;
                                $demand_status = $demand->field('status')->where($maps)->select()['0']['status'];
                                dump($demand_status);
                                
                                $orders = M('transport_orders');   //实例化订单模型
                                if ($demand_status == '未确认') {  //请求为未确认状态
                                    $orders->transportDemand_id = $transportDemand_id;   //插入order记录
                                    $orders->driver_id = $driver_id;
                                    $orders->status = '未完成';
                                    $orders->created_at = date('Y-m-d H:i:s');
                                    $orders->updated_at = date('Y-m-d H:i:s');

                                
                                    $mapper['id'] = $transportDemand_id;
                                    $result = $demand->where($mapper)->setField('status','已确认');
                                    $update = $driver->where(array('user_id'=>$user_id))->setField('isFree',0);
                                    if ($result) {
                                        echo "askfjslkfjls";
                                    }
                                    if ($orders->add() && $result) {
                                        $response['status'] = OK;
                                        $response['content'] ='订您已成功添加订单';
                                        $j_push = M('j_push_users');    #获得用户绑定的registrationID,用于推送
                                        $registrationID = $j_push->field('registrationID')
                                            ->where(array('user_id'=>$user_id))
                                            ->select()['0']['registrationID'];
                                        $content = "您的订单已被接收";  
                                        // $JPUSH = new JPushController();
                                        // $JPUSH->sendToMerchantByRegistrationID($registrationID,$content);#调用向商家推送信息函数
                                    }
                                }
                                elseif ($demand_status == '已取消') {
                                    $response['content'] = '订单已取消';
                                }
                                elseif ($demand_status == '已确认') {
                                    $response['content'] = '订单已存在';
                                }   
                            }
                            else{
                                #如果司机不空闲
                                $response['status'] = ERROR;
                                $response['content'] = '您有未完成订单，暂时不能接单';
                            }
                            
                        }
                        else   #如果拒绝接单
                        {
                            $demand = M('transport_demands');
                            $mapper['id'] = $transportDemand_id;
                            $demand_ispoint = (int)$demand->field('ispoint')->where($mapper)->select()['0']['ispoint'];
                            echo "是否指定：$demand_ispoint";
                            if ($demand_ispoint == 1) {  #如果是指定的
                                $mapper['id'] = $transportDemand_id;
                                $result = $demand->where($mapper)->setField('status','已取消');//直接取消订单
                                echo "更新结果：";
                                echo $result;
                                if (false!==$result) {
                                    # 更新成功
                                    $response['status'] = OK;
                                    $response['content'] = '您已成功拒绝订单';
                                        $j_push = M('j_push_users');    #获得用户绑定的registrationID,用于推送
                                        $registrationID = $j_push->field('registrationID')
                                            ->where(array('user_id'=>$user_id))
                                            ->select()['0']['registrationID'];
                                        $content = "您的订单已被拒绝，请您重新下单";  
                                        // $JPUSH = new JPushController();
                                        // $JPUSH->sendToMerchantByRegistrationID($registrationID,$content);#调用向商家推送信息函数
                                }
                                else{   #更新失败
                                    $response['status'] = ERROR;
                                    $response['content'] = '更新失败';
                                }
                            }
                            else{
                                $mapper['id'] = $transportDemand_id;
                                $driver = M('drivers');
                                #获取
                                #获取空闲司机列表
                                $restDrivers = $driver->field('id')->where(array('isFree'=>'1'))->select();
                                #随机生成一个数字
                                $count = count($restDrivers);
                                $index = rand(0,$count-1);
                                #选取司机id
                                $driver_id = $restDrivers[$index]['id'];
                                $data['driver_id'] = $driver_id;
                                $result = $demand->where(array('id'=>$transportDemand_id))->setField($data);
                                if (intval($result)!=0) {
                                    # 更新成功
                                    $response['status'] = OK;
                                    $response['content'] = '拒绝成功，并将订单传递给其他空闲司机';
                                }
                                else{
                                    $response['status'] = ERROR;
                                    $response['content'] = '更新呀失败';
                                }
                            }       
                        }
                    
                    
                }

            }
            else
                {
                    $response['status'] = NOT_LOGGED_IN;
            }
        }
        else{
            $response['status'] = ERROR;
            $response['content'] = '存在空值';
        }
        $this->response($response,'json');
    }

    /**
     * 完成订单
     */
    public function completeOrder_driver()
    {
        $token = I('post.token');
        $token_data = $this->validate_token($token);
        $condition['id'] = I('post.id');//查询条件
        $data['driver_ok'] = 1;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $Order = M('transport_orders');
        $Order->where($condition)->save($data);
        if($Order){
            $driver_id = $Order->where($condition)->getField('driver_id'); //获得司机id
            $driver_data['isFree'] = 1;         //将isFree 设置成1
            $driver_data['updated_at'] = date('Y-m-d H:i:s');
            $condition2['id'] = $driver_id;     //查询司机的条件
            M('drivers')->where($condition2)->save($driver_data);


            $JPush = new JPushController();

//            $condition3['user_id'] = $user_id;
            $transportDemand_id = $Order->where($condition)->getField('transportDemand_id');
            $merchant_id = M('transport_demands')->where(array('transportDemand_id'=>$transportDemand_id))
                ->getField('merchant_id');
            $user_id = M('merchants')->where(array('id' => $merchant_id))->getField('user_id');
            $registration_id = M('j_push_users')->where(array('user_id'=>$user_id))->getField('registrationID'); //得到registrationid
            $JPush->sendToMerchantByRegistrationID($registration_id,'司机已经确认订单，请您及时确认'); //推送

            $result['status'] = 'OK';
            $this->response($result,'json');
        }

    }

    /*
    查询当前需要处理的请求
     */
    public function getAllTransportDemand()
    {
        $response['status'] = ERROR;
        $response['content'];
        $token = I('token');
        if (!empty($token)) {   //检查token是否为空
            $Token = M('tokens');
            $map['token'] = $token;
            $user_data = $Token->field('user_id,usertype')->where($map)->select();
            $user_id = $user_data['0']['user_id'];
            $user_type = $user_data['0']['usertype'];
            if (!empty($user_id)) {     //检查token是否正确，以此判断是否登陆
                if ($user_type == "driver") {
                    $driver = M('drivers');
                    $maps['user_id'] = $user_id;
                    $driver_id = $driver->field('user_id')->where($maps)->select()['0']['user_id'];
                    $demand = M('transport_demands');    //实例化demand表
                    $response['content'] = $demand
                    ->join('lb_merchants ON lb_transport_demands.merchant_id = lb_merchants.id')
                    ->join('lb_cates ON lb_transport_demands.cate_id = lb_cates.id')
                    ->join('lb_users ON lb_merchants.user_id = lb_users.id')
                    ->field(array('lb_merchants.id'=>'merchant_id',
                        'lb_users.name'=>'merchant_name',
                        'lb_users.mobile'=>'merchant_mobile',
                        'lb_cates.id'=>'cate_id',
                        'lb_cates.name'=>'cate_name',
                        'lb_transport_demands.ispoint'=>'ispoint',
                        'lb_transport_demands.id'=>'demand_id'))
                    ->where('lb_transport_demands.driver_id=%s',$driver_id)
                    ->select();
                    $response['status'] = OK;
                }
            }
            else{
                $response['status'] = NOT_LOGGED_IN;
            }
        }
        else{
            $response['content'] = '空token';
        }
        $this->response($response,'json');
    }
    /*
    查询当前需要处理的订单
     */
    public function getAllTransportOrder()
    {
        $response['status'] = ERROR;
        $response['content'];
        $token = I('token');
        if (!empty($token)) {   //检查token是否为空
            $Token = M('tokens');
            $map['token'] = $token;
            $user_data = $Token->field('user_id,usertype')->where($map)->select();
            $user_id = $user_data['0']['user_id'];
            $user_type = $user_data['0']['usertype'];
            if (!empty($user_id)) {     //检查token是否正确，以此判断是否登陆
                if ($user_type == "driver") {
                    $driver = M('drivers');
                    $maps['user_id'] = $user_id;
                    $driver_id = $driver->field('id')->where($maps)->select()['0']['id'];
                    // dump($driver_id);
                    $Order = M('transport_orders');
                    // $status = "未完成";
                    $mapper['lb_transport_orders.driver_id'] = $driver_id;    //查找该driver_id的未完成订单
                    $mapper['lb_transport_orders.status'] = '未完成';
                    $response['content'] = $Order
                    ->join('lb_transport_demands ON lb_transport_orders.transportDemand_id = lb_transport_demands.id')
                    ->join('lb_cates ON lb_transport_demands.cate_id = lb_cates.id')
                    ->join('lb_merchants ON lb_transport_demands.merchant_id = lb_merchants.id')
                    ->join('lb_users ON lb_merchants.user_id = lb_users.id')
                    ->field(array('lb_transport_demands.merchant_id'=>'merchant_id',
                        'lb_users.name'=>'merchant_name',
                        'lb_users.mobile'=>'merchant_mobile',
                        'lb_transport_demands.cate_id'=>'cate_id',
                        'lb_cates.name'=>'cate_name',
                        'lb_transport_demands.id'=>'demand_id',
                        'lb_transport_orders.id'))
                    ->where($mapper)
                    ->select();
                    // dump($response);
                    $response['status'] = OK;
                }
            }
            else{
                $response['status'] = NOT_LOGGED_IN;
            }
        }
        else{
            $response['content'] = '空token';
        }

            $this->response($response,'json');
    }


    /**
     * 判断司机是否在地理围栏里面
     *
     * 还需要将token 的验证模块加进来
     */
    public function driverIsInMarket()
    {
        $token = I('token');
        $this->validate_token($token);
        $pointX = I('longitude');//X坐标
        $pointY = I('latitude');//Y坐标

        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];
        $user_id = $tokenData['user_id'];//用户id
        //获取到用户的id之后 需要知道车主是在哪一个市场
        $driversData = M('drivers')->field('market_id')->where(array('user_id' => $user_id))->select()[0];
        $market_id = $driversData['market_id'];//市场的id
        $marketData = M('markets')->where(array('id' => $market_id))->select()[0];
        //获取到市场的经度和纬度
        $marketX = $marketData['lon'];//经度
        $marketY = $marketData['lat'];//纬度
        $radius = $marketData['radius'];

        $data['marketX'] = $marketX;
        $data['marketY'] = $marketY;
        $data['marketRadius'] = $radius;
        $data['marketDistance'] = $this->getDistance($pointY, $pointX, $marketY, $marketX);
        $data['isIn'] = $radius > $data['marketDistance'];

        $result['status'] = OK;
        $result['content'] = $data;
        $this->response($data,'json');
    }

    /**
     * 返回圆周率
     * @param $d
     * @return string
     */
    private function rad($d)
    {
        return floatval(floatval($d) * pi() / 180.0);
    }

    /**
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @return mixed
     */
    private function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $radLat1 = $this->rad($lat1);
        //echo $lat1."  ".$radLat1;
        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * 6378.137;
        $s = round($s * 10000) / 10000;
        return $s;
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
        move_uploaded_file($_FILES[$avatar_name]['tmp_name'], "./upload/".$token.".png");
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

        return "http://banar-image.oss-cn-beijing.aliyuncs.com/".$token. ".png";
    }

    public function getUrlByAvatar($key)
    {
        $client = OSSClient::factory(array(
            'AccessKeyId' => 'PdUWUlXoZ0iS05hF',
            'AccessKeySecret' => 'nsMLg5QRScXirbW6UGL9Ec6VGqP2VV',
        ));

        $url = $client->generatePresignedUrl(array(
            'Bucket' => 'banar-image',
            'Key' => $key,
            'Endpoint' => 'http://oss-cn-beijing.aliyuncs.com',
            'Expires' => new \DateTime("+5 minutes"),
        ));
        return $url;
    }


    public function addContacts(){
        $response['status'] = ERROR;
        $response['content'];
        $name = I('name');
        $content = I('content');
        $address = I('address');
        if (!empty($name) && !empty($content) && !empty($address)) {
            # 如果不存在空值
            $contact = M('contacts');
            $data = array(
                'name' => $name,
                'content' => $content,
                'address' => $address
                );
            $contact->add($data);
            if ($contact) {
                #如果插入成功
                $response['status'] = OK;
                $response['content'] = '插入成功';
            }
            else{
                $response['status'] = ERROR;
                $response['content'] = '插入失败';
            }

        }
        else {
            # 如果存在空值
            $response['status'] = ERROR;
            $response['content'] = '存在空值';
        }
        $this->response($response,'json');
    }

}