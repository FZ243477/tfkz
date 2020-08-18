<?php


namespace app\common\model;
use think\Model;

class ReviewTask extends Model
{

    public  function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    public  function getConfirmTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'无';
    }
    public  function getSignForTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    public  function getStepTwoCompleteTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

    public  function getHighPraiseTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'无';
    }

    public  function getKeywordimgAttr($value){ //关键词默认截图
        if($value){
            return $value;
        }else{
            $value="/uploads/timg.jpg";
            return $value;
        }
    }

    public  function getChatimgAttr($value){ //默认聊天截图
        if($value){
            return $value;
        }else{
            $value="/uploads/timg.jpg";
            return $value;
        }
    }

    public  function getOrderDetailImgAttr($value){ //默认付款截图
        if($value){
            return $value;
        }else{
            $value="/uploads/timg.jpg";
            return $value;
        }
    }


    public  function getHighPraiseImgAttr($value){ //默认好评截图
        if($value){
            return $value;
        }else{
            $value="/uploads/timg.jpg";
            return $value;
        }
    }

    //状态
    public  function getDeliveryStateAttr($value){
        $type_array=array(
            '0'=>"未发货",
            '1'=>"已录入快递单号，待确认发货",
            '2'=>"已发货",
            '3'=>"已签收",
        );
        return $type_array[$value];
    }
    //状态
    public  function getStateAttr($value){
        $type_array=array(
            '0'=>"未支付",
            '1'=>"已支付",
            '2'=>"已审核",
            '3'=>"已上传",
            '4'=>"已完成",
            '5'=>"已取消",
            '6'=>"买手拒接",
            '7'=>"已拒绝",
        );
        return $type_array[$value];
    }
    //状态
    public  function getDeltaskTypeAttr($value){
        $type_array=array(
            '1'=>"超时取消",
            '2'=>"自动放弃",
            '3'=>"已取消",
        );
        return $value?$type_array[$value]:'';
    }


}
