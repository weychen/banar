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
        'Trucks' => array(
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'Trucks',
            'foreign_key' => 'driver_id'

        ),
    );
}