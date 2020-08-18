<?php


namespace app\common\model;
use think\Model;

class Notice extends Model
{
public  function getCreateTimeAttr($value){
    return date('Y-m-d H:i:s',$value);
}

    public  function getContentAttr($value){
        return htmlspecialchars_decode($value);
    }
}
