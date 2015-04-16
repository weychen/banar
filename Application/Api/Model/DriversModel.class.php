<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 15/4/15
 * Time: 下午4:35
 */

namespace Api\Model;
use Think\Model\RelationModel;

class DriversModel extends RelationModel {

    protected $_link = array(
        'Users' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Users',
            'foreign_key' => 'user_id'
        ),
        'Trucks' => array(
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'Trucks',
            'foreign_key' => 'driver_id'
        ),

    );
}