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

            $truck_data = array(
                'driver_id' => $driver_id,
                'plateId' => I('post.truck_plateId'),
                'cate_id' => I('post.truck_cate_id'),
                'avatar' => I('post.truck_avatar'),
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

    /**
     * 判断司机是否在地理围栏里面
     */
    public function driverIsInMarket()
    {
        $token = I('token');
        $pointX = I('pointX');//X坐标
        $pointY = I('pointY');//Y坐标

        $condition['token'] = $token;
        $tokenData = M('tokens')->field('userType,user_id')
            ->where($condition)->select()[0];
        $user_id = $tokenData['user_id'];//用户id
        //获取到用户的id之后 需要知道车主是在哪一个市场
        $driversData = M('drivers')->field('market_id')->where(array('user_id' => $user_id))->select()[0];
        $market_id = $driversData['market_id'];//市场的id
        $marketData = M('markets')->where(array('id' => $market_id))->select()[0];
        //获取到市场的经度和纬度
        $marketX = $marketData['lat'];//经度
        $marketY = $marketData['lon'];//纬度
        $radius = $marketData['radius'];

        $data['marketX'] = $marketX;
        $data['marketY'] = $marketY;
        $data['marketRadius'] = $radius;
        $data['marketDistance'] = $this->getDistance($pointY, $pointX, $marketY, $marketX);
        $data['isIn'] = $radius > $data['marketDistance'];

        $this->response($data,'json');
    }

    /**
     * 返回圆周率
     * @param $d
     * @return string
     */
    private function rad($d)
    {
        return floatval(floatval($d) * pi());
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
}