<?php


namespace app\common\model;
use think\Model;


class SellerDepositRecharge extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * @notes 财务类型显示
     * @date 2019/8/10
     * @time 11:50
     * @param $value
     * @return string
     */
    public  function getTypeAttr($value){
        $arr = config('finance.financeClass');
        return isset($arr[$value]) ? $arr[$value] : '未知财务类型'.$value;
    }

    //余额银锭
    public  function getUidAttr($value){
        $user_info=db('seller')->where('id',$value)->find();
        $user_info['type']="商家";
        return $user_info;
    }
}
