<?php
/**
 * Created by PhpStorm.
 * User: niuwei
 * Date: 15/4/17
 * Time: 14:36
 */

namespace Api\Controller;
use Think\Controller\RestController;



require_once './Application/vendor/autoload.php';
use JPush\Model as M;
use JPush\JPushClient;

class JPushController extends RestController{
    //商户appKay
    public $merchant_appKey = "868cb71e4bf509eceb859d20";
    public $merchant_secret = "d21e8793170e4abdecec17fc";
    //司机appKey
    public $driver_appKey = "52850fe9e445eaf69bbb1b13";
    public $driver_secret = "79dfe4dd25a796b74fbb4af8";

    /**
     * 将消息发送到指定的商户
     * @param $registration_id
     * @param $content
     */
    public function sendToMerchantByRegistrationID($registration_id, $content, $transport_demandId, $mobile) {
        $client = new JPushClient("868cb71e4bf509eceb859d20", "d21e8793170e4abdecec17fc");
        $response = $client->push()->setPlatform(M\all)
            ->setAudience(M\audience(M\registration_id(array($registration_id))))
            ->setNotification(M\notification($content))
            ->setMessage(M\message($content, null, null, array(
                'transportDemand_id' => $transport_demandId,
                'mobile' => $mobile)))
            ->send();

        $this->assertTrue($response->isOk === true);
    }

    /**
     * 将消息发送到指定的司机
     * @param $registration_id
     * @param $content
     */
    public function sendToDriverByRegistrationID($registration_id, $content, $transport_demandId, $mobile) {
        $client = new JPushClient($this->driver_appKey, $this->driver_secret);
        $response = $client->push()->setPlatform(M\all)
            ->setAudience(M\audience(M\registration_id(array($registration_id))))
            ->setNotification(M\notification($content))
            ->setMessage(M\message($content, null, null, array(
                'transportDemand_id' => $transport_demandId,
                'mobile' => $mobile)))
            ->send();

        $this->assertTrue($response->isOk === true);
    }
}