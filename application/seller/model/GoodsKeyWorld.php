<?php


namespace app\seller\model;


use think\Model;

class GoodsKeyWorld extends Model
{
    public function getDiscountAttr($val)
    {
        if($val){
            $val = explode(',',$val);
        }else{
            $val = [];
        }
        return $val;
    }
    public function getFilterAttr($val)
    {
        if($val){
            $val = explode(',',$val);
            $arr['class1'] = isset($val[0]) ? $val[0] : '';
            $arr['class2'] = isset($val[1]) ? $val[1] : '';
            $arr['class3'] = isset($val[2]) ? $val[2] : '';
            $arr['class4'] = isset($val[3]) ? $val[3] : '';
        }else{
            $arr = [
                'class1'=>'',
                'class2'=>'',
                'class3'=>'',
                'class4'=>'',
            ];
        }
        return $arr;
    }
}