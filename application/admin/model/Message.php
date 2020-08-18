<?php


namespace app\admin\model;
use think\Model;


class Message extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    public  function getAdminIdAttr($value){
        $name=db('admin_user')->where('id',$value)->value('user_name');
        return $name;
    }
}

