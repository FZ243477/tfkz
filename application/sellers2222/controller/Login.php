<?php
namespace app\seller\controller;

use think\Controller;
use app\common\model\Sms;
use think\Db;
use think\Request;
use think\captcha\Captcha;

/**
 * 会员登录
 * Class Login
 * @package app\admin\controller
 */
class Login extends Controller
{
    /**
     * @notes 登录页面
     * @date 2019/7/23
     * @time 10:51
     * @return \think\response\View
     */
    public function index(){
        return view();
    }

    /**
     * @notes 短信验证码发送
     * @date 2019/7/27
     * @time 15:11
     * @param Request $request
     */
    public function mobileDo(Request $request){
        $mobile = $request->param('mobile');
        $check = '/^1[3-9]{1}[0-9]{9}$/';
        if (!preg_match($check, $mobile))return $this->error('手机号码不正确');
        $res = Sms::send($mobile);
        if($res==0)return $this->success('发送成功！');
        return $this->error($res);
    }

    /**
     * @notes 登录提交验证
     * @date 2019/7/23
     * @time 10:50
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function loginDo(Request $request){
      $data=$request->param();
      $ip = request()->ip();
      $data['seller_name'] = trim($data['seller_name']);
      if(!isset($data['seller_name']) ||!$data['seller_name'])return $this->error('请输入登录用户名!');
      if(!isset($data['password']) ||!$data['password'])return $this->error('请输入登录密码!');
      if(!isset($data['code']) ||!$data['code'])return $this->error('请输入验证码!');
      if(!captcha_check($data['code']))return $this->error('验证码错误');
      $info=Db::name('seller')->where(['seller_name'=>$data['seller_name']])->field('id,mobile,login_pwd,state')->find();//查询会员信息
      if(!$info)return $this->error('会员账号未注册!');
      if($info['state']==0)return $this->error('已被限制登录!');
      //if(md5($data['password'])!=$info['login_pwd'])return $this->error('密码错误!');
      session('seller_id',$info['id']);
      session('mobile',$info['mobile']);
      $update['logins_ip']=$ip;
      db('seller')->where(['id'=>$info['id']])->update($update);
      return $this->success('登陆成功！',url('Index/index'));
    }

    /**
     * @notes 判断是否是登录状态
     * @date 2019/12/3
     * @time 15:05
     */
    public function getSession(){
        if(session('seller_id')){
            return $this->error();
        };
        return $this->success('',url('login/login'));
    }

    /**
     * @notes 退出登录
     * @date 2019/8/26
     * @time 9:10
     */
    public function loginOut(){
        session_start();
        session_destroy();
        return $this->redirect(url('login/index'));
    }


    public function imgtest(){
        exit;
        $data = json_encode([1,2,3]);
        $data2 = json_encode([100,200,300]);
        $data3 = json_encode([2,2,3]);
        $edit = [
            'goods_id'=>$data,
            'goods_unit_price'=>$data2,
            'goods_num'=>$data3,
        ];
        Db::name('seller_task')->where(['id'=>1])->update($edit);
        dump(json_decode($data));
        dump($data2);
        dump($data3);
        return view();
    }

    /**
     * @notes 忘记密码页面
     * @date 2019/10/7
     * @time 16:22
     * @return \think\response\View
     */
    public function forget(){
        return view();
    }

    /**
     * @notes 忘记密码提交
     * @date 2019/10/7
     * @time 16:22
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function forgetDo(Request $request){
        $data=$request->param();
        $data['mobile'] = trim($data['mobile']);
        if(!isset($data['mobile']) ||!$data['mobile'])return $this->error('请输入手机号!');
        if(!isset($data['password']) ||!$data['password'])return $this->error('请输入新密码!');
        if($data['mobile']!=session('mobile'))return $this->error('手机号错误!');
        if(!isset($data['code']) ||!$data['code'])return $this->error('请输入验证码!');
        if($data['code']!=session('code'))return $this->error('验证码错误!');
        $info=Db::name('seller')->where(['mobile'=>$data['mobile']])->field('id,mobile,login_pwd,state')->find();//查询会员信息
        if(!$info)return $this->error('会员账号未注册!');
        if(!Db::name('seller')->where(['id'=>$info['id']])->update(['login_pwd'=>md5($data['password'])]))return $this->error('重置失败');
        return $this->success('重置成功',url('login/index'));
    }


    public function yanzhengma(){
        $captcha = new Captcha();
        // 设置验证码字符为纯数字
        $captcha->codeSet = '0123456789';
        $captcha->useCurve = false;
        return $captcha->entry();
    }



}
