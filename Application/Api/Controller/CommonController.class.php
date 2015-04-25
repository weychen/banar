<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/24
 * Time: 下午12:19
 */

namespace Api\Controller;

use Think\Controller\RestController;
use Api\Model\UsersModel;
use JPush\JPushClient;
require_once MODULE_PATH. "aliyun-php/aliyun.php";
use \Aliyun\OSS\OSSClient;
use Think\Model;

class CommonController extends RestController {
    /**
     * @return string
     * 把图像上传到云端
     */
    public function put_pic_to_oss($avatar_name)
    {

        $token = generate_token();
        if(!move_uploaded_file($_FILES[$avatar_name]['tmp_name'],"./upload/".$token.".png" ))
        {
//            $result['status'] = "ERROR";
//            $result['content'] = "图片上传失败";
//            $this->response($result, 'json');
            $avatar_data = false;
            return $avatar_data;
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

}