<?php


namespace app\common\model;
use think\Model;

class SellerBank extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    //会员名
    public  function getUidAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        return $user_info;
    }

    //状态
    public  function getStateAttr($value){
        $type_array=array(
            '0'=>"未审核",
            '1'=>"正常使用",
            '2'=>"审核不通过",
        );
        return $type_array[$value];
    }

    //银行名称
    public  function getBankIdAttr($value){
      $bank_name=db('bank')->where('id',$value)->value('name');
        return $bank_name;
    }
}
