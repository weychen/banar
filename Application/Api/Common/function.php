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
 * @param $e
 * @return object|void
 * 将数组转化为对象
 */
function arrayToObject($e){
    if( gettype($e)!='array' ) return;
    foreach($e as $k=>$v){
        if( gettype($v)=='array' || getType($v)=='object' )
            $e[$k]=(object)arrayToObject($v);
    }
    return (object)$e;
}



