<?php


namespace app\common\model;
use think\Model;


class UserBuyno extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    //vip到期时间
    public function getVipTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    //vip到期时间
    public function getFrozenTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    //是否是vip
//    public function getStateAttr($value){
//        if($value ==0){
//            return '未审核';
//        }else if($value ==1){
//            return '审核通过';
//        }else if($value ==2){
//            return '已禁用';
//        }else if($value ==3){
//            return '已删除';
//        }
//    }
    //余额银锭
    //public function
    public  function getUidAttr($value){
        $user_info=db('users')->where('id',$value)->find();
        $user_info['type']="买手";
        return $user_info;
    }
}

