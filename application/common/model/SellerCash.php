<?php


namespace app\common\model;
use think\Model;

class SellerCash extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getUidAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        $user_info['type']="商家";
        $user_info['username']=$user_info['seller_name'];
        return $user_info;
    }


    public  function getTypeAttr($value){
        $type_array=array(
            '1'=>"押金",
            '2'=>"银锭",
        );
        return $type_array[$value];
    }
    public  function getMemoAttr($value){
        return $value ? $value : '无';
    }
}
