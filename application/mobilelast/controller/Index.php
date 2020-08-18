<?php
namespace app\buy\controller;
use app\buy\model\Message;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends Base
{
    public function index()
    {

        return $this->redirect(url('mobile/my/index'));
    }
    public function msg(Request $request){
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

    public function setMsg(Request $request){
        $data = $request->param();
        Db::name('message')->where(['id'=>$data['id']])->update(['look'=>1]);
        $num = Db::name('message')->where(['type'=>1,'user_id'=>$this->id,'state'=>1,'look'=>0])->count('id');
        return $this->success('success','',$num);
    }

}