<?php


namespace app\admin\model;
use think\Model;


class Seller extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    //vip到期时间
    public function getVipTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    //是否是vip
//    public function getVipAttr($value){
//        if($value ==1){
//            return '是';
//        }else{
//            return '否';
//        }
//    }

//推荐人
    public function getTjuserAttr($value){
        return $value?$value:'无';
    }

    //余额银锭
    //public function
    public  function getUidAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        $user_info['type']="商家";
        return $user_info;
    }
}

