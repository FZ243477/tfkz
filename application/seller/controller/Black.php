<?php


namespace app\seller\controller;


use app\seller\model\SellerLimit;
use think\Request;
use think\Db;

class Black extends Base
{
    public function index(){
        $this->assign('menu','1-4');
        return view();
    }

    public function getData(Request $request){
        $data = $request->param();
        $where['seller_id'] = $this->seller['id'];
        $total = Db::name('seller_limit')->where($where)->count('id');
        $list = SellerLimit::where($where)->order('id desc')->limit(($data['page']-1)*$data['size'],$data['size'])->select();
        if($list)$list = $list->toArray();
        $res['list'] = $list;
        $res['total'] = $total;
        return $this->success('success','',$res);
    }

    public function editData(Request $request){
        $data = $request->param();
        if(!$data['wangwang'])return $this->error('旺旺号不能为空');
        if($data['state']){
            if(!$data['end_time'])return $this->error('限时时间不能为空');
        };
        if($data['id']){//修改
            $edit['wangwang'] = $data['wangwang'];
            $edit['state'] = $data['state'];
            if($data['state']==0){
                $edit['end_time'] = '';
            }else{
                $edit['end_time'] = strtotime($data['end_time']);
            }
            $edit['status'] = 0;
            $edit['update_time'] = time();
            if(!Db::name('seller_limit')->where(['id'=>$data['id']])->update($edit))return $this->error('修改失败');
            return $this->success('修改成功');
        }else{//添加
            $list = Db::name('seller_limit')->where(['seller_id'=>$this->seller['id'],'wangwang'=>$data['wangwang']])->find();
            if($list)return $this->error('该旺旺已经在黑名单里面!');
            $add['seller_id'] = $this->seller['id'];
            $add['wangwang'] = $data['wangwang'];
            $add['state'] = $data['state'];
            $add['end_time'] = strtotime($data['end_time']);
            $add['create_time'] = time();
            if(!Db::name('seller_limit')->insert($add))return $this->error('添加失败');
            return $this->success('添加成功');
        }

    }

    /**
     * @notes 删除黑名单
     * @date 2019/10/19
     * @time 10:16
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function del(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误');
        $list = Db::name('seller_limit')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->find();
        if(!$list)return $this->error('未找到数据！');
        if($list['status']==1)return $this->error('数据状态不正确，不能删除！');
        if(!Db::name('seller_limit')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->delete())return $this->error('删除失败！');
        return $this->success('删除成功！');
    }
}
