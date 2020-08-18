<?php


namespace app\seller\model;


use think\Db;
use think\Model;

class SellerTaskPraise extends Model
{

    /**
     * @notes 解析图片
     * @date 2019/10/14
     * @time 9:46
     * @param $val
     * @return mixed
     */
    public function getContentAttr($val)
    {
        return json_decode($val);
    }
}