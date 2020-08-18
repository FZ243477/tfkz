<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends Base
{
    public function index()
    {
     return view();
    }

    public function main()
    {
        // 获取今天发布总任务
        $data['today_task_num']=Db::name('seller_task') ->whereTime('publish_time','today')->count();
        // 获取今日发单总数
        $data['today_user_task_num']=Db::name('seller_task') ->whereTime('publish_time','today')->sum('num');
        // 获取今日PC单总数
        $data['today_pc_task_num']=Db::name('seller_task')->where('terminal',1) ->whereTime('publish_time','today')->sum('num');
        // 获取今日手机单总数
        $data['today_phone_task_num']=Db::name('seller_task')->where('terminal',2) ->whereTime('publish_time','today')->sum('num');

        // 获取今日商家提现金额
        $data['seler_cash_price']=Db::name('seller_cash') ->whereTime('create_time','today')->sum('price');
        // 获取今日买手提现金额
        $data['user_cash_price']=Db::name('user_cash') ->whereTime('create_time','today')->sum('price');
        // 获取今日提现总金额
        $data['cash_price']=$data['seler_cash_price']+$data['user_cash_price'];
        // 获取今日充值总金额
        $data['recharge_price']=Db::name('recharge')->where(['state'=>1]) ->whereTime('create_time','today')->sum('price');
        $this->assign('data',$data);
        return view();
    }
//修改密码
    public function changePwd()
    {
        if (request()->isPost()) {
            $data = input();
            $user_info=db('admin_user')->where('id',session('admin_id'))->find();
            if(!$user_info){
                return $this->redirect('Login/index');
            }
            if($user_info['password']!=md5($data['pwd'])){
                return $this->error('原密码不正确!');
            }
            if($data['pwd1']!=$data['pwd2']){
                return $this->error('两次密码不相同!');
            }
            $res=db('admin_user')->where('id',session('admin_id'))->update(['password'=>md5($data['pwd1'])]);
            if($res){
                return $this->success('密码修改成功!');
            }else{
                return $this->error('密码修改失败!');
            }
        }
        return view();
    }
}
