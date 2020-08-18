<?php


namespace app\common\model;
use think\Model;

class SellerTask extends Model
{
    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getTimingTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'无';
    }


    public  function getPublishTimeAttr($value){
    return $value?date('Y-m-d H:i:s',$value):'无';
}

    public  function getYsTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'无';
    }

    public  function getIsYsAttr($value){
        $type_array=array(
            '0'=>"否",
            '1'=>"是",
        );
        return $type_array[$value];
    }

    public  function getIsFreeShipingAttr($value){
        $type_array=array(
            '1'=>"包邮",
            '2'=>"不包邮",
            '3'=>"无需物流",
        );
        return $type_array[$value];
    }

    //店铺信息
    public  function getShopIdAttr($value){
        $shop_info=db('shop')->where('id',$value)->find();
        $type_array=array(
            '1'=>"淘宝",
            '2'=>"天猫",
            '3'=>"飞猪",
        );
        $shop_info['shop_type']=$type_array[$shop_info['type']];
        if($shop_info['logistics']==1){
            $shop_info['logistics']="不包邮";
        }else{
            $shop_info['logistics']="无需物流";
        }
        return $shop_info;
    }

    //商家信息
    public  function getSellerIdAttr($value){
        $seller_info=db('seller')->where('id',$value)->find();
        return $seller_info;
    }

    //终端
    public  function getTerminalAttr($value){
        $type_array=array(
            '1'=>"pc",
            '2'=>"手机",
        );
        return $type_array[$value];
    }

    //订单类型
    public  function getTaskTypeAttr($value){
        $type_array=array(
            '1'=>"文字好评",
            '2'=>"淘口令",
            '3'=>"二维码",
            '4'=>"直通车",
            '5'=>"通道任务",
        );
        return $type_array[$value];
    }


}
