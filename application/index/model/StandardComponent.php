<?php
/**
 * Created by PhpStorm.
 * User: 10728
 * Date: 2018/4/9
 * Time: 12:00
 */

namespace app\index\model;


use think\Db;
use think\Model;

class StandardComponent extends Model
{
    /**
     * 判断是否为标准件
     * @param $gt_sn    序列号
     * @param $type     系统类型
     * @return int
     */
    public static function isStandard($gt_sn, $type){
        $standard_info = Db::name('standard_component')->where(['gt_sn' => $gt_sn, 'type' => ['EXP', "=" . (is_numeric($type)?(int)$type:"'$type'")]])->find();
//        echo Db::name('standard_component')->getLastSql();die;
        return empty($standard_info) ? 0 : 1;
    }
}