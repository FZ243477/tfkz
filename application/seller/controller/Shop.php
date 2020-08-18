<?php


namespace app\seller\controller;

use think\Db;
use think\Request;

class Shop extends Base
{

    /**
     * @notes 绑定店铺页面
     * @date 2019/8/12
     * @time 15:02
     * @return \think\response\View|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shop(Request $request){
        $data = $request->param();
        if(!isset($data['str'])){
            $str = 'taobao';
            $menu = '4-1';
        }else{
            $str = $data['str'];
            $menu = '4-2';
        }
        if(request()->isAjax()){
            $where['seller_id'] = $this->seller['id'];
            $where['state'] = ['neq',3];
            $list = Db::name('shop')->where($where)->select();
            if($list)$list->toArray();
            return $this->success('success','',$list);
        }
        $this->assign('str',$str);
        $this->assign('menu',$menu);
        return view();
    }

    /**
     * @notes 绑定店铺
     * @date 2019/8/12
     * @time 15:02
     * @param Request $request
     */
    public function shopAdd(Request $request){
        $data = $request->param();
        $data['shop_name'] = trim($data['shop_name']);
        $data['wangwang'] = trim($data['wangwang']);
        $result = $this->validate($data,'Shop');
        if(true !== $result){
            return $this->error($result);
        }
        if(Db::name('shop')->where(['seller_id'=>$this->seller['id'],'state'=>['neq',3]])->count('id') >= 8)return $this->error('最多只能添加8个店铺，您已达上限！');
        $add['seller_id'] = $this->seller['id'];
        $add['name'] = trim($data['name']);
        $add['wangwang'] = trim($data['wangwang']);
        $add['shop_name'] = trim($data['shop_name']);
        $add['mobile'] = $data['mobile'];
        $add['province'] = $data['province'];
        $add['city'] = $data['city'];
        $add['area'] = $data['area'];
        $add['address'] = $data['address'];
        $add['link'] = $data['link'];
        $add['type'] = $data['type'];
        $add['code'] = $data['code'];
        $add['state'] = 0;
        $add['create_time'] = time();
        if(!Db::name('shop')->insert($add))return $this->error('申请数据添加失败');
        return $this->success('绑定店铺申请成功，请耐心等待后台审核！');
    }


    /**
     * @notes 店铺修改
     * @date 2019/8/12
     * @time 15:13
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function shopEdit(Request $request){
        $data = $request->param();
        $data['shop_name'] = trim($data['shop_name']);
        $data['wangwang'] = trim($data['wangwang']);
        $result = $this->validate($data,'Shop');
        if(true !== $result){
            return $this->error($result);
        }
        $list = Db::name('shop')->where(['seller_id'=>$this->seller['id'],'id'=>$data['id']])->find();
        if(!$list)return $this->error('未找到该店铺数据！');
        if(!$data['wangwang'])return $this->error('旺旺号不能为空！');
        $lists = Db::name('shop')->where(['wangwang'=>$data['wangwang'],'id'=>['neq',$data['id']]])->find();
        if($lists)return $this->error('旺旺号已被使用！');
        $edit['seller_id'] = $this->seller['id'];
        $edit['name'] = $data['name'];
        $edit['shop_name'] = $data['shop_name'];
        $edit['mobile'] = $data['mobile'];
        $edit['province'] = $data['province'];
        $edit['city'] = $data['city'];
        $edit['area'] = $data['area'];
        $edit['address'] = $data['address'];
        $edit['link'] = $data['link'];
        $edit['type'] = $data['type'];
        $edit['code'] = $data['code'];
        $edit['state'] = 0;
        $edit['update_time'] = time();
        if(!Db::name('shop')->where(['id'=>$data['id']])->update($edit))return $this->error('申请数据添加失败');
        return $this->success('绑定店铺申请成功，请耐心等待后台审核！');
    }

    public function getDelivery(Request $request){
        $data = $request->param();
        $list = Db::name('delivery')->select();
        return $this->success($list);
    }
}