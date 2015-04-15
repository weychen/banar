<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/15
 * Time: 上午9:20
 */

namespace Api\Model;

use Think\Model\RelationModel;

class UsersModel extends RelationModel{
    protected $tableName = 'users';
    protected $_link = array(

    );
    protected $_validate = array(
        array('mobile','','帐号名称已经存在！',0,'unique',1), // 在新增的时候验证mobile字段是否唯一
    );
}