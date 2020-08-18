<?php


namespace app\common\model;
use think\Model;

class AdminRole extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

}
