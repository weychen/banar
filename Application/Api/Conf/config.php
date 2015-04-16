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
             * $_GET:
             *  [ { id, name }]
             */
            array('cate/getAllCates', 'Communal/getAllCates', array('method' => 'GET')),
            /**
             * 公用接口
             *      2.获取市场
             * 状态:
             *  已完成 weychen
             * $_GET:
             *  [ {id, name, address}]
             */
            array('market/getAllMarkets', 'Communal/getAllMarkets', array('method' => 'GET')),


            /**
             * 司机接口接口
             *      1.司机注册
             * 状态
             *  基本已完成，需要重构 weychen
             *
             * $_POST:
             *  [mobile, password, name, market_id, avatar, icld, truck_cate_id, truck_plateld, truck_avatar]
             *
             * return:
             *  {
             *      status: true|false
             *      token:  __hash__
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
             *  基本已完成，需要重构 weychen
             *
             * return:
             *  {
             *      token: __hash__
             *  }
             */
            array('user/driverLogin', 'User/driverLogin', array('method' => 'POST')),


            /**
             *  司机接口
             *      3.获取历史订单
             *  状态：
             *        已完成
             *        魏星
             * $_POST:
             *  [token]
             *
             * return:
             *  {
             *      merchant_name,
             *      merchant_avatar,
             *      cate_name
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
             *      avatar
             *  }
             */
            array('user/getMyProfile', 'User/getMyProfile', array('method => POST')),

            /**
             *  司机接口
             *      5.绑定极光推送
             *   状态：
             *         已完成
             *         魏星
             * $_POST:
             *  [token,registrationID]
             *
             * return:
             *  {
             *      status: true|false
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
             *  [token,transportDemandId]
             *
             * return:
             *  {
             *      status: true|false
             *  }
             */
            array('transportOrder/takeoverByTransportDemandId', 'User/takeoverByTransportDemandId', array('method => POST')),



            /**
             *  商户接口
             *      1.商户注册
             *  状态:
             *      基本已完成,需要重构  weychen
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
             *      基本已完成，需要重构  weychen
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
             *      3.发起用车请求
             *
             * $_POST:
             *  [token, cate_id, driver_id]
             *
             * return:
             *  {
             *
             *  }
             */
            array('transportDemand/addTransportDemand'),

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
            array('transportDemand/cancelTransportDemandById'),

            /**
             * 商户接口
             *      5.完成订单
             * $_POST:
             *  [token, id]
             */
            array('transportOrder/finishTransportOrderById', ),



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
             * $_POST:
             *  [token]
             *
             * return:
             *  {
             *      demand_id,
             *      demand_status,
             *      order_id,
             *      order_status,
             *      driver_mobile,
             *      driver_name,
             *  }
             */
            array('transportOrder/getAllMyTransportOrder',),

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
        ),

     // 默认数据库配置,本地
    
    'DB_TYPE'       =>  'mysql',
    'DB_HOST'       =>  'localhost',
    'DB_NAME'       =>  'banar',
    'DB_USER'       =>  'root',
    'DB_PWD'        =>  'root',
    'DB_PORT'       =>  '3306',
    'DB_PREFIX'     =>  'lb_',    // 数据库表前缀
);
