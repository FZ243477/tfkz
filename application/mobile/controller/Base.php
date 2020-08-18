<?php
namespace app\mobile\controller;
use app\seller\model\Message;
use think\Db;
use think\Request;
use think\Session;
use think\Controller;//引入系统控制器

class Base extends Controller//继承系统控制器
{
    protected  $id='';//获取用户ID
    protected  $invite_code='';//获取用户ID
    public $redis;
    public function _initialize()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
        if(!isMobile())return $this->redirect(url('buy/my/index'));
       /* $operation_time=time();//验证登录时间是否超过五分钟
        $now_time=Session::get('operation_time');
        $fifteen=30*60;*/
        /*if($operation_time-$now_time>$fifteen){
            //return $this->redirect(url('Login/index'));
        }else{
            \think\Session::set('operation_time',$operation_time);
        }*/
        if(!session('id')){
            return $this->redirect(url('mobile/login/index'));
        }else{
            $this->id =Session::get('id');//获取用户ID
            $users = db('users')->where('id', $this->id)->field('id,username,vip,reward,balance,logins_ip,vip_time,qq,mobile,pay_pwd,invite_code')->find();
            $this->users = $users;
            $this->assign('users',$users);
            $this->invite_code=$this->users['invite_code'];//获取用户邀请码
        }
        $ip = $this->request->ip();
//       if ($this->users['logins_ip'] != $ip) {
//            return $this->redirect(url('Login/index'));
//        }
        $topnav=input('param.topnav');//顶部导航
        $hnav=input('param.hnav');//hnav 一级导航
        $nav = input('param.nav');//nav 二级导航
        $this->assign('hnav',$hnav);
        $this->assign('nav',$nav);
    }


}

