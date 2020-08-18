<?php


namespace app\common\model;
use think\Model;

class AdminLog extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getAdminIdAttr($value){
        $id=db('admin_user')->where('id',$value)->field('id,user_name,name')->find();
        return $id;
    }
}
