<?php


namespace app\admin\model;
use think\Model;

class UserCash extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getUidAttr($value){
        $user_info=db('users')->where('id',$value)->find();
        $user_info['type']="买手";
        return $user_info['username'];
    }
    public  function getStateAttr($value){
        $arr = [
            '已申请','已同意','已拒绝','已打款'
        ];
        return $arr[$value];
    }
    public  function getUserTypeAttr($value){
        $type_array=array(
            '1'=>"商家",
            '2'=>"买手",
        );
        return $type_array[$value];
    }
    public  function getBankNumberAttr($value){

        return $value;
    }

    public  function getTypeAttr($value){
        $type_array=array(
            '1'=>"本金",
            '2'=>"银锭",
        );
        return $type_array[$value];
    }
}
