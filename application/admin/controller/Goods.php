<?php


namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;

class Goods extends Base
{
    /**
     * @Function 同步商品主图
     * @Author 扬风
     * @Date: 2020/4/17
     * @Time: 12:39
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function changeGoods(Request $request){
        $data = $request->param();
        if(!$data['task_id'] || !$data['goods_id'])return $this->error('参数错误！');
        $goods_img = Db::name('goods')->where(['id'=>$data['goods_id']])->value('pc_img');
        $goods_img = json_decode($goods_img);
        if(!$goods_img || !isset($goods_img[0]))return $this->error('请上传商品主图！');
        $update['pc_img'] = $goods_img[0];
        if(!Db::name('task_goods')->where(['task_id'=>$data['task_id'],'goods_id'=>$data['goods_id']])->update($update))return $this->error('同步失败！');
        return $this->success('同步成功！');
    }
}