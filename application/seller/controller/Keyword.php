<?php


namespace app\seller\controller;


use think\Controller;
use think\Db;


class Keyword extends Controller
{
    /**押金财务导出
     * @notes
     * @date 2019/8/10
     * @time 13:50
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function test(){
        $id = 854;
        $arrays = [];
        $arrType = [
            1=>'淘宝',
            2=>'天猫',
            3=>'飞猪',
        ];
        $keyList = Db::name('goods_key')->where(['seller_id'=>$id])->select()->toArray();
        foreach ($keyList as $item){
            $goods = Db::name('goods')->where(['goods_key_id'=>$item['id']])->find();
            if($goods){
                $goods['shop'] = Db::name('shop')->where(['id'=>$goods['shop_id']])->value('shop_name');
            }else{
                $goods['name'] = '';
                $goods['shop'] = '';
            }
            $list = Db::name('goods_key_world')->where(['goods_key_id'=>$item['id']])->select()->toArray();
            foreach ($list as $value){
               $key['pingtai'] = $arrType[$item['type']];
               $key['goods_name'] = $goods['name'];
               $key['shop_name'] = $goods['shop'];
               $key['name'] = $item['name'];
               $key['key'] = $value['key_world'];
               $key['discount'] = $value['discount'];
               $key['filter'] = $value['filter'];
               $key['sort'] = $value['sort'];
               $key['max_price'] = $value['max_price'] > 0 ? $value['max_price'] : '';
               $key['min_price'] = $value['min_price'] > 0 ? $value['min_price'] : '';
               $key['province'] = $value['province'];
               $arrays[] = $key;
            }
        }
        $title = ['平台','商品名','店铺名','方案名','关键词','折扣服务','筛选分类','排序方式','搜索最大价格','搜索最小价格','发货省份'];
        Phpexcel::exportExcel($title,$arrays,'关键词表2');
    }
}