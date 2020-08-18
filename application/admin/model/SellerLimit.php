<?php


namespace app\admin\model;
use think\Model;

class SellerLimit extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getSellerIdAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        return $user_info;
    }

    public  function getStateAttr($value){
        $type_array=array(
            '0'=>"永久拉黑",
            '1'=>"限时拉黑",
        );
        return $type_array[$value];
    }


    public  function getStatusAttr($value){
        $type_array=array(
            '0'=>"待审核",
            '1'=>"审核通过",
            '2'=>"拒绝",
        );
        return $type_array[$value];
    }

    public  function getEndTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '暂无';
    }

    public  function getUpdateTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '暂无';
    }



}
