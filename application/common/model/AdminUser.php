<?php


namespace app\common\model;
use think\Model;

class AdminUser extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getLoginsTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getRoleIdAttr($value){
        $name=db('admin_role')->where('id',$value)->value('name');
        return $name;
    }
}
