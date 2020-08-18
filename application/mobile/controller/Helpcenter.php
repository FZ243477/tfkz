<?php
namespace app\mobile\controller;
use app\buy\model\Message;
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
        if(request()->isAjax()){
            $field = "id,title,create_time";
            $total = Db::name('notice')->where(['state'=>1,'type'=>1])->count('id');
            $list = Notice::where(['state'=>1,'type'=>1])->order('newstop desc,id desc')->limit(0,10)->field($field)->select();
            if($list)$list = $list->toArray();
            $total2 = Db::name('notice')->where(['state'=>1,'type'=>2])->count('id');
            $list2 = Notice::where(['state'=>1,'type'=>2])->order('newstop desc,id desc')->limit(0,10)->field($field)->select();
            if($list2)$list2 = $list2->toArray();
            $res = [
                'list'=>$list,
                'list2'=>$list2,
                'total'=>$total,
                'total2'=>$total2,
            ];
            return $this->success('success','',$res);
        }
        $topnav=2;
        $this->assign('topnav',$topnav);//头部导航;
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
        $field = "id,title,create_time";
        $firse = ($data['page']-1)*10;
        $total = Db::name('notice')->where($where)->count('id');
        $list = Notice::where($where)->order('newstop desc,id desc')->limit($firse,10)->field($field)->select();
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
        $where['type'] = 2;
        $field = "id,title,create_time";
        $firse = ($data['page']-1)*10;
        $total = Db::name('notice')->where($where)->count('id');
        $list = Notice::where($where)->order('newstop desc,id desc')->limit($firse,10)->field($field)->select();
        if($list)$list = $list->toArray();
        $res = [
            'list'=>$list,
            'total'=>$total,
        ];
        return $this->success('success','',$res);
    }


    public function detail(Request $request){
        $id = $request->param('id');
        if(request()->isAjax()){
            $field = "title,type,content,create_time";
            $list = Notice::where(['state'=>1])->order('newstop desc,id desc')->where('id',$id)->field($field)->find();
            $list['type'] = $list['type']==1 ? '公告' : '常见问题';
            return $this->success('success','',$list);
        }
        $this->assign('id',$id);
        return view();
    }


    public function msg(Request $request){
        if(request()->isAjax()){
            $data = $request->param();
            $first = ($data['page'] - 1) * 5;
            $num = Db::name('message')->where(['type'=>1,'user_id'=>$this->id,'state'=>1,'look'=>0])->count('id');
            $total = Db::name('message')->where(['type'=>1,'user_id'=>$this->id,'state'=>1])->count('id');
            $list = Message::where(['type'=>1,'user_id'=>$this->id,'state'=>1])->order("id desc")->limit($first,5)->select();
            $list = $list ? $list->toArray() : [];
            $res = [
                'list'=>$list,
                'total'=>$total,
                'num'=>$num,
            ];

            return $this->success('success','',$res);
        }
        return view();
    }

    public function setMsg(Request $request){
        $data = $request->param();
        Db::name('message')->where(['id'=>$data['id']])->update(['look'=>1]);
        $num = Db::name('message')->where(['type'=>1,'user_id'=>$this->id,'state'=>1,'look'=>0])->count('id');
        return $this->success('success','',$num);
    }
}