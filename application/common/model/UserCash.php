<?php


namespace app\common\model;
use think\Model;

class UserCash extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getUidAttr($value){
        $user_info=db('users')->where('id',$value)->find();
        $user_info['type']="买手";
        return $user_info;
    }

    public  function getTypeAttr($value){
        $type_array=array(
            '1'=>"押金",
            '2'=>"银锭",
        );
        return $type_array[$value];
    }
}
