<?php
namespace app\buy\controller;
use think\Controller;
use think\Db;
use app\common\model\Sms; //引入手机验证码
use app\common\controller\Img;
use think\Request;
use think\Session;
use app\common\model\Notice;

class Helpcenter extends Base
{
    /**
     * @notes 帮助中心页面
     * @date 2019/8/12
     * @time 20:47
     * @return \think\response\View|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(){
        $topnav=2;
        $this->assign('topnav',$topnav);//头部导航;
        if(request()->isAjax()){
            $field = "title,content,create_time";
            $total = Db::name('notice')->where(['state'=>1,'type'=>1])->count('id');
            $list = Notice::where(['state'=>1,'type'=>1])->order('newstop desc,id desc')->limit(0,6)->field($field)->select();
            if($list)$list = $list->toArray();
            $total2 = Db::name('notice')->where(['state'=>1,'type'=>2])->count('id');
            $list2 = Notice::where(['state'=>1,'type'=>2])->order('newstop desc,id desc')->limit(0,6)->field($field)->select();
            dump($list2);exit;
            if($list2)$list2 = $list2->toArray();
            $res = [
                'list'=>$list,
                'list2'=>$list2,
                'total'=>$total,
                'total2'=>$total2,
            ];
            return $this->success('success','',$res);
        }
        return view();
    }

    /**
     * @notes 获取公告数据
     * @date 2019/8/12
     * @time 20:46
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getggData(Request $request){
        $data = $request->param();
        if($data['search']){
            $where['title'] = ['like','%'.$data['search'].'%'];
        }
        $where['state'] = 1;
        $where['type'] = 1;
        $field = "title,content,create_time";
        $firse = ($data['page']-1)*6;
        $total = Db::name('notice')->where($where)->count('id');
        $list = Notice::where($where)->order('newstop desc,id desc')->limit($firse,6)->field($field)->select();
        if($list)$list = $list->toArray();
        $res = [
            'list'=>$list,
            'total'=>$total,
        ];
        return $this->success('success','',$res);
    }

    /**
     * @notes 获取问题数据
     * @date 2019/8/12
     * @time 20:46
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getwtData(Request $request){
        $data = $request->param();
        if($data['search']){
            $where['title'] = ['like','%'.$data['search'].'%'];
        }
        $where['state'] = 1;
        $where['type'] = 4;
        $field = "title,content,create_time";
        $firse = ($data['page']-1)*6;
        $total = Db::name('notice')->where($where)->count('id');
        $list = Notice::where($where)->order('newstop desc,id desc')->limit($firse,6)->field($field)->select();
        if($list)$list = $list->toArray();
        $res = [
            'list'=>$list,
            'total'=>$total,
        ];
        return $this->success('success','',$res);
    }
}