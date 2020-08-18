<?php


namespace app\seller\model;
use think\Model;

class SellerCash extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    public  function getStateAttr($value){
        $arr = [
            '待审核','已审核待转账','拒绝','转账成功'
        ];
        return $arr[$value];
    }

    public  function getUidAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        $user_info['type']="商家";
        $user_info['username']=$user_info['seller_name'];
        return $user_info;
    }

    public  function getMemoAttr($value){
        return $value ? $value : '无';
    }
}
