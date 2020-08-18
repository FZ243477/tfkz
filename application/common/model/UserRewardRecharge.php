<?php


namespace app\common\model;


use think\Model;

class UserRewardRecharge extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    public  function getUidAttr($value){
        $user_info=db('users')->where('id',$value)->find();
        $user_info['type']="买手";
        return $user_info;
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
}
