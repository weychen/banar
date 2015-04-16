<?php
/**
 * @return string
 * 生成token 的函数
 */
function generate_token()
{
    $token = md5(microtime(true));
    return $token;
}

function get_user($key,$value){
    $user = null;
    if(!empty($key) && !empty($value)){
        $User = M('Users');
        $user = $User->where($key . " ='%s'",array($value))
            ->limit(1)
            ->select();
    }
    return empty($user) ? $user : $user[0];
}

/**
 * @param $token        $token值
 * @param $user_type    $用户类型
 * @param $user_id      $用户id
 *
 */
function put_token_into_sql($token, $user_type, $user_id)
{
    $token_data = array(
        'token' => $token,
        'userType' => $user_type,
        'user_id' => $user_id,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    );
    D('tokens')->add($token_data);
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



