<?php

//买手
namespace app\admin\controller;

use app\admin\model\Message;
use app\common\model\UserBuyno;
use app\common\model\UserDepositRecharge;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Session;
use app\common\model\Users;
use app\common\model\UserRewardRecharge;
use app\common\model\UserBank;

class Buyer extends Base
{
    //买家列表
    public function BuyerList()
    {
        if(request()->isPost()) {
            $date=input();
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            if(isset($date['registerTime'])&&$date['registerTime']){
                $time=explode(" - ",$date['registerTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            if(isset($date['expireTime'])&&$date['expireTime']){
                $time=explode(" - ",$date['expireTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['vip_time'] = ['between',[$time1,$time2]];
            }
            if(isset($date['state'])&&$date['state']){
                if($date['state']==1){
                    $where['vip']=1;
                }else if($date['state']==2){
                    $where['vip']=0;
                }
            }
            if(isset($date['phone'])&&$date['phone']){
                $where['mobile'] =['like','%'.trim($date['phone']).'%'];
               // $where['mobile'] = $date['phone'];
            }
            if(isset($date['tjuser'])&&$date['tjuser']){
                $where['tjuser'] =['like','%'.trim($date['tjuser']).'%'];
                // $where['mobile'] = $date['phone'];
            }

            if(isset($date['qq'])&&$date['qq']){
                $where['qq'] =['like','%'.trim($date['qq']).'%'];
                //$where['qq'] = $date['qq'];
            }
            if(isset($date['username'])&&$date['username']){
                $where['username'] =['like','%'.trim($date['username']).'%'];
            }

            $count=Db::name('users')->count('id');
            $buy_list=Users::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($buy_list)$buy_list = $buy_list->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buy_list]);
        }
        return view();
    }
    //新增买家
    public function addbuyer(){
        if(request()->isPost()){
            $date=input();
            $result = $this->validate($date,'Register');
            if(true !== $result){
                return $this->error($result);
            }
            //var_dump($date['phone']);exit;
            if($date['login_pwd'] != $date['login_pwd2'] ){
                return $this->error('两次密码输入不一致,请检查后重新输入.');
            }
            $date['name']=trim($date['name']);
            if(!$date['qq']){
                return $this->error('请输入QQ');
            }
            if(!$date['mobile']){
                return $this->error('请输入手机号');
            }
            if(!$date['name']){
                return $this->error('请输入用户名');
            }
            $check_username=db('users')->where('username',$date['name'])->value('id');
            if($check_username){
                return $this->error('用户名已在买家存在');
            }
            $check_sellername=db('seller')->where('seller_name',$date['name'])->value('id');
            if($check_sellername){
                return $this->error('用户名已在商家家存在');
            }
            $check_mobile=db('users')->where('mobile',$date['mobile'])->value('id');
            if($check_mobile){
                return $this->error('手机号存在');
            }
            if(!$date['vip_time']){
                $date['vip_time']=time();
            }else{
            	 $date['vip_time']=strtotime($date['vip_time']);
            }
            if(strtotime($date['vip_time'])>time()){
                $date['vip']=1;
            }else{
                $date['vip']=0;
            }
            $adduser=[
                'username'=>$date['name'],
                'login_pwd'=>md5($date['login_pwd']),
                'mobile'=>$date['mobile'],
                'qq'=>$date['qq'],
                'vip'=>$date['vip'],
                'vip_time'=>$date['vip_time'],
                'balance'=>$date['balance'],
                'reward'=>$date['reward'],
                'invite_code'=>md5(time().rand(0,99999)),
                'create_time'=>time(),
                'state'=>1,
                'note'=>$date['note']
            ];
            Db::name('users')->insertGetId($adduser);
            $res1=admin_log("新增买家", "管理员{$this->admin_info['user_name']}操作:新增买号用户名为{$date['name']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return $this->success('添加成功','Buyer/BuyerList');
        }
        return view();
    }
    //买号列表
    public function BuyNumberList()
    {
        if(request()->isPost()) {
            $date=input();
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            $where['state']= array('neq',3);
            if(isset($date['wangwang'])&&$date['wangwang']){
               // $where['wwid']=$date['wangwang'];
                $where['wwid'] =['like','%'.trim($date['wangwang']).'%'];
            }
            if(isset($date['phone'])&&$date['phone']){
                $where['addressphone']=trim($date['phone']);
            }

            if(isset($date['userphone'])&&$date['userphone']){
                $where['uid']=db('users')->where('mobile',trim($date['userphone']))->value('id');
            }

            if(isset($date['username'])&&$date['username']){
                $where['uid']=db('users')->where('username',trim($date['username']))->value('id');
            }
            if(isset($date['qq'])&&$date['qq']){
                $where['uid']=db('users')->where('qq',trim($date['qq']))->value('id');
            }

            if(isset($date['alipayname'])&&$date['alipayname']){
                $where['alipayname']=trim($date['alipayname']);
            }

            if(isset($date['detail_address'])&&$date['detail_address']){
                $where['detail_address'] =['like','%'.trim($date['detail_address']).'%'];
            }

            if(isset($date['registerTime'])&&$date['registerTime']){
                $time=explode(" - ",$date['registerTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            if(isset($date['expireTime'])&&$date['expireTime']){
                $time=explode(" - ",$date['expireTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['vip_time'] = ['between',[$time1,$time2]];
            }
            if(isset($date['state'])&&$date['state']!='' &&$date['state']!=3){
                    $where['state']=$date['state'];
            }
            $count=Db::name('user_buyno')->where($where)->count('id');
            $buyno_list=UserBuyno::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($buyno_list)$buyno_list = $buyno_list->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
            return $this->notice_date(1,$date);
        }
        return view();
    }

    //银锭
    public function yinding(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            if(!empty($dataid)){
                $where['uid']=$dataid;
            }
            if(isset($date['expireTime'])&&$date['expireTime']){
                $time=explode(" - ",$date['expireTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            if (isset($date['describe']) && $date['describe']) {
                //$where['task_number'] = $date['id'];
                $where['memo'] =['like','%'.trim($date['describe']).'%'];
            }

            $count=Db::name('user_reward_recharge')->where($where)->count('id');
            $reward_recharge=UserRewardRecharge::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($reward_recharge)$notice_list = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //押金
    public function yajin(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            if(!empty($dataid)){
                $where['uid']=$dataid;
            }
            if(isset($date['expireTime'])&&$date['expireTime']){
                $time=explode(" - ",$date['expireTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            if (isset($date['describe']) && $date['describe']) {
                //$where['task_number'] = $date['id'];
                $where['memo'] =['like','%'.$date['describe'].'%'];
            }
            $count=Db::name('user_deposit_recharge')->where($where)->count('id');
            $reward_recharge=UserDepositRecharge::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($reward_recharge)$notice_list = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
            return $this->notice_date(1,$date);
        }
        return view();
    }

    //买号
    public function maihao(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            $where['state']= array('neq',3);
            if(!empty($dataid)){
                $where['uid']=$dataid;
            }
            if(isset($date['expireTime'])&&$date['expireTime']){
                $time=explode(" - ",$date['expireTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            $count=Db::name('user_buyno')->where('uid',$where['uid'])->count();
            $reward_recharge=UserBuyno::where($where)->limit(($page-1)*$limit,$limit)->select();
            if($reward_recharge)$notice_list = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //伪删除买号
    public function del_buyno(){
        $date=input();
        $del=[
            'state'=>3
        ];
        $info=Db::name('user_buyno')->where('id', $date['id'])->find();
        $res = Db::name('user_buyno')->where('id', $date['id'])->update($del);
        if($res){
        $res1=admin_log("删除买号", "管理员{$this->admin_info['user_name']}操作:删除买号旺旺名为{$info['wwid']}");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
            return $this->success('删除买号成功！');
        }else{
            return $this->error('删除买号失败！');
        }
    }
    //推荐
    public function tuijian(){
        return view();
    }
    //违规备注
    public function wgbz(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=Users::where('id',$dataid)->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //修改违规备注
    public  function edit_wgbz(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $edit_note=[
                'note'=>$date['note']
            ];
            $info=Db::name('users')->where('id', $dataid)->find();
            $res = Db::name('users')->where('id', $dataid)->update($edit_note);
            $res1=admin_log("修改违规备注", "管理员{$this->admin_info['user_name']}操作:违规备注买家用户名为{$info['username']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            if($res){
                return $this->success('操作成功！');
            }else{
                return $this->error('操作失败！');
            }
        }
    }
    //修改密码
    public function updatepwd(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id

            if(empty($date['login_pwd'])){
                return $this->error("密码不能为空");
            }
            if(empty($date['login_pwd2'])){
                return $this->error("确认密码不能为空");
            }
            if($date['login_pwd'] != $date['login_pwd2'] ){
                return $this->error("两次密码输入不一致,请检查后重新输入");
            }
            $edit_loginword=[
                'login_pwd'=>md5($date['login_pwd'])
            ];
            $res = Db::name('users')->where('id', $dataid)->update($edit_loginword);
            $info=Db::name('users')->where('id', $dataid)->find();
            $res1=admin_log("修改密码", "管理员{$this->admin_info['user_name']}操作:买家用户名为{$info['username']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            if($res){
                return $this->success('修改成功！');
            }else{
                return $this->error('修改失败！');
            }
        }
        return view();
    }
    //编辑资料 显示资料 账号
    public function bianji(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=Users::where('id',$dataid)->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //编辑资料 修改资料 账号
    public function edit_bianji(){
        if(request()->isPost()){
            $date=input();
            $note=$date['note'];
            $dataid=input('param.id');//获得用户id
            $result = $this->validate($date,'Edituser');
            if(true !== $result){
                return json(['code'=>1,'msg'=>$result]);
            }
            $user_info=Db::name('users')->where('id', $dataid)->find();//买家信息
            if(!$date['vip_time']){
                $date['vip_time']=time();
            }else{
                $date['vip_time']=$date['vip_time']/1000;
            }
         if(trim($date['mobile'])!=$user_info['mobile']){
         $check_mobile=db('users')->where('mobile',$date['mobile'])->value('id');
         if($check_mobile){
          return $this->error('手机号已存在');
         }

         }
            if(trim($date['qq'])!=$user_info['qq']){
                $check_qq=db('users')->where('qq',$date['qq'])->value('id');
                if($check_qq){
                    return $this->error('QQ号已存在');
                }

            }
            if(trim($date['tjuser'])&&trim($date['tjuser'])!=$user_info['tjuser']&&trim($date['tjuser'])!=$user_info['username']&&trim($date['tjuser'])!='无'){
                $check_username=db('users')->where('username',$date['tjuser'])->value('id');
                if($check_username){
                    $update_tjuser['tjuser']=$date['tjuser'];
                    $update_tjuser['tjuser_state']=1;
                }
                $check_sellername=db('seller')->where('seller_name',$date['tjuser'])->value('id');
                if($check_sellername){
                    $update_tjuser['tjuser']=$date['tjuser'];
                    $update_tjuser['tjuser_state']=2;
                }
                if(!isset($update_tjuser['tjuser'])){
                    return $this->error('推荐人不存在！');
                }
                $update_tjuser['update_time']=time();
                $res_tj = Db::name('users')->where('id', $dataid)->update($update_tjuser);
                if($res_tj){
                    $res2 = admin_log("修改推荐人", "管理员{$this->admin_info['user_name']}操作:操作买号用户名为:{$user_info['username']}");
                    if(!$res2) {
                        return $this->error('操作日志写入失败！');
                    }
                }


            }

            if($date['vip_time']>time()){
                $date['vip']=1;
            }else{
                $date['vip']=0;
            }
            if($user_info['vip_time']-$date['vip_time']>0){
                $vtime=date('Y-m-d H:i:s',$date['vip_time']);
               $msg="管理员{$this->admin_info['user_name']}操作:缩短VIP到{$vtime}{$note}";
            }else{
                $vtime=date('Y-m-d H:i:s',$date['vip_time']);
                $msg="管理员{$this->admin_info['user_name']}操作:延时VIP到{$vtime}{$note}";
            }

            $edit_bianji=[
                'mobile'=>$date['mobile'],
                'balance'=>$date['balance'],
                'reward'=>$date['reward'],
                'qq'=>$date['qq'],
                'vip'=>$date['vip'],
                'vip_time'=>$date['vip_time'],
                'mc_task_num'=>$date['mc_task_num'],
            ];
            if(isset($user_info['reward'])&&$user_info['reward']!=$date['reward']){ //判断管理员是否修改银锭
                if($user_info['reward']>$date['reward']){
                    $reward=$user_info['reward']-$date['reward']; //扣除银锭
                    $reward_type="扣除银锭";
                    $reward_from="8";//扣款
                    $reward_price=-$reward;
                }else{
                    $reward=$date['reward']-$user_info['reward']; //充值银锭
                    $reward_type="充值银锭";
                    $reward_from="2";//充值
                    $reward_price=$reward;
                }
            }

            if(isset($user_info['balance'])&&$user_info['balance']!=$date['balance']){ //判断管理员是否修改本金
                if($user_info['balance']>$date['balance']){
                    $balance=$user_info['balance']-$date['balance']; //扣除本金
                    $balance_type="扣除本金";
                    $balance_from="8";//扣款
                    $balance_price=-$balance;
                }else{
                    $balance=$date['balance']-$user_info['balance']; //充值本金
                    $balance_type="充值本金";
                    $balance_from="2";//充值
                    $balance_price=$balance;
                }
            }

            $edit_bianji['update_time']=time();
            $res = Db::name('users')->where('id', $dataid)->update($edit_bianji);

            if($user_info['vip_time']-$date['vip_time']!=0){
            $insert_data['utype']=2;
            $insert_data['uid']=$user_info['id'];
            $insert_data['user_name']=$user_info['username'];;
            $insert_data['create_time']=time();
            $insert_data['expire_time']=$date['vip_time'];
            $insert_data['remarks']=$msg;
            $vipres=db('vip_record')->insert($insert_data);
            if(!$vipres){
                return $this->error('会员日志写入失败！');
            }
            }

            if(isset($reward)&&$reward && $res && !$note){
                $czres=finance($user_info['id'],2,$reward_price,2,$reward_from,"管理员{$this->admin_info['user_name']}操作:{$reward_type}{$reward}元");
            }elseif(isset($reward)&&$reward && $res && $note){
                $czres=finance($user_info['id'],2,$reward_price,2,$reward_from,"管理员{$this->admin_info['user_name']}操作:{$note}");
            }
            if(isset($balance)&&$balance && $res && !$note){
                $czres1=finance($user_info['id'],2,$balance_price,1,$balance_from,"管理员{$this->admin_info['user_name']}操作:{$balance_type}{$balance}元");
            }elseif (isset($balance)&&$balance && $res && $note){
                $czres1=finance($user_info['id'],2,$balance_price,1,$balance_from,"管理员{$this->admin_info['user_name']}操作:{$note}");
            }
            if(isset($czres1)&&$czres1){
                if(isset($balance_type)&&$balance_type){
                    $res1=admin_log($balance_type, "管理员{$this->admin_info['user_name']}操作:操作买号用户名为:{$user_info['username']}");
                }
                if(!$res1) {
                    return $this->error('操作日志写入失败！');
                }
            }
            if(isset($czres)&&$czres) {
                if (isset($reward_type) && $reward_type) {
                    $res2 = admin_log($reward_type, "管理员{$this->admin_info['user_name']}操作:操作买号用户名为:{$user_info['username']}");
                }
                if(!$res2) {
                    return $this->error('操作日志写入失败！');
                }
            }
            return $this->success('操作成功！');
        }
        return view();
    }


    //账户 提现账户
    public function tixian(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            if(!empty($dataid)){
                $where['user_id']=$dataid;
            }
            $count=1;
            $reward_recharge=UserBank::where($where)->limit(($page-1)*$limit,$limit)->select();
            if($reward_recharge)$notice_list = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //修改星级 展示星级
    public function  editstar(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=Users::where('id',$dataid)->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //修改星级 修改星级星级
    public function edit_star(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            if($date['star']==''){
                return $this->error('请填写星级！');
            }
            if($date['star']<0 || $date['star']>5){
                return $this->error('最高5星！');
            }
            $edit_note=[
                'star'=>$date['star']
            ];
            $info=Db::name('users')->where('id', $dataid)->find();
            $res = Db::name('users')->where('id', $dataid)->update($edit_note);
            $res1=admin_log("修改星级", "管理员{$this->admin_info['user_name']}操作:买家用户名为{$info['username']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }else{
                return $this->success('修改成功！');
            }
        }
    }
    //修改星级 展示星级
    public function  editbuynostar(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=UserBuyno::where('id',$dataid)->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //修改星级 修改星级星级
    public function edit_buynostar(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            if($date['star']==''){
                return $this->error('请填写星级！');
            }
            if($date['star']<0 || $date['star']>5){
                return $this->error('最高5星！');
            }
            $edit_note=[
                'star'=>$date['star']
            ];
            $info=Db::name('user_buyno')->where('id', $dataid)->find();
            $res = Db::name('user_buyno')->where('id', $dataid)->update($edit_note);
            if($res){
                $res1=admin_log("修改星级", "管理员{$this->admin_info['user_name']}操作:买号旺旺为{$info['wwid']}");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }else{
                    return $this->success('修改成功！');
                }
            }

        }
    }
    //消息
    public function xiaoxi(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            $where['type']=1;
            if(!empty($dataid)){
                $where['user_id']=$dataid;
            }
            if(isset($date['date'])&&$date['date']){
                $time=explode(" - ",$date['date']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            $count=Db::name('message')->where('type','1')->where('user_id',$where['user_id'])->count();
            $reward_recharge=Message::where($where)->limit(($page-1)*$limit,$limit)->order('create_time desc')->select();
            if($reward_recharge){
                $notice_list = $reward_recharge->toArray();
            }
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$notice_list]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //消息 发布
    public function send_message(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            if(isset($date['type'])&&$date['type']==1){
                $state='1';
            }else if(isset($date['type'])&&$date['type']==0){
                $state='0';
            }
            $addmessage=[
                'state'=>$state,
                'admin_id'=>session('admin_id'),
            ];
            Db::name('message')->where('id', $date['id'])->update($addmessage);
            return json(['code'=>0,'uid'=>$dataid]);
        }
        return view();
    }

    //删除消息
    public function del_message(){
        $date=input();
        $res = Db::name('message')->where('id', $date['id'])->delete();
        $res1=admin_log("删除消息", "删除消息");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        return json(['code'=>0,'uid'=>$date['uid']]);
    }

    //新增消息
    public function xinzheng(){
        $admin_id=session('admin_id');
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $addmessage=[
                'type'=>1,
                'title'=>$date['title'],
                'content'=>$date['content'],
                'create_time'=>time(),
                'user_id'=>$dataid,
                'author'=>session('admin_name'),
                'admin_id'=>$admin_id
            ];
            Db::name('message')->insertGetId($addmessage);
            return json(['code'=>0,'uid'=>$dataid]);
        }
        return view();
    }
    //修改消息
    public function editmessage(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=Message::where('id',$dataid)->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //修改消息 保存修改
    public function editxinzheng(){
        $admin_id=session('admin_id');
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $addmessage=[
                'title'=>$date['title'],
                'content'=>$date['content'],
                'create_time'=>time(),
                'author'=>session('admin_name'),
                'admin_id'=>session('admin_id'),
            ];
            $res=Db::name('message')->where('id', $date['id'])->update($addmessage);
            if($res){
                $this->success('修改成功!');
            }else{
                $this->error('修改失败!');
            }
            return json(['code'=>0,'uid'=>$dataid]);
        }
        return view();
    }
    //买号列表 审核
    public function examine(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=UserBuyno::where('id',$dataid)->find();
            //var_dump($note['uid']['username']);exit;
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //买号列表 审核修改
    public function edit_examine(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $edit_bianji=[
                'state'=>$date['state'],
                'note'=>$date['note']
            ];
            $res = Db::name('user_buyno')->where('id', $dataid)->update($edit_bianji);
            if($res){
                $this->success("审核成功！");
            }else{
                $this->error("审核失败！");
            }
            return json(['code'=>0,'uid'=>$dataid]);
        }
        return view();
    }
    //买号列表 修改
    public function maihaobianji(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=UserBuyno::where('id',$dataid)->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //买号列表 修改
    public function edit_maihaobianji(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            if(!$date['wwid']){
                return $this->error('请填写旺旺');
            }
            if(!$date['alipayname']){
                return  $this->error('请填写支付宝实名');
            }
            if(!$date['addressname']){
                return $this->error('请填写收件人');
            }
            if(!$date['addressphone']){
                return $this->error('请填写手机号');
            }
            $user_info=Db::name('user_buyno')->where('id', $dataid)->find();
            if(trim($date['wwid'])!=$user_info['wwid']){
               // $check_wwid=db('user_buyno')->where('wwid',$date['wwid'])->value('id');
                $check_wwid=db('user_buyno')->where(['wwid'=>$date['wwid'],'id'=>['neq',$user_info['id']]])->value('id');
                if($check_wwid){
                    return $this->error('旺旺已存在');
                }
            }
            $edit_bianji=[
                'wwid'=>$date['wwid'],
                'alipayname'=>$date['alipayname'],
                'addressname'=>$date['addressname'],
                'addressphone'=>$date['addressphone'],
                'addresstext'=>$date['addresstext'],
                'state'=>$date['state'],
                'frozen_time'=>strtotime($date['frozen_time']),
                'note'=>$date['note']

            ];
            if($date['state']==1){
                $msg="审核通过";
            }elseif($date['state']==2){
                $msg="审核不通过";
            }elseif($date['state']==0){
                $msg="未审核";
            }
            if($date['addresspro']){
                $edit_bianji['addresspro']=$date['addresspro'];
            }
            if($date['addresscity']){
                $edit_bianji['addresscity']=$date['addresscity'];
            }
            if($date['addressarea']){
                $edit_bianji['addressarea']=$date['addressarea'];
            }
            if($date['addresspro'] && $date['addresscity'] &&$date['addressarea']&&$date['addresstext']){
                $edit_bianji['detail_address']=$date['addresspro'].'-'.$date['addresscity'].'-'.$date['addressarea'].'-'.$date['addresstext'];
            }else {
                if ($date['addresstext']) {
                    $edit_bianji['detail_address'] = $user_info['addresspro'] . '-' . $user_info['addresscity'] . '-' . $user_info['addressarea'] . '-' . $date['addresstext'];
                }
            }
            $res = Db::name('user_buyno')->where('id', $dataid)->update($edit_bianji);
            if($res){
                if($date['state']!=$user_info['state']){
                $res1=admin_log("审核买号", "管理员{$this->admin_info['user_name']}操作:买号旺旺名为{$user_info['wwid']} {$msg}");
                }else{
                    $res1=admin_log("买号修改", "管理员{$this->admin_info['user_name']}操作:买号旺旺名为{$user_info['wwid']}");
                }
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
               return $this->success('修改成功！');
            }else{
                return $this->error('修改失败！');
            }
        }
        return view();
}
    //买号列表 删除
    public function  delbuyno(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $edit_bianji=[
                'state'=>3,
            ];
            $res = Db::name('user_buyno')->where('id', $dataid)->update($edit_bianji);
            return json(['code'=>0,'uid'=>$dataid]);
        }
        return view();
    }
    public function select(){
        return view();
    }
}
