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
