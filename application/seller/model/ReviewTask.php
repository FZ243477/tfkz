<?php


namespace app\seller\model;


use think\Db;
use think\Model;

class ReviewTask extends Model
{
    /**
     * @notes  店铺shuju
     * @date 2019/9/16
     * @time 9:45
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopIdAttr($id)
    {
        $field = "id,type,shop_name,wangwang";
        $list = Db::name('shop')->where(['id'=>$id])->field($field)->find();
        $img = [
            '1'=>'/static/seller/img/taobao.png',
            '2'=>'/static/seller/img/tianmao.png',
            '3'=>'/static/seller/img/feizhu.png',
        ];
        $arr = [
            1=>'淘宝',
            2=>'天猫',
            3=>'飞猪',
        ];
        $list['type_name'] = $arr[$list['type']];
        $list['img'] = $img[$list['type']];
        return $list;
    }

    /**
     * @notes 任务类型
     * @date 2019/9/16
     * @time 10:07
     * @param $type
     * @return mixed
     */
    public function getStateAttr($type)
    {
        $arr = [
            0=>'未支付',
            1=>'已支付',
            2=>'已审核',
            3=>'已上传好评截图',
            4=>'已完成',
            5=>'已取消',
            6=>'已拒接',
            7=>'已拒绝',
        ];
        return $arr[$type];
    }
    public function getBuyIdAttr($val)
    {
        $list = Db::name('user_buyno')->where(['id'=>$val])->find();
        return $list ? $list['wwid'] : '';
    }
    public function getUserTaskIdAttr($val)
    {
        $list = Db::name('user_task')->where(['id'=>$val])->find();
        return $list ? $list['task_number'] : '';
    }
    public function getUploadTimeAttr($val)
    {
        return $val ? date('Y-m-d H:i:s',$val) : '';
    }
    public function getConfirmTimeAttr($val)
    {
        return $val ? date('Y-m-d H:i:s',$val) : '';
    }
    public function getImgAttr($val)
    {
        return $val ? explode(',',$val) : [];
    }
}
