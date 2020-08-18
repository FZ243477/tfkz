<?php


namespace app\seller\model;


use think\Db;
use think\Model;

class Goods extends Model
{
    public function getShopIdAttr($val){
        return Db::name('shop')->where(['id'=>$val])->find();
    }
    public function getGoodsKeyIdAttr($val){
        return Db::name('goods_key')->where(['id'=>$val])->find();
    }
    public function getPcImgAttr($val){
        return json_decode($val);
    }
}