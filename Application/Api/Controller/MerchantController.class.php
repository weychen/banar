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
        $token_data = $this->validate_token($token);
        $cate_id = I('post.cate_id');
        $merchant = $token_data['user_id'];
        // 用join 查询来查找出所需要的信息
        echo $token_data[user_id];
        echo "<br />";
        //收藏的司机的id
        $favorites_id = M('merchant_favorites')
            ->where(array('merchant_id' => $token_data[user_id]))->getField('driver_id',true);
        //查询收藏的结果集
        $map1['lb_drivers.id']  = array('in',$favorites_id);

        $data2 = M('drivers')->field('lb_drivers.id, lb_users.name, lb_users.mobile, lb_drivers.isFree')
            ->join('lb_trucks on lb_drivers.id = lb_trucks.driver_id')
            ->join('lb_users on lb_drivers.user_id = lb_users.id')
            ->where('lb_trucks.cate_id = 1')->where($map1)->order()
            ->select();
        //查询非收藏的结果集
        $map2['lb_drivers.id']  = array('not in',$favorites_id);
        $data3 = M('drivers')->field('lb_drivers.id, lb_users.name, lb_users.mobile, lb_drivers.isFree')
            ->join('lb_trucks on lb_drivers.id = lb_trucks.driver_id')
            ->join('lb_users on lb_drivers.user_id = lb_users.id')
            ->where('lb_trucks.cate_id = 1')->where($map2)->order()
            ->select();
        $data = array_merge($data2,$data3);

        $result['status'] = "OK";
        $result['content'] = $data;
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
            $result['status'] = 'error';
            $result['content'] = 'token is error';
            $this->response($result, 'json');
        }else {
            $token_updated_time = $token_data['updated_at'];
            if(strtotime("$token_updated_time +2 day") - strtotime(date("Y-m-d H:i:s")) < 0)
            {
                //token 已经过期,销毁token
                M('tokens')->where($condition)->delete();
                $result['status'] = 'error';
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