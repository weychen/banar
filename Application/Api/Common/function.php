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
