<?php


namespace app\seller\controller;


use think\Db;
use think\Exception;
use think\Request;

class Recharge extends Base
{
    public function index(){

    }

    /**
     * @notes 充值页面
     * @date 2019/8/1
     * @time 16:54
     * @return \think\response\View
     */
    public function recharge(){
        $this->assign('seller',$this->seller);
        $this->assign('menu','3-1');
        return view();
    }

    /**
     * @notes 账户充值接口
     * @date 2019/8/9
     * @time 11:49
     * @param Request $request
     */
    public function rechargeDo(Request $request){
        $data = $request->param();
        if(!isset($data['type']) || !in_array($data['type'],[1,2]))return $this->error('参数错误1');
        if(!isset($data['price']) || $data['price'] <= 0 || !is_numeric($data['price']))return $this->error('请选择支付金额');

        if($data['type'] == 1){//支付宝支付充值押金
            try{
                Db::startTrans();
                $id = Db::name('recharge')->order('id desc')->value('id');
                $add['uid'] = $this->seller['id'];
                $add['number'] = '1'.$this->seller['id'].$id;
                $add['user_type'] = 1;
                $add['type'] = 1;
                $add['price'] = $data['price'];
                $add['create_time'] = time();
                Db::name('recharge')->insert($add);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return $this->error($e->getMessage());
            }
            ///return $this->success('正在跳转到支付页面',url('Recharge/pay',['price'=>$data['price'],'title'=>$add['number']]));//TODO:跳转支付
            return $this->success('正在跳转到支付宝支付页面',url('pay/codepay',['money'=>$data['price'],'data'=>$add['number']]));//TODO:跳转支付
        }
        if($data['type'] == 2){
            if(!isset($data['pay_type']) || !in_array($data['pay_type'],[1,2]))return $this->error('参数错误2');
            if($data['pay_type'] == 1){//支付宝支付充值银锭
                try{
                    Db::startTrans();
                    $id = Db::name('recharge')->order('id desc')->value('id');
                    $add['uid'] = $this->seller['id'];
                    $add['number'] = '1'.$this->seller['id'].$id;
                    $add['user_type'] = 1;
                    $add['recharge_type'] = 2;
                    $add['type'] = 1;
                    $add['price'] = $data['price'];
                    $add['create_time'] = time();
                    Db::name('recharge')->insert($add);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    return $this->error($e->getMessage());
                }
                //return $this->success('正在跳转到支付页面',url('Recharge/pay',['price'=>$data['price'],'title'=>$add['number']]));//TODO:跳转支付
                return $this->success('正在跳转到支付宝支付页面',url('pay/codepay',['money'=>$data['price'],'data'=>$add['number']]));
            }
            if($data['pay_type'] == 2){//押金支付

                try{
                    Db::startTrans();
                    if($this->seller['balance'] < $data['price'])throw new Exception('押金余额不足');
                    $update['balance'] = $this->seller['balance'] - $data['price'];
                    $update['reward'] = $this->seller['reward'] + $data['price'];
                    Db::name('seller')->where(['id'=>$this->seller['id']])->update($update);
                    if(!finance($this->seller['id'],1,-$data['price'],1,2,"使用押金充值银锭扣除押金{$data['price']}元！"))throw new Exception('押金写入财务失败！');
                    if(!finance($this->seller['id'],1,$data['price'],2,2,"使用押金充值银锭新增银锭{$data['price']}银锭！"))throw new Exception('银锭写入财务失败！');
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    return $this->error($e->getMessage());
                }
                return $this->success('充值成功',url('Finance/finance'));
            }
        }

    }

    /**
     * @notes 支付页面
     * @date 2019/8/9
     * @time 12:16
     * @param Request $request
     * @return \think\response\View
     */
    public function pay(Request $request){
        $data = $request->param();
        $optEmail = config('alipay.alidirect_account');
        $payAmount = isset($data["price"]) ? $data["price"] : "0.01";
        $title = Db::name('recharge')->where(['user_type'=>1,'uid'=>$this->seller['id']])->order('number desc')->value('number');
        $title = $title ? $title+1 : $this->seller['id'].'100';
        $title = isset($data["title"]) ? $data["title"] : $title;
        $memo = "";
        $act = "";
        $this->assign('optEmail',$optEmail);
        $this->assign('payAmount',$payAmount);
        $this->assign('title',$title);
        $this->assign('memo',$memo);
        $this->assign('act',$act);
        return view();
    }

    /**
     * @notes 押金提现页面
     * @date 2019/8/9
     * @time 17:01
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cash(){
        $config = Db::name('system')->field('seller_min_money,seller_cash_fee')->find();
        $this->assign('seller',$this->seller);
        $this->assign('config',$config);
        $this->assign('menu','3-3');
        return view();
    }

    /**
     * @notes 押金提现接口
     * @date 2019/8/9
     * @time 17:02
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashDo(Request $request){
        $data = $request->param();
        if(!isset($data['price']) || !$data['price'])return $this->error('请正确的填写提现金额');
        if($data['price'] <= 0)return $this->error('金额只能为正数!');
        if(!is_numeric($data['price']))return $this->error('金额只能为数字!');
        if(!isset($data['pay_pwd']) || !$data['pay_pwd'])return $this->error('请输入支付密码');
        $seller = Db::name('seller')->where(['id'=>$this->seller['id']])->find();
        if(!$seller['pay_pwd'])return $this->error('您还为设置支付密码,  请先去设置');
        if($seller['pay_pwd']!=md5($data['pay_pwd']))return $this->error('支付密码不正确');
        $seller_bank = Db::table('tfkz_seller_bank')->alias('a')->join('tfkz_bank b','b.id = a.bank_id')->where(['a.uid'=>$seller['id'],'a.state'=>1])->field('a.bank_seller,a.mobile,a.bank_number,b.name')->find();
        if(!$seller_bank)return $this->error('未添加银行卡或银行卡未通过审核！');
        $config = Db::name('system')->field('seller_min_money,seller_cash_fee')->find();
        if($data['price'] < $config['seller_min_money'])return $this->error("最小提现金额为{$config['seller_min_money']}");
        if($data['price'] > $seller['balance'])return $this->error('账户余额不足！');
        try{
            Db::startTrans();
            $add['uid'] = $seller['id'];
            $add['price'] = $data['price'];
            $add['bank_name'] = $seller_bank['name'];
            $add['bank_number'] = $seller_bank['bank_number'];
            $add['bank_seller'] = $seller_bank['bank_seller'];
            $add['mobile'] = $seller_bank['mobile'];
            $add['yprice'] = $seller['balance'] - $data['price'];
            $add['poundage'] = round($data['price']*$config['seller_cash_fee'],2);  //feiyong
            $add['toaccount'] = $data['price'] - $add['poundage']; //到账金额
            $add['create_time'] = time();
            Db::name('seller_cash')->insert($add);
            Db::name('seller')->where(['id'=>$this->seller['id']])->update(['balance'=>$add['yprice']]);
            if(!finance($seller['id'],1,-$data['price'],1,3,"提现押金扣除押金{$data['price']}元"))throw new Exception('财务写入错误');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('提现申请成功，耐心等待审核',url('Finance/finance',['state'=>2]));
    }
}