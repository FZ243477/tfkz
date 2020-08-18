<?php


namespace app\common\model;


use think\Model;
use think\Db;

class VipRecord extends Model
{
    /**
     * @notes 会员到期时间
     * @date 2019/8/8
     * @time 14:40
     * @param $name
     * @return false|string
     */
   public function getExpireTimeAttr($name)
   {
       return date('Y-m-d',$name);
   }

    public  function getUtypeAttr($value){
        $type_array=array(
            '1'=>"商家",
            '2'=>"买手",
        );
        return $type_array[$value];
    }

    public  function getUsernameAttr($value,$data){
        if($data['utype']==1){
            $username = Db::name('seller')->where(['id'=>$data['uid']])->value('seller_name');
        }else{
            $username = Db::name('users')->where(['id'=>$data['uid']])->value('username');
        }
        return $username;
    }
}
