<?php


namespace app\admin\controller;

use app\admin\model\Message;
use app\admin\model\Seller as modelseller;
use app\common\model\SellerDepositRecharge;
use app\common\model\SellerRewardRecharge;
use app\admin\model\Shop;
use think\Db;
use app\admin\model\SellerLimit as Blacklist;

class Seller extends Base
{
    /**
     * 商家列表
     */
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

            if(isset($date['username'])&&$date['username']){
                $where['seller_name'] =['like','%'.trim($date['username']).'%'];
                //$where['seller_name'] = $date['username'];
            }

            if(isset($date['qq'])&&$date['qq']){
                //$where['qq'] = $date['qq'];
                $where['qq'] =['like','%'.trim($date['qq']).'%'];
            }

            $count=Db::name('seller')->where($where)->count('id');
            $buy_list=modelseller::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($buy_list)$buy_list = $buy_list->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buy_list]);
            return $this->notice_date(1,$date);
        }
        return view();
    }

    //商家列表 银锭
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
            $count=Db::name('seller_reward_recharge')->where($where)->count('id');
            $reward_recharge=SellerRewardRecharge::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($reward_recharge)$reward_recharge = $reward_recharge->toArray();
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
                $where['memo'] =['like','%'.trim($date['describe']).'%'];
            }
            $count=Db::name('seller_deposit_recharge')->where($where)->count('id');
            $reward_recharge=SellerDepositRecharge::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($reward_recharge)$reward_recharge = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //新增商家
    public function addbuyer(){
        if(request()->isPost()){
            $date=input();
            if($date['login_pwd'] != $date['login_pwd2'] ){
                return $this->error('两次密码输入不一致,请检查后重新输入.');
            }
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

            $check_mobile=db('seller')->where('mobile',$date['mobile'])->value('id');
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
                'seller_name'=>$date['name'],
                'login_pwd'=>md5($date['login_pwd']),
                'mobile'=>$date['mobile'],
                'qq'=>$date['qq'],
                'vip'=>$date['vip'],
                'vip_time'=>$date['vip_time'],
                'balance'=>$date['balance'],
                'reward'=>$date['reward'],
                'create_time'=>time(),
                'invite_code'=>md5(time().rand(0,99999)),
                'state'=>1,
                'note'=>$date['note']
            ];
            $res=Db::name('seller')->insertGetId($adduser);
            if($res){
                $res1=admin_log("新增商家家", "管理员{$this->admin_info['user_name']}操作:新增商家用户名为{$date['name']}");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
                return $this->success('添加成功！');
            }else{
                return $this->success('添加失败！');
            }
        }
        return view();
    }
    //店铺
    public function shop(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            $where['state']= array('neq',3);
            if(!empty($dataid)){
                $where['seller_id']=$dataid;
            }
            if(isset($date['expireTime'])&&$date['expireTime']){
                $time=explode(" - ",$date['expireTime']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            $count=Db::name('shop')->where($where)->count();
            $reward_recharge=shop::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($reward_recharge)$notice_list = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
            return $this->notice_date(1,$date);
        }
        return view();
    }
    //店铺 删除
    public function del_shop(){
        exit;
        $date=input();
        $del=[
            'state'=>3
        ];

        $res = Db::name('shop')->where('id', $date['id'])->update($del);
        return json(['code'=>0]);
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
            $note=modelseller::where('id',$dataid)->find();
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
            $res = Db::name('seller')->where('id', $dataid)->update($edit_note);
            $seller_info=Db::name('seller')->where('id', $dataid)->find();
            $res1=admin_log("违规备注", "管理员{$this->admin_info['user_name']}操作:违规备注商家用户名为{$seller_info['seller_name']}");
            if(!$res1) {
                return $this->error('操作日志写入失败！');
            }
            if($res){
                return $this->success("修改成功！");
            }else{
                return $this->error("修改失败!");
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
            $res = Db::name('seller')->where('id', $dataid)->update($edit_loginword);
            $seller_info=Db::name('seller')->where('id', $dataid)->find();
            $res1=admin_log("修改密码", "管理员{$this->admin_info['user_name']}操作:修改密码商家用户名为{$seller_info['seller_name']}");
            if(!$res1) {
                return $this->error('操作日志写入失败！');
            }
            if($res){
                return $this->success("修改成功!");
            }else{
                return $this->error("修改失败!");
            }
        }
        return view();
    }

    //编辑资料 显示资料 账号
    public function bianji(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=modelseller::where('id',$dataid)->find();
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
            $date['mobile'] = trim($date['mobile']);
            $result = $this->validate($date,'Edituser');
            if(true !== $result){
                return json(['code'=>1,'msg'=>$result]);
            }
            $seller_info=Db::name('seller')->where('id', $dataid)->find();//商家信息
            if(!$date['vip_time']){
                $date['vip_time']=time();
            }else{
                $date['vip_time']=$date['vip_time']/1000;
            }


            if(trim($date['tjuser'])&&trim($date['tjuser'])!=$seller_info['tjuser']&&trim($date['tjuser'])!=$seller_info['seller_name']&&trim($date['tjuser'])!='无'){
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
                $res_tj = Db::name('seller')->where('id', $dataid)->update($update_tjuser);
                if($res_tj){
                    $res2 = admin_log("修改推荐人", "管理员{$this->admin_info['user_name']}操作:操作商家用户名为:{['seller_name']}");
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
            if($seller_info['vip_time']-$date['vip_time']>0){
                $vtime=date('Y-m-d H:i:s',$date['vip_time']);
                $msg="管理员{$this->admin_info['user_name']}操作:缩短VIP到{$vtime}{$note}";
            }else{
                $vtime=date('Y-m-d H:i:s',$date['vip_time']);
                $msg="管理员{$this->admin_info['user_name']}操作:延时VIP到{$vtime}{$note}";
            }
            if(trim($date['mobile'])!=$seller_info['mobile']){
                $check_mobile=db('seller')->where('mobile',$date['mobile'])->value('id');
                if($check_mobile){
                    return $this->error('手机号已存在');
                }
            }
            $edit_bianji=[
                'mobile'=>$date['mobile'],
                'balance'=>$date['balance'],
                'reward'=>$date['reward'],
                'qq'=>$date['qq'],
                'vip'=>$date['vip'],
                'vip_time'=>$date['vip_time'],
            ];
            if(isset($seller_info['reward'])&&$seller_info['reward']!=$date['reward']){ //判断管理员是否修改银锭
                if($seller_info['reward']>$date['reward']){
                    $reward=$seller_info['reward']-$date['reward']; //扣除银锭
                    $reward_type="扣除银锭";
                    $reward_from="8";//扣款
                    $reward_price=-$reward;
                }else{
                    $reward=$date['reward']-$seller_info['reward']; //充值银锭
                    $reward_type="充值银锭";
                    $reward_from="2";//充值
                    $reward_price=$reward;
                }
            }

            if(isset($seller_info['balance'])&&$seller_info['balance']!=$date['balance']){ //判断管理员是否修改本金
                if($seller_info['balance']>$date['balance']){
                    $balance=$seller_info['balance']-$date['balance']; //扣除本金
                    $balance_type="扣除本金";
                    $balance_from="8";//扣款
                    $balance_price=-$balance;
                }else{
                    $balance=$date['balance']-$seller_info['balance']; //充值押金
                    $balance_type="充值押金";
                    $balance_from="2";//充值
                    $balance_price=$balance;
                }
            }

            $edit_bianji['update_time']=time();
            $res = Db::name('seller')->where('id', $dataid)->update($edit_bianji);

            if($seller_info['vip_time']-$date['vip_time']!=0) {
                $insert_data['utype'] = 1;
                $insert_data['uid'] = $seller_info['id'];
                $insert_data['user_name'] = $seller_info['seller_name'];;
                $insert_data['create_time'] = time();
                $insert_data['expire_time'] = $date['vip_time'];
                $insert_data['remarks'] = $msg;
                $vipres = db('vip_record')->insert($insert_data);
                if (!$vipres) {
                    return $this->error('会员日志写入失败！');
                }
            }


            if(isset($reward)&&$reward && $res && !$note){
                $czres=finance($seller_info['id'],1,$reward_price,2,$reward_from,"管理员{$this->admin_info['user_name']}操作:{$reward_type}{$reward}元");
            }elseif(isset($reward)&&$reward && $res && $note){
                $czres=finance($seller_info['id'],1,$reward_price,2,$reward_from,"管理员{$this->admin_info['user_name']}操作:{$note}");
            }
            if(isset($balance)&&$balance && $res && !$note){
                $czres1=finance($seller_info['id'],1,$balance_price,1,$balance_from,"管理员{$this->admin_info['user_name']}操作:{$balance_type}{$balance}元");
            }elseif (isset($balance)&&$balance && $res && $note){
                $czres1=finance($seller_info['id'],1,$balance_price,1,$balance_from,"管理员{$this->admin_info['user_name']}操作:{$note}");
            }
            if(isset($czres1)&&$czres1){
                if(isset($balance_type)&&$balance_type){
                    $res1=admin_log($balance_type, "管理员{$this->admin_info['user_name']}操作:操作商家用户名为:{$seller_info['seller_name']}");
                }
                if(!$res1) {
                    return $this->error('操作日志写入失败！');
                }
            }
            if(isset($czres)&&$czres) {
                if (isset($reward_type) && $reward_type) {
                    $res2 = admin_log($reward_type, "管理员{$this->admin_info['name']}操作:操作商家用户名为:{$seller_info['seller_name']}");
                }
                if(!$res2) {
                    return $this->error('操作日志写入失败！');
                }
            }
            return $this->success('操作成功！');
        }
        return view();
    }

    //消息
    public function xiaoxi(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            if(!empty($dataid)){
                $where['user_id']=$dataid;
            }
            if(isset($date['date'])&&$date['date']){
                $time=explode(" - ",$date['date']);
                $time1=strtotime($time[0]);
                $time2=strtotime($time[1]);
                $where['create_time'] = ['between',[$time1,$time2]];
            }
            $count=Db::name('message')->where('type',2)->where('user_id',$where['user_id'])->count();
            $reward_recharge=Message::where($where)->where('type',2)->limit(($page-1)*$limit,$limit)->order('create_time desc')->select();
            if($reward_recharge)$notice_list = $reward_recharge->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$reward_recharge]);
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
        return json(['code'=>0,'uid'=>$date['uid']]);
    }

    //新增消息
    public function xinzheng(){
        $admin_id=session('admin_id');
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $addmessage=[
                'type'=>2,
                'title'=>$date['title'],
                'content'=>$date['content'],
                'create_time'=>time(),
                'user_id'=>$dataid,
                  'author'=>session('admin_name'),
                'admin_id'=>session('admin_id'),
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
            $res= Db::name('message')->where('id', $date['id'])->update($addmessage);
            if($res){
                $this->success('修改成功!');
            }else{
                $this->error('修改失败!');
            }
            return json(['code'=>0,'uid'=>$dataid]);
        }
        return view();
    }



    /**
     * 店铺列表
     */
    //买号列表
    public function BuyNumberList()
    {
        if(request()->isPost()) {
            $date=input();
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            $where['state']= array('neq',3);
            if(isset($date['name'])&&$date['name']){
                $res = Db::name('seller')->where('seller_name', $date['name'])->find();
                $where['seller_id']=$res['id'];
            }
            if(isset($date['qq'])&&$date['qq']){
                    $wheres['qq'] =['like','%'.trim($date['qq']).'%'];
                    $res = Db::name('seller')->where($wheres)->column('id');
                $where['seller_id']=['in',$res];
            }
            if(isset($date['shopName'])&&$date['shopName']){
                $where['shop_name'] =['like','%'.$date['shopName'].'%'];
               // $where['shop_name']=$date['shopName'];
            }

            if(isset($date['mobile'])&&$date['mobile']){
                $where['mobile'] =['like','%'.$date['mobile'].'%'];
                // $where['shop_name']=$date['shopName'];
            }

            if(isset($date['wangwang'])&&$date['wangwang']){
                $where['wangwang'] =['like','%'.$date['wangwang'].'%'];
                //$where['wangwang']=$date['wangwang'];
            }

            if(isset($date['type'])&&$date['type']!=''){
                $where['type']=$date['type'];

            }
            if(isset($date['logistics'])&&$date['logistics']!=''){
                $where['logistics']=$date['logistics'];

            }

            if(isset($date['state'])&&$date['state']!='' &&$date['state']!=3){
                $where['state']=$date['state'];
            }
            $count=Db::name('shop')->where($where)->count('id');
            $buyno_list=Shop::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($buyno_list)$notice_list = $buyno_list->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
            return $this->notice_date(1,$date);
        }
        return view();
    }

    //店铺列表 审核
    public function examine(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=Shop::where('id',$dataid)->find();
            //var_dump($note['uid']['username']);exit;
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        return view();
    }

    //店铺列表 审核修改
    public function edit_examine(){
        exit;
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $edit_bianji=[
                'state'=>$date['state'],
                'cause'=>$date['note']
            ];
            $res = Db::name('shop')->where('id', $dataid)->update($edit_bianji);
            if($res){
            return $this->success("审核成功！");
            }else{
                return $this->error("审核失败！");
            }
        }
        return view();
    }

    //店铺列表 修改
    public function maihaobianji(){
        if(request()->isPost()) {
            $date = input();
            $dataid=input('param.id');//获得用户id
            $note=Shop::where('id',$dataid)->where(['state'=>['neq',3]])->find();
            return json(['code'=>0,'msg'=>'获取数据成功','data'=>$note]);
            return $this->notice_date(1,$date);
        }
        $delivery_data = db('delivery')->select();
        $this->assign('delivery_data', $delivery_data);
        return view();
    }
    //店铺列表 修改
    public function edit_maihaobianji(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $date['shop_name'] = trim($date['shop_name']);
            $date['wangwang'] = trim($date['wangwang']);
            if(!$date['wangwang']){
                return $this->error('请填写旺旺');
            }
            if(!$date['shop_name']){
                return  $this->error('请填写店铺名');
            }
            if(!$date['link']){
                return $this->error('请填写链接');
            }
            if(!$date['name']){
                return $this->error('请填写发件姓名');
            }
            if(!$date['mobile']){
                return $this->error('请填写发件电话');
            }
            $info=Db::name('shop')->where('id', $dataid)->find();
            if(trim($date['wangwang'])!=$info['wangwang']){
                //$check_wwid=db('shop')->where('wangwang',$date['wangwang'])->value('id');
                $check_wwid=db('shop')->where(['wangwang'=>$date['wangwang'],'id'=>['neq',$info['id']]])->value('id');
                if($check_wwid){
                    return $this->error('旺旺已存在');
                }
            }
            if(trim($date['shop_name'])!=$info['shop_name']){
                $check_shop_name=db('shop')->where(['shop_name'=>$date['shop_name']])->value('id');
                if($check_shop_name){
                    return $this->error('店铺名已存在');
                }
            }
            $edit_bianji=[
                'shop_name'=>$date['shop_name'],
                'type'=>$date['type'],
                'link'=>$date['link'],
                'wangwang'=>$date['wangwang'],
                'name'=>$date['name'],
                'logistics'=>$date['logistics'],
                'mobile'=>$date['mobile'],
                'address'=>$date['address'],
                'state'=>$date['examine_state'],
                'cause'=>$date['note'],
                'code'=>$date['code'],
                'update_time'=>time()
            ];
            if($date['examine_state']==1){
                $msg="审核通过";
            }elseif($date['examine_state']==2){
                $msg="审核不通过";
            }elseif($date['examine_state']==0){
                $msg="未审核";
            }
            if($date['province']){
                $edit_bianji['province']=$date['province'];
            }
            if($date['city']){
                $edit_bianji['city']=$date['city'];
            }
            if($date['district']){
                $edit_bianji['area']=$date['district'];
            }
            if($date['sheng']){
                $edit_bianji['sheng']=$date['sheng'];
            }
            if($date['shi']){
                $edit_bianji['shi']=$date['shi'];
            }
            if($date['qu']){
                $edit_bianji['qu']=$date['qu'];
            }

            $res = Db::name('shop')->where(['id'=>$dataid])->update($edit_bianji);
            if($res){
                if($date['examine_state']!=$info['state']){
                    $res1=admin_log("审核店铺", "管理员{$this->admin_info['user_name']}操作:店铺旺旺名为{$info['wangwang']} {$msg}");
                }else{
                    $res1=admin_log("店铺修改", "管理员{$this->admin_info['user_name']}操作:店铺旺旺名为{$info['wangwang']}");
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

    //店铺列表 删除
    public function  delbuyno(){
        if(request()->isPost()){
            $date=input();
            $dataid=input('param.id');//获得用户id
            $info = Db::name('shop')->where('id', $dataid)->find();
            $edit_bianji=[
                'state'=>3,
                'wangwang'=>$info['wangwang'].$info['id'],
                'shop_name'=>$info['shop_name'].$info['id'],
            ];
            $res = Db::name('shop')->where('id', $dataid)->update($edit_bianji);
            if($res){
                $res1=admin_log("删除店铺", "管理员{$this->admin_info['user_name']}操作:删除店铺旺旺名为{$info['wangwang']}");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
                return $this->success('删除店铺成功！');
            }else{
                return $this->error('删除店铺失败！');
            }
        }
        return view();
    }




//  黑名单列表

    public function BlackList()
    {
        if(request()->isPost()) {
            $date=input();
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $where = [];
            if(isset($date['name'])&&$date['name']){
                $res = Db::name('seller')->where('seller_name', $date['name'])->find();
                $where['seller_id']=$res['id'];
            }
            if(isset($date['shopName'])&&$date['shopName']){
                $where['wangwang']=$date['shopName'];
            }
            $blackuser=array();
            if(isset($date['num'])&&$date['num']){
                $blacklist=db('seller_limit')->select();
                foreach ($blacklist as $v){
                    $count=db('seller_limit')->where(['wangwang'=>$v['wangwang']])->count();
                    if($count>=$date['num']){
                        $blackuser[]=$v['wangwang'];
                    }
                }
                $where['wangwang'] = ['in',$blackuser];
                $count=Db::name('seller_limit')->where($where)->count('id');
                $buyno_list=Blacklist::where($where)->limit(($page-1)*$limit,$limit)->order('wangwang desc')->select();
                if($buyno_list)$notice_list = $buyno_list->toArray();
                return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
            }
            if(isset($date['state'])&&$date['state']!=""){
                    $where['status']=$date['state'];
            }
            $count=Db::name('seller_limit')->where($where)->count('id');
            $buyno_list=Blacklist::where($where)->limit(($page-1)*$limit,$limit)->order('id desc')->select();
            if($buyno_list)$notice_list = $buyno_list->toArray();
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
        }
        return view();
    }

//    黑名单审核
    public function BlackListExamine(){
        $data=input();
        if(!$data['id']){
            $this->error('参数错误！');
        }
        $data_info=db('seller_limit')->where('id',$data['id'])->find();
        $this->assign('data_info',$data_info);
        return view();
    }


    //    黑名单审核
    public function to_examine(){
        $data=input();
        if (!$data['id'] || $data['state']=='') {
            return $this->error('参数错误！');
        }
        $data_info=db('seller_limit')->where('id',$data['id'])->find();
        if(!$data_info){
            return $this->error('记录不存在！');
        }
        $update_data['status']=$data['state'];
        $update_data['remarks']=$data['remarks'];
        $update_data['update_time']=time();
        $res = db('seller_limit')->where('id', $data['id'])->update($update_data);
        if($res){
            $res1=admin_log("黑名单审核", "管理员{$this->admin_info['user_name']}操作:审核旺旺{$data_info['wangwang']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return $this->success('审核成功！');
        }else{
            return $this->error('审核失败！');
        }

    }


    //黑名单 删除
    public function  delblacklist(){
        if(request()->isPost()){
            $dataid=input('param.id');//获得用户id
            $seller_limit_info = Db::name('seller_limit')->where('id', $dataid)->find();
            if(!$seller_limit_info){
                return $this->error('记录不存在！');
            }
           $res=Db::name('seller_limit')->where('id', $dataid)->delete();
            if($res){
                $res1=admin_log("黑名单删除", "管理员{$this->admin_info['user_name']}操作:删除旺旺{$seller_limit_info['wangwang']}");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
                return $this->success('删除成功！');
            }else{
                return $this->error('删除失败！');
            }
        }
    }

}
