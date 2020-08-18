<?php


namespace app\seller\model;


use think\Db;
use think\Model;

class SellerTask extends Model
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
        $field = "id,type,shop_name";
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
    public function getTaskTypeAttr($type)
    {
        $arr = [
            '1'=>'文字好评',
            '2'=>'淘口令',
            '3'=>'二维码',
            '4'=>'直通车',
            '5'=>'通道任务',
        ];
        return $arr[$type];
    }
    public function getPublishTimeAttr($time)
    {
        $time = $time ? date('Y-m-d H:i:s',$time) : '';
        return $time;
    }

    /**
     * @notes 商品数据
     * @date 2019/9/16
     * @time 9:45
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsIdAttr($id)
    {
        $goods_ids = json_decode($id);
        $goods = [];
        foreach ($goods_ids as &$goods_id){
            $field = "id,name,link,pc_img,goods_key_id,show_price";
            $good = Db::name('goods')->where(['id'=>$goods_id])->field($field)->find();
            $img = json_decode($good['pc_img']);
            $img = $img ? $img : [''];
            $good['pc_img'] = $img;
            $goods[] = $good;
        }
        return $goods;
    }
}