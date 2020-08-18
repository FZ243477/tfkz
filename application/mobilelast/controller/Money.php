<?php
namespace app\mobile\controller;
use app\seller\controller\Phpexcel;
use think\Controller;
use think\Db;
use app\common\model\Sms; //引入手机验证码
use app\common\controller\Img;
use think\Request;
use think\Session;
use app\common\model\UserDepositRecharge;
use app\common\model\UserRewardRecharge;

class Money extends Base
{

    public function withsilver(Request $request){
        $editpass=input('param.editpass');//顶部导航
        $this->assign('editpass',$editpass);
        $show=input('param.show');//切换选项卡为2
        $this->assign('show',$show);
        if(request()->isAjax()) {
            $data = $request->param();
            $where['uid'] = $this->id;
           if (!empty($data['datetime1']) || !empty($data['datetime2'])) {
               $datetime1=$data['datetime1']/1000;
               $datetime2=$data['datetime2']/1000;
               $datetime1=date('Y-m-d',$datetime1);
               $datetime2=date('Y-m-d',$datetime2);
                $where['create_time'] = ['between',[strtotime($datetime1),strtotime($datetime2)]];
            }
            $total = Db::name('user_reward_recharge')->where($where)->count('id');
            $list = Db::name('user_reward_recharge')->where($where)->limit(($data['page']-1)*10,10)->order('id desc')->select()->toArray();
            foreach ($list as $k =>$v){
                $list[$k]['creata_time']=date('Y-m-d H:i:s',$v['create_time']);
            }
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }
        $topnav=3;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }
    public function drawal_record(Request $request){
        if(request()->isAjax()) {
            $data = $request->param();
            $where['uid'] = $this->id;
            $where['type'] =1;
            if (!empty($data['datetime1']) || !empty($data['datetime2'])) {
                $datetime1=$data['datetime1']/1000;
                $datetime2=$data['datetime2']/1000;
                $datetime1=date('Y-m-d',$datetime1);
                $datetime2=date('Y-m-d',$datetime2);
                $where['create_time'] = ['between',[strtotime($datetime1),strtotime($datetime2)]];
            }
            $total = Db::name('user_cash')->where($where)->count('id');
            $list = Db::name('user_cash')->where($where)
                ->limit(($data['page']-1)*10,10)
                ->order('id desc')
                ->select()
                ->toArray();
            foreach($list as &$item){
                $state_array=array(
                    '0'=>"待审核",
                    '1'=>"已审核待转账",
                    '2'=>"拒绝",
                    '3'=>"转账成功"
                );
                $item['state']=$state_array[$item['state']];
                $item['create_time']=date('Y-m-d H:i:s',$item['create_time']);
            }
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }

        return view();
    }

    public function silver_record(Request $request){
        if(request()->isAjax()) {
            $data = $request->param();
            $where['uid'] = $this->id;
            $where['type'] =2;
            if (!empty($data['datetime1']) || !empty($data['datetime2'])) {
                $datetime1=$data['datetime1']/1000;
                $datetime2=$data['datetime2']/1000;
                $datetime1=date('Y-m-d',$datetime1);
                $datetime2=date('Y-m-d',$datetime2);
                $where['create_time'] = ['between',[strtotime($datetime1),strtotime($datetime2)]];
            }
            $total = Db::name('user_cash')->where($where)->count('id');
            $list = Db::name('user_cash')->where($where)
                ->limit(($data['page']-1)*10,10)
                ->order('id desc')
                ->select()
                ->toArray();
            foreach($list as &$item){
                $state_array=array(
                    '0'=>"待审核",
                    '1'=>"已审核待转账",
                    '2'=>"拒绝",
                    '3'=>"转账成功"
                );
                $item['state']=$state_array[$item['state']];
                $item['create_time']=date('Y-m-d H:i:s',$item['create_time']);
            }
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }
        $topnav=3;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }
    public function commission(Request $request){
        if(request()->isAjax()) {
            $data = $request->param();
            $where['uid'] = $this->id;
            $where['type'] =4;
            if($data['inputcontent']){
                $where['id']=$data['inputcontent'];
            }else if ($data['datetime']) {
                $where['create_time'] = ['between',[strtotime($data['datetime'][0]),strtotime($data['datetime'][1])]];
            }
            $total = UserRewardRecharge::where($where)->limit(($data['page']-1)*$data['size'],$data['size'])->count('id');
            $list = UserRewardRecharge::where($where)->limit(($data['page']-1)*$data['size'],$data['size'])->order('id desc')->select()->toArray();
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }
        return view();
    }

    //充值生成order 支付宝支付 充值
    public function creat_order(){
        $data=input();
        $random = rand(100000,999999);
        $number=$random.time();
        $id = Db::name('recharge')->order('id desc')->value('id');
        $info = Db::name('recharge')->where(['uid'=>$this->id,'user_type'=>2,'state'=>0])->order('id desc')->find();
        if(time()-$info['create_time']<360){
            return $this->error('对不起,上一单未支付请等待6分钟再次充值！');
        }
        $number='2'.$this->id.$id;
        $order=[
            'uid'=>$this->id,
            'number'=>$number,
            'user_type'=>$data['user_type'],
            'recharge_type'=>$data['recharge_type'],
            'type'=>$data['type'],
            'price'=>$data['price'],
            'state'=>0,
            'create_time'=>time(),
        ];
        Db::name('recharge')->insertGetId($order);
        //return $this->success('正在跳转支付',url('Money/pay',['title'=>$number,'price'=>$order['price']]));
        return $this->success('正在跳转到支付宝支付页面',url('pay/codepay',['money'=>$order['price'],'data'=>$number]));
    }
    //本金支付
    public function principal(){
        $data=input();
        $user=Db::name('users')->where('id',$this->id)->find();
        if($data['price']>$user['balance']){
            return $this->error('对不起本金不足，请检查后重新输入');
        }else{
            $change_num_balance=$user['balance']-$data['price'];
            $change_num_reward=$user['reward']+$data['price'];
        }
        $balance=[
            'balance'=>$change_num_balance,
            'reward'=>$change_num_reward
        ];
        Db::startTrans();
        try {
            $res = Db::name('users')->where('id', $this->id)->update($balance);
            if($res) {
                finance($this->id, 2, -$data['price'], 1, $data['type'], "减少{$data['price']}元本金充值银锭,银锭增加{$data['price']}银锭");
                finance($this->id, 2, $data['price'], 2, $data['type'], "减少{$data['price']}元本金充值银锭,银锭增加{$data['price']}银锭");
                Db::commit();
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->error('操作失败');
        }
        return $this->success('充值成功','Money/withsilver');
    }

    //支付免签支付
    public function pay(){
        $data=input();
        $optEmail = config('alipay.alidirect_account');//isset($_REQUEST["optEmail"]) ? $_REQUEST["optEmail"] : "";
        $payAmount = $data['price'];//isset($_REQUEST["payAmount"]) ? $_REQUEST["payAmount"] : "";
        $title = $data['title'];//isset($_REQUEST["title"]) ? $_REQUEST["title"] : "";
        $memo = '';//isset($_REQUEST["memo"]) ? $_REQUEST["memo"] : "";
        $act = '';//isset($_REQUEST["act"]) ? $_REQUEST["act"] : "";
        $this->assign('optEmail',$optEmail);
        $this->assign('payAmount',$payAmount);
        $this->assign('title',$title);
        $this->assign('memo',$memo);
        $this->assign('act',$act);
        return view();
    }
    //本金提现
    public function withdrawal(Request $request){
        $editpass=input('param.editpass');//本金记录跳转导航
        $this->assign('editpass',$editpass);
        $show=input('param.show');//切换选项卡为2
        $this->assign('show',$show);
        $ydtx=input('param.ydtx');//银锭提现跳转导航
        $this->assign('ydtx',$ydtx);
        $admin_limit=Db::name('system')->where('id',1)->find();
        $this->assign('admin_limit',$admin_limit);//后台配置
        if(request()->isAjax()) {
            $data = $request->param();
            $where['uid'] = $this->id;
            if (!empty($data['datetime1']) || !empty($data['datetime2'])) {
                $datetime1=$data['datetime1']/1000;
                $datetime2=$data['datetime2']/1000;
                $datetime1=date('Y-m-d',$datetime1);
                $datetime2=date('Y-m-d',$datetime2);
                $where['create_time'] = ['between',[strtotime($datetime1),strtotime($datetime2)]];
            }
            $total = Db::name('user_deposit_recharge')->where($where)->count('id');
            $list = Db::name('user_deposit_recharge')->where($where)->limit(($data['page']-1)*10,10)->order('id desc')->select()->toArray();
           foreach ($list as $k =>$v){
                   $list[$k]['creata_time']=date('Y-m-d H:i:s',$v['create_time']);
           }
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }
        $topnav=4;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }
    //本金记录 搜索 search
    public function search_principal(){
        $data=input();
        if(!empty($data['searchInput'])){
            $search_principal=Db::name('user_deposit_recharge')
                ->where('id',$data['searchInput'])
                ->where('uid',$this->id)
                ->field('id,price,yprice,create_time,memo')
                ->find();
            return $this->assign('search_principal',$search_principal);
            //return $this->error('操作失败');
        }else{
            return $this->error('操作失败');
        }
    }
    //提现
    public function creat_withdrawal(){
        $data=input();
        $user=Db::name('users')->where('id',$this->id)->find();
        $user_bank=Db::name('user_bank')->where('user_id',$this->id)->find();
        $bank=Db::name('bank')->where('id',$user_bank['bank_id'])->find();
        if(!$user_bank){
            return $this->error('请先绑定银行卡！','my/withdrawal');
        }
        if($user_bank['state']==0){
            return $this->error('银行卡正在审核中，无法提现！');
        }
        if($user_bank['state']==2){
            return $this->error('银行卡已被禁用，无法提现！');
        }
        if($data['radio']==1){
            if($data['price']>$user['balance']){
                return $this->error('对不起本金不足，请检查后重新输入');
            }
        }else if($data['radio']==2){
            if($data['price']>$user['reward']){
                return $this->error('对不起银锭不足，请检查后重新输入');
            }
        }
        if(!$user['pay_pwd']){
            return $this->error('请先设置支付密码!','my/information');
        }
        if($user['pay_pwd'] != md5($data['password'])){
            return $this->error('支付密码错误，请检查后重新输入');
        }
        $admin_system=Db::name('system')->where('id',1)->find();
        if($admin_system['user_fee_max_price']>=$data['price']){
            $poundage=1;
            $toaccount=$data['price']-$poundage;
        }else{
            $poundage=0;
            $toaccount=$data['price'];
        }
        Db::startTrans();
        try{
        if($data['radio'] == 1){
            $price_num=$user['balance']-$data['price'];
            $price_change=[
                'balance'=>$price_num
            ];
            Db::name('users')->where('id',$this->id)->update($price_change);
            $cash=[
                'uid'=>$this->id,
                'price'=>$data['price'],
                'yprice'=>$price_num,
                'type'=>$data['radio'],
                'state'=>0,
                'create_time'=>time(),
                'poundage'=>$poundage,
                'toaccount'=>$toaccount,
                'bank_number'=>$user_bank['bank_no'],
                'bank_name'=>$bank['name'],
                'bank_seller'=>$user_bank['bank_user'],
                'mobile'=>$user_bank['mobile']
            ];
            Db::name('user_cash')->insertGetId($cash);
            finance($this->id, 2, -$data['price'], 1,3,"{$user['username']} 取现{$data['price']}元，手续费{$poundage}元");
        }else if($data['radio'] == 2){
            $price_num=$user['reward']-$data['price'];
            $admin_limit=Db::name('system')->where('id',1)->find();
            $money_really=$data['price']*$admin_limit['reward_price'];
            $price_change=[
                'reward'=>$price_num
            ];
            $poundage=$data['price']*(1-$admin_limit['reward_price']);
            Db::name('users')->where('id',$this->id)->update($price_change);
            $cash=[
                'uid'=>$this->id,
                'price'=>$data['price'],
                'yprice'=>$price_num,
                'type'=>$data['radio'],
                'state'=>0,
                'create_time'=>time(),
                'poundage'=>$poundage,
                'toaccount'=>$money_really,
                'bank_number'=>$user_bank['bank_no'],
                'bank_name'=>$bank['name'],
                'bank_seller'=>$user_bank['bank_user'],
                'mobile'=>$user_bank['mobile']
            ];
            Db::name('user_cash')->insertGetId($cash);
            finance($this->id, 2, -$data['price'], 2,3,"{$user['username']} 提取银锭{$data['price']}银锭，即现金{$money_really}元");
        }

        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->error('操作失败');
        }
        return $this->success('提现申请提交成功正在跳转','Money/withdrawal');
    }

    /**
     * @notes 押金财务导出
     * @date 2019/10/14
     * @time 10:22
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelDeposit(Request $request){
        $data = $request->param();
        $where['uid'] = $this->id;
        if(isset($data['start']) && $data['start'] && isset($data['end']) && $data['end']){
            $where['create_time'] = ['between',[strtotime($data['start']),strtotime($data['end'])]];
        }
        $list = UserDepositRecharge::where($where)->field('price,type,yprice,memo,create_time')->select();
        if($list)$list = $list->toArray();
        $title = ['金额','财务类型','当前账户余额','财务描述','财务写入时间'];
        Phpexcel::exportExcel($title,$list,'押金财务导出表');
    }

    /**
     * @notes 银锭财务导出
     * @date 2019/10/14
     * @time 10:23
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelReward(Request $request){
        $data = $request->param();
        $where['uid'] = $this->id;
        if(isset($data['start']) && $data['start'] && isset($data['end']) && $data['end']){
            $where['create_time'] = ['between',[strtotime($data['start']),strtotime($data['end'])]];
        }
        $list = UserRewardRecharge::where($where)->field('price,type,yprice,memo,create_time')->select();
        if($list)$list = $list->toArray();
        $title = ['金额','财务类型','当前账户余额','财务描述','财务写入时间'];
        Phpexcel::exportExcel($title,$list,'银锭财务导出表');
    }
}
