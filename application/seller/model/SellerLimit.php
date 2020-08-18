<?php


namespace app\seller\model;


use think\Model;

class SellerLimit extends Model
{
    public function getCreateTimeAttr($name)
    {
        return $name ? date("Y-m-d H:i",$name) : '暂无数据';
    }
    public function getEndTimeAttr($name)
    {
        return $name ? date("Y-m-d H:i",$name) : '暂无数据';
    }
    public function getUpdateTimeAttr($name)
    {
        return $name ? date("Y-m-d H:i",$name) : '暂无数据';
    }
    public function getStatusAttr($name)
    {
        $arr = [
            0=>'待审核',
            1=>'已通过',
            2=>'已拒绝',
        ];
        return $arr[$name];
    }

}