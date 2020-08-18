<?php


namespace app\seller\model;


use think\Db;
use think\Model;

class UserTask extends Model
{

    public function getStateAttr($name)
    {
        $arr = [
            '正在进行','已完成','已取消','待发货','待收货','待返款','已返款待确认'
        ];
        return $arr[$name];
    }
    public function getSellerTaskIdAttr($name)
    {
        $list = Db::name('seller_task')->where(['id'=>$name])->field('task_number,task_type')->find();
        $arr = [
            '1'=>'关键词',
            '2'=>'淘口令',
            '3'=>'二维码',
            '4'=>'关键词',
            '5'=>'通道',
        ];
        $list['task_type'] = $arr[$list['task_type']];
        return $list;
    }
}