<?php
return array(

        // restful router
        'URL_MODEL'         => 1,
        'URL_ROUTER_ON'  => true,
        'URL_ROUTE_RULES' => array(
            /**
             * 公用接口
             *      1.获取车型
             * 状态:
             *  已完成 weychen
             * 测试状态
             *      已通过 weychen
             * $_GET:
             *  [ { id, name }]
             */
            array('cate/getAllCates', 'Communal/getAllCates', array('method' => 'GET')),
            /**
             * 公用接口
             *      2.获取市场
             * 状态:
             *  已完成 weychen
             * 测试状态
             *      已通过 weychen
             * $_GET:
             *  [ {id, name, address}]
             */
            array('market/getAllMarkets', 'Communal/getAllMarkets', array('method' => 'GET')),


            /**
             * 司机接口接口
             *      1.司机注册
             * 状态
             *      基本已完成 weychen
             * 测试状态
             *      已通过     weychen
             *
             *
             * $_POST:
             *  [mobile, password, name, market_id, avatar, icld, truck_cate_id, truck_plateld, truck_avatar]
             *
             * return:
             *  {
             *      status: true|false
             *      content: token
             *  }
             */
            array('user/driverRegister', 'User/driverRegister', array('method' => 'POST')),

            /**
             * 司机接口
             *      2.司机登录
             * $_POST:
             *  [mobile, password]
             *
             * 状态:
             *  基本已完成， weychen
             *
             * return:
             *  {
             *      token: __hash__
             *  }
             */
            array('user/driverLogin', 'User/driverLogin', array('method' => 'POST')),


            /**
             *  公共接口
             *      3.获取历史订单
             *  状态：
             *        已完成
             *        魏星
             *  测试:
             * $_POST:
             *  [token]
             *如果是司机则：
             * return:
             *  {
             *        status,
             *        content[{
             *              merchant_name,
             *              merchant_avatar,
             *              cate_name,
             *              time
             *        }]
             *      
             *  }
             *如果是商户则：
             * return:
             *  {
             *        status,
             *        content[{
             *              demand_id,
             *              demand_status,
             *              order_id,
             *              order_status,
             *              driver_mobile,
             *              driver_name
             *        }]
             *        
             *  }
             */
            array('transportOrder/getAllMyTransportOrder', 'User/getAllMyTransportOrder', array('method' => 'POST')),

            /**
             *  司机接口
             *      4.获取个人信息
             *  状态:
             *      已完成 weychen
             * $_POST:
             *  [token]
             *
             * return:
             *  {
             *      id,
             *      name,
             *      avatar,
             *  }
             */
            array('user/getMyProfile', 'User/getMyProfile', array('method => POST')),

            /**
             *  司机接口
             *      5.绑定极光推送
             *   状态：
             *         已完成
             *         魏星
             *         现在已经不是一个接口了
             * $_POST:
             *  [token,registrationID]
             *
             * return:
             *  {
             *      status: OK|ERROR|NOT_LOGGED_IN
             *  }
             */
            array('user/bindJPushRegistrationID', 'User/bindJPushRegistrationID', array('method => POST')),

            /**
             *  司机接口
             *      6.接单
             *   状态：
             *         已完成
             *         魏星
             * $_POST:
             *  [token,transportDemandId,isAccept]
             *
             * return:
             *  {
             *      status,
             *      content
             *  }
             */
            array('transportOrder/takeoverByTransportDemandId', 'User/takeoverByTransportDemandId', array('method => POST')),

            /**
             *  司机接口
             *      7.司机完成订单
             *  状态:
             *      weychen
             *  $_POST:
             *  [token, order_id]
             *
             * return :
             * {
             *      status: true | false
             * }
             */
            array('transportOrder/completeOrder_driver', 'User/completeOrder_driver', array('method' => 'POST')),

            /**
             *  商户接口
             *      1.商户注册
             *  状态:
             *      已完成  weychen
             *
             * $_POST:
             *  [mobile, password, name, market_id, avatar, address, telephone]
             *
             * return:
             *  {
             *      token: __HASH__
             *  }
             */
            array('user/merchantRegister', 'Merchant/merchantRegister', array('method => POST')),

            /**
             * 商户接口
             *      2.商户登录
             * 状态:
             *      已完成  weychen
             * $_POST:
             *  [mobile, password]
             *
             * return:
             *  {
             *      token: __HASH__
             *  }
             */
            array('user/merchantLogin', 'Merchant/merchantLogin', array('method => POST')),

            /**
             * 商户接口
             *      3.商户发起用车请求
             * 状态:
             *      正在完成 牛威
             * $_POST:
             *  [token, cate_id, driver_id]
             *
             * return:
             *  {
             *
             *  }
             */
            array('transportDemand/addTransportDemand','Merchant/postATransportDemand', array('method => POST')),
            /*
             * 商户接口
             *      商户发起用车请求，自动分配司机
             * $_POST:
             * [token,cate]
             *
             * return:{
             *
             * }
             *
             * */
            array('transportDemand/addTransportDemandAuto','Merchant/postATransportDemandByAuto',array('method => POST')),
            /**
             * 商户接口
             *      4.取消请求
             * $_POST:
             *  [token, id]
             *
             * return:
             *  {
             *  }
             */


            array('transportDemand/cancelTransportDemandById','Merchant/cancelTransportDemandById',array('method'=>'POST')),

            /*
             *商户接口
             *      5.获取所有请求
             * $_POST:
             * [token]
             *
             * return:
             * {
             *     请求id，cate_id，driver_name(请求详情)
             * }
             * */
            array('transportDemand/getAllTransportDemand','User/getAllTransportDemand',array('method'=>'POST')),



            /**
             * 商户接口
             *      6.获取收藏夹
             * 完成 牛威
             * $_POST:
             *  [token]
             *
             * return:
             *  {
             *      id,
             *      merchant_id,
             *      driver_id,
             *      name,
             *      mobile
             *  }
             */
            array('favorite/getMyFavorites', 'Favorite/getFavorites', array('method' => 'POST')),

            /**
             * 商户接口
             *      7.添加收藏夹
             * 完成 牛威
             * $_POST:
             *  [token,driver_id]
             *
             * return:
             *  {
             *
             *  }
             */
            array('favorite/addFavorite', 'Favorite/addFavorite', array('method' => 'POST')),

            /**
             * 商户接口
             *      8.删除收藏夹
             * 完成 牛威
             * $_POST:
             *  [token, id]
             *
             */
            array('favorite/deleteFavoriteById', 'Favorite/deleteFavoriteById', array('method' => 'POST')),

            /**
             * 商户接口
             *      9.获取历史订单
             * 状态：
             *       已完成
             *       魏星
             * $_POST:
             *  [token]
             *
             * return:
             *  {
             *        status,
             *        content:[{
             *              demand_id,
             *              demand_status,
             *              order_id,
             *              order_status,
             *              driver_mobile,
             *              driver_name
             *        }]
             *      
             *  }
             */
            array('transportOrder/getAllMyTransportOrder', 'User/getAllMyTransportOrder', array('method' => 'POST')),

            /**
             * 商户接口
             *      10.获取司机
             * 状态:
             *      完成  weychen
             *
             * $_POST:
             *  [token, cate_id]
             * return:
             *  {
             *      id,
             *      name,
             *      mobile,
             *      isFree
             *  }
             */
            array('driver/getDriversByCateId', 'Merchant/getDriversByCateId', array('method' => 'POST')),

            /**
             *  商户接口
             *      11.确认已完成订单
             *  状态:
             *      weychen
             *  $_POST:
             *  [token, order_id]
             *
             * return :
             * {
             *      status: true | false
             * }
             */
            array('transportOrder/completeOrder_merchant', 'Merchant/completeOrder_merchant', array('method' => 'POST')),


            /**
             * 判断车主是否在地理围栏的位置当中
             * 状态:
             *  完成 牛威
             * $_POST:
             *  [token, pointX, pointY]
             * return:
             * {
             *      isIn
             * }
             */
            array('driver/driverIsInMarket', 'User/driverIsInMarket', array('method' => 'POST')),

             /**
             * 司机接口
             * 返回当前司机需要处理的订单
             * 状态:
             *      已完成
             *      魏星
             * $_POST:
             *  [token]
             * return:
             * {
             *      status,
             *      content:[{
             *                  merchant_id,
             *                  merchant_name,
             *                  merchant_number,
             *                  cate_id,
             *                  cate_name,
             *                  demand_id,
             *                  order_id 
             *                  }]
             * }
             */
            array('transportOrder/getAllTransportOrder','User/getAllTransportOrder',array('method' => 'POST')),

             /**
             * 司机接口
             * 返回当前司机需要处理的请求，同时处理包括拒绝和接受
             * 状态:
             *      已完成
             *      魏星
             * $_POST:
             *  [token]
             * return:
             * {
             *      status,
             *      content:[{
             *                  merchant_id,
             *                  merchant_name,
             *                  merchant_mobile,
             *                  cate_id,
             *                  cate_name,
             *                  ispoint,
             *                  demand_id
             *                  }]
             * }
             */
            array('transportOrder/getAllTransportDemand','User/getAllTransportDemand',array('method'=>'POST')),
        ),

     // 默认数据库配置,本地
    
    'DB_TYPE'       =>  'mysql',
    'DB_HOST'       =>  'localhost',
    'DB_NAME'       =>  'banar',
    'DB_USER'       =>  'root',
    'DB_PWD'        =>  '100693',
    'DB_PORT'       =>  '3306',
    'DB_PREFIX'     =>  'lb_',    // 数据库表前缀
);
