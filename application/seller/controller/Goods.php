<?php


namespace app\seller\controller;


use app\common\controller\Img;
use app\common\controller\aliyunOss;
use app\seller\model\GoodsKeyWorld;
use think\Db;
use think\Exception;
use think\Request;
use app\seller\model\Goods as GoodsModel;

class Goods extends Base
{
    public function index(Request $request)
    {
        if(request()->isAjax()){
            $data = $request->param();
            if($data['type'])$where['b.type'] = $data['type'];
            if($data['shop'])$where['a.shop_id'] = $data['shop'];
            if($data['max_price'])$where['a.price'] = ['lt',$data['max_price']];
            if($data['min_price'])$where['a.price'] = ['gt',$data['min_price']];
            if($data['max_price'] && $data['min_price'])$where['price'] = ['between',[$data['min_price'],$data['max_price']]];
            if($data['word'])$where['a.name'] = ['like','%'.$data['word'].'%'];
            $firse = ($data['page']-1)*$data['size'];
            $where['a.seller_id'] = $this->seller['id'];
            $where['a.state'] = 1;
            $field = "b.shop_name,b.type,a.name,a.id,a.pc_img,a.price,a.show_price,a.num";
            $total = Db::table('tfkz_goods')->alias('a')->join("tfkz_shop b","a.shop_id=b.id")->where($where)->count('a.id');
            $list = Db::table('tfkz_goods')->alias('a')->join("tfkz_shop b","a.shop_id=b.id")->where($where)->order('a.id desc')->field($field)->limit($firse,$data['size'])->select();
            $list = $list ? $list->toArray() : [];
            foreach ($list as $key=>$item){
                $list[$key]['pc_img'] = isset(json_decode($item['pc_img'])[0]) ? json_decode($item['pc_img'])[0] :'';
                $arr = [
                    1=>'淘宝',
                    2=>'天猫',
                    3=>'飞猪',
                ];
                $arr2 = [
                    1=>'/static/seller/img/taobao.png',
                    2=>'/static/seller/img/tianmao.png',
                    3=>'/static/seller/img/feizhu.png',
                ];
                $list[$key]['pic'] = $arr2[$item['type']];
                $list[$key]['type'] = $arr[$item['type']];
            }
            $shop = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'state'=>1])->field("id,shop_name")->select();
            $shop = $shop ? $shop->toArray() : [];
            $res = [
                'total' => $total,
                'list' => $list,
                'shop' => $shop
            ];
            return $this->success('success','',$res);
        }
        $this->assign('menu','5-1');
        return view();
    }

    /**
     * @notes 获取店铺数据
     * @date 2019/8/29
     * @time 16:59
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shop(){
        $shop = Db::name('shop')->where(['state'=>1,'seller_id'=>$this->seller['id']])->field('id,shop_name')->select();
        $shop = $shop ? $shop->toArray() : [];
        return $this->success('','',$shop);
    }

    /**
     * @notes 添加时初始数据
     * @date 2019/8/22
     * @time 10:26
     * @return \think\response\View|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goods()
    {
        if(request()->isAjax()){
            $shop = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'state'=>1])->field("id,shop_name")->select();
            $shop = $shop ? $shop->toArray() : [];
            $res = [
                'shop' => $shop
            ];
            return $this->success('success','',$res);
        }
        $state = Db::name('system')->value('switch');
        $this->assign('state',$state);
        return view();
    }

    /**
     * @notes 发布任务是添加商品
     * @date 2019/12/2
     * @time 11:38
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskGoods()
    {
        if(request()->isAjax()){
            $shop = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'state'=>1])->field("id,shop_name")->select();
            $shop = $shop ? $shop->toArray() : [];
            $res = [
                'shop' => $shop
            ];
            return $this->success('success','',$res);
        }
        $state = Db::name('system')->value('switch');
        $this->assign('state',$state);
        return view();
    }
    public function getGoodsKey(Request $request)
    {
        $data = $request->param();
        if($data['shop_id']){
            $type = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'id'=>$data['shop_id']])->value('type');
            $where['type'] = $type ? $type : 0;
        }
        $where['seller_id'] = $this->seller['id'];
        $goods_key = Db::name('goods_key')->where($where)->order('create_time desc')->limit(5)->select();
        $goods_key = $goods_key ? $goods_key->toArray() : [];
        foreach ($goods_key as &$item){
            $item['key_wold'] = GoodsKeyWorld::where(['goods_key_id'=>$item['id']])->select();
            $item['key_wold'] = $item['key_wold'] ? $item['key_wold'] : [];
        }
        $res = [
            'goods_key' => $goods_key,
        ];
        return $this->success('success','',$res);
    }

    /**
     * @notes
     * @date 2019/9/4
     * @time 11:31
     * @param Request $request
     * @return bool|\think\response\View|void
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        $data = $request->param();
        if(!$data['id'])return false;
        if(request()->isAjax()){
            $where['id'] = $data['id'];
            $shop = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'state'=>1])->field("id,shop_name")->select();
            $shop = $shop ? $shop->toArray() : [];
            $list = Db::name('goods')->where(['id'=>$data['id']])->find();
            $list['pc_img'] = json_decode($list['pc_img'],true);
            if(!$list)return $this->error('该数据不存在');
            $img = [];
            foreach ($list['pc_img'] as $key=>$item){
                $img[$key]['name'] = '';
                $img[$key]['url'] = 'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com'.$item;
            }
            $res = [
                'list' => $list,
                'shop' =>$shop,
                'img' =>$img
            ];
            return $this->success('success','',$res);
        }
        $this->assign('id',$data['id']);
        $state = Db::name('system')->value('switch');
        $this->assign('state',$state);
        return view();
    }

    /**
     * @notes 发布任务是修改
     * @date 2019/12/2
     * @time 11:34
     * @param Request $request
     * @return bool|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskEdit(Request $request)
    {
        $data = $request->param();
        if(!$data['id'])return false;
        if(request()->isAjax()){
            $where['id'] = $data['id'];
            $shop = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'state'=>1])->field("id,shop_name")->select();
            $shop = $shop ? $shop->toArray() : [];
            $list = Db::name('goods')->where(['id'=>$data['id']])->find();
            $list['pc_img'] = json_decode($list['pc_img']);
            if(!$list)return $this->error('该数据不存在');
            //$list['key_world'][0] = Db::name('goods_key')->where(['id'=>$list['goods_key_id']])->find();
            //$list['key_world'][0]['key_wold'] = GoodsKeyWorld::where(['goods_key_id'=>$list['goods_key_id']])->select();
            $res = [
                'list' => $list,
                'shop' =>$shop
            ];
            return $this->success('success','',$res);
        }
        $this->assign('id',$data['id']);
        $state = Db::name('system')->value('switch');
        $this->assign('state',$state);
        return view();
    }

    /**
     * @notes
     * @date 2019/9/4
     * @time 11:31
     * @param Request $request
     */
    public function goodsKeyAdd(Request $request){
        $data = $request->param();
        if(!$data['name'])return $this->error('请填写方案名称！');
        if(!$data['shop_id'])return $this->error('请选择店铺！');
        $type = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'id'=>$data['shop_id']])->value('type');
        Db::startTrans();
        try{
            $add['seller_id'] = $this->seller['id'];
            $add['type'] = $type;
            $add['name'] = $data['name'];
            $add['create_time'] = time();
            $data['id'] = Db::name('goods_key')->insertGetId($add);
            $num = 0;
            foreach ($data['key_wold'] as &$row){
                if($row['key_world']){
                    $num++;
                    $row['goods_key_id'] = $data['id'];
                    $row['create_time'] = time();
                    if(isset($row['discount']) && $row['discount']){
                        $row['discount'] = implode(',',$row['discount']);
                    }else{
                        $row['discount'] = '';
                    }
                    if(isset($row['filter'])){
                        $row['filter'] = implode(',',$row['filter']);
                    }else{
                        $row['filter'] = ',,,';
                    }
                    Db::name('goods_key_world')->insert($row);
                }
            }
            if($num==0)throw new Exception('请填写关键词');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('添加方案成功','',$data);
    }

    /**
     * @notes 删除商品
     * @date 2019/9/4
     * @time 14:45
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function del(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误！');
        $goods = Db::name('goods')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id'],'state'=>1])->find();
        if(!$goods)return $this->error('未查到该数据！');
        if(!Db::name('goods')->where(['id'=>$data['id']])->update(['state'=>2]))return $this->error('删除失败！');
        return $this->success('删除成功！');
    }

    /**
     * @notes
     * @date 2019/9/4
     * @time 11:31
     * @param Request $request
     */
    public function goodsKeyEdit(Request $request){
        $data = $request->param();
        if(!$data['name'])return $this->error('请填写方案名称！');
        if(!$data['id'])return $this->error('参数错误！');
        Db::startTrans();
        try{
            $edit['name'] = $data['name'];
            $edit['update_time'] = time();
            Db::name('goods_key')->where(['id'=>$data['id']])->update($edit);
            Db::name('goods_key_world')->where(['goods_key_id'=>$data['id']])->delete();
            $num = 0;
            foreach ($data['key_wold'] as &$row){
                if($row['key_world']){
                    $num++;
                    $row['goods_key_id'] = $data['id'];
                    $row['create_time'] = time();
                    if(isset($row['discount']) && $row['discount']){
                        $row['discount'] = implode(',',$row['discount']);
                    }else{
                        $row['discount'] = '';
                    }
                    if(isset($row['filter'])){
                        $row['filter'] = implode(',',$row['filter']);
                    }else{
                        $row['filter'] = ',,,';
                    }
                    Db::name('goods_key_world')->insert($row);
                }
            }
            if($num==0)throw new Exception('请填写关键词');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('修改方案成功','',$data);
    }

    /** 修改商品
     * @notes
     * @date 2019/8/27
     * @time 10:24
     * @param Request $request
     */
    public function goodsEditDo(Request $request){
        $data = $request->param();
        $result = $this->validate($data,'Goods.edit');
        if(true !== $result){
            return $this->error($result);
        }
        $data['link'] = str_replace('amp;','',$data['link']);
        $res = $this->convertUrlQuery($data['link']);
        if(!isset($res['id']))return $this->error('商品链接不正确');
        $taobao_id = $res['id'];
        $state = Db::name('system')->value('switch');
        if($state==1){
            if (!preg_match('/^[0-9]{6}$/i',$data['number']))return $this->error('核对码只能是6位数字！');
        }
        if($data['number']){
            if (!preg_match('/^[0-9]{6}$/i',$data['number']))return $this->error('核对码只能是6位数字！');
        }
        $edit['number'] = $data['number'];
        Db::startTrans();
        try{
            foreach ($data['pc_img'] as &$item){
                $item = str_replace('http://tfkzpic.oss-cn-hangzhou.aliyuncs.com','',$item);
            }
            $edit['pc_img'] = json_encode($data['pc_img']);
            $edit['name'] = $data['name'];
            $edit['shop_id'] = $data['shop_id'];
            $edit['link'] = $data['link'];
            $edit['taobao_id'] = $taobao_id;
            $edit['show_price'] = $data['show_price'];
            $edit['update_time'] = time();
            Db::name('goods')->where(['id'=>$data['id']])->update($edit);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('修改成功');
    }

    /**
     * @notes 添加商品
     * @date 2019/8/27
     * @time 10:24
     * @param Request $request
     */
    public function goodsAdd(Request $request){
        $data = $request->param();
        $result = $this->validate($data,'Goods.add');
        if(true !== $result){
            return $this->error($result);
        }
        $data['link'] = str_replace('amp;','',$data['link']);
        $res = $this->convertUrlQuery($data['link']);
        if(!isset($res['id']))return $this->error('商品链接不正确');
        $taobao_id = $res['id'];
        $state = Db::name('system')->value('switch');
        if($state==1){
            if (!preg_match('/^[0-9]{6}$/i',$data['number']))return $this->error('核对码只能是6位数字！');
        }
        if($data['number']){
            if (!preg_match('/^[0-9]{6}$/i',$data['number']))return $this->error('核对码只能是6位数字！');
        }
        $add['number'] = $data['number'];
        Db::startTrans();
        try{
            $add['pc_img'] = json_encode($data['pc_img']);
            $add['name'] = $data['name'];
            $add['shop_id'] = $data['shop_id'];
            $add['seller_id'] = $this->seller['id'];
            $add['link'] = $data['link'];
            $add['taobao_id'] = $taobao_id;
            $add['show_price'] = $data['show_price'];
            $add['create_time'] = time();
            Db::name('goods')->insert($add);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('添加成功');
    }


    /**
     * @notes 识别链接id
     * @date 2019/10/15
     * @time 13:56
     * @param string $query 链接
     * @return array|bool
     */
    public function convertUrlQuery($query="https://item.taobao.com/item.htm?id=603687399746")
    {
        $url_info = parse_url($query);
        if(!isset($url_info['query']))return false;
        $queryParts = explode('&', $url_info['query']);
        $params = array();
        foreach ($queryParts as $param)
        {

            $item = explode('=', $param);
            if(isset($item[0])){
                if(!isset($item[1]))$item[1]='';
                $params[$item[0]] = $item[1];
            }
        }
        return $params;
    }


    public function goodsImg(Request $request){
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $path = 'uploads' . DS . 'goods' . DS;
        $res = aliyunOss::uploadImg($file,$path);
        return json($res);
    }

    /**
     * Function 获取1688链接参数
     * User 扬风
     * Date 2020/6/21
     * Time 6:13 PM
     */
    public function parse_str($str=''){
        $arr = parse_url($str);
        if(!isset($arr['path']))return false;
        $str = str_replace('.html','',$arr['path']);
        $str = str_replace('offer','',$str);
        $str = str_replace('/','',$str);
        return $str;
    }

    public function text(){
        exit;
        $list = Db::name('goods')->select()->toArray();
        $num=0;
        foreach ($list as $item){
            $edit['link'] = str_replace('amp;','',$item['link']);
            if($edit['link'] != $item['link']){
                $num++;
                //Db::name('goods')->where(['id'=>$item['id']])->update($edit);
            }
        }
        dump($num);
        exit;
        $data = "https://item.taobao.com/item.htm?spm=a1z10.1-c.w4004-18673075209.8.2cda60a6fs0dJP&amp;id=57245856633";
        dump($data);
        $data = str_replace('amp;','',$data);
        dump($data);
    }

}