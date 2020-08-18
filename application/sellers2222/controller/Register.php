<?php


namespace app\seller\controller;


use think\Controller;
use think\Db;
use think\Exception;
use think\Request;

class Register extends Controller
{
    /**
     * @notes 商家注册页面
     * @date 2019/7/26
     * @time 10:57
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request){
        $data = $request->param();
        $type = isset($data['type']) ? $data['type'] : '';
        $invite_code = isset($data['invite']) ? $data['invite'] : '';
        $this->assign('type',$type);
        $this->assign('invite_code',$invite_code);
        return view();
    }

    /**
     * @notes 商家注册
     * @date 2019/7/26
     * @time 10:58
     * @param Request $request
     */
    public function registerDo(Request $request){
        $data = $request->param();
        $result = $this->validate($data,'Register');
        if(true !== $result){
            return $this->error($result);
        }
        $data['seller_name'] = trim($data['seller_name']);
        if(time() - session('code_time') > 1200){
            session('code',null);
            session('code_time',null);
        }
        if($data['code']!= session('code'))return $this->error('验证码错误');
        if($data['mobile'] != session('mobile'))return $this->error('手机号码不正确！');
        $db = '';
        if($data['type']==1){
            $db = 'users';
            $field = 'username';
        }
        if($data['type']==2){
            $db = 'seller';
            $field = 'seller_name';
        }
        if(!$db)return $this->error('推荐链接有误1！');
        $tjuser = Db::name($db)->where(['invite_code'=>$data['invite_code'],'state'=>1])->value($field);
        if(!$tjuser)return $this->error('推荐链接有误！');
        $user = Db::name('users')->where(['username'=>$data['seller_name']])->find();
        if($user)return $this->error('用户名已被使用，请跟换！');
        $system = Db::name('system')->find();
        $add['seller_name'] = $data['seller_name'];
        $add['mobile'] = $data['mobile'];
        $add['reward'] = $system['seller_num'];
        $add['qq'] = $data['qq'];
        $add['login_pwd'] = md5($data['login_pwd']);
        $add['tjuser'] = $tjuser;
        $add['tjuser_state'] = $data['type'];
        $add['invite_code'] = md5(time().rand(0,99999));
        $add['vip_time'] = time() + (90*24*3600);
        $add['create_time'] = time();
        try{
            Db::startTrans();
            $id = Db::name('seller')->insertGetId($add);
            $memo = "注册赠送3个月会员，到期时间为".date('Y-m-d H:i:s',$add['vip_time']);
            if(!$this->writeVips($id,$add['seller_name'],0,$memo,$add['vip_time']))throw new Exception('添加会员记录失败');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
        return $this->success('注册成功！',url('Login/index'));
    }
    /**
     * @notes vip充值记录写入
     * @date 2019/7/29
     * @time 16:21
     * @param $id 用户id
     * @param $name 用户名
     * @param $price 金额
     * @param $memo 描述
     * @param $vip_time 到期时间
     * @return int|string
     */
    private function writeVips($id,$name,$price,$memo,$vip_time){
        $add['uid'] = $id;
        $add['utype'] = 1;
        $add['user_name'] = $name;
        $add['price'] = $price;
        $add['remarks'] = $memo;
        $add['expire_time'] = $vip_time;
        $add['create_time'] = time();
        $res = Db::name('vip_record')->insert($add);
        return $res;
    }
}