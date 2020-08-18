<?php


namespace app\admin\model;
use think\Model;


class Shop extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    //vip到期时间
    public function getVipTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    //余额银锭
    //public function
    public  function getSellerIdAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        $user_info['type']="商家";
        return $user_info;
    }
}

