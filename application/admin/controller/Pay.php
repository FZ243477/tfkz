<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Cache;
use think\Exception;
use think\Request;
use think\Session;

/**
 * @package app\admin\controller
 */
class Pay extends Controller
{

    /**
     * Returns the url query as associative array
     *
     * @param    string    query
     * @return    array    params
     */

    /**
     * @notes 支付宝支付回调
     * @date 2019/8/2
     * @time 11:32
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function alipayNotify(Request $request){
        $data = $request->param();
        $tradeNo = isset($data['tradeNo'])?$data['tradeNo']:'';
        $Money = isset($data['Money'])?$data['Money']:0;
        $title = isset($data['title'])?$data['title']:'';
        $memo = isset($data['memo'])?$data['memo']:'';
        $alipay_account = isset($data['alipay_account'])?$data['alipay_account']:'';
        $Gateway = isset($data['Gateway'])?$data['Gateway']:'';
        $Sign = isset($data['Sign'])?$data['Sign']:'';
        if(strtoupper(md5(config('alipay.alidirect_pid') . config('alipay.alidirect_key') . $tradeNo . $Money . $title . $memo)) == strtoupper($Sign)){
            //MD5签名验证通过后，判断订单是否存在，判断订单是否已处理，判断订单金额是否与通知中的金额一致
            //全部验证通过后即可做到账处理，并修改订单状态为已处理
            //以下为示例，您可以删除示例代码编写您自己的代码
            //==================================================================================================
            $result = Db::name('recharge')->where(['number'=>$title,'price'=>$Money,'state'=>0])->find();//读取支付前保存的订单信息
            if(!$result) exit('IncorrectOrder');//订单不存在
            if($result['price']!=$Money) exit('Fail');//实际支付金额不匹配
            Db::startTrans();
            try{
                $recharge_update['trade_no'] = $tradeNo;
                $recharge_update['state'] = 1;
                Db::name('recharge')->where(['id'=>$result['id']])->update($recharge_update);
                if(!in_array($result['type'],[1,2]))throw new Exception('error');
                if(!in_array($result['user_type'],[1,2]))throw new Exception('error');
                if($result['type']==2){
                    if($result['user_type']==1){
                        $sys = Db::name('system')->value('seller_vip');
                        $sys = explode(',',$sys);
                        $vip = 0;
                        foreach ($sys as $key=>$val){
                            if($Money==$val) $vip = $key + 1;
                        }
                        if(!$vip)throw new Exception('购买金额不存在');
                        $seller = Db::name('seller')->where(['id'=>$result['uid']])->find();
                        $update['vip'] = 1;
                        $update['vip_time'] = $seller['vip_time'] + ($vip * 90 * 24 * 3600);
                        if($seller['vip_time'] < time())$update['vip_time'] = time() + ($vip * 90 * 24 * 3600);
                        Db::name('seller')->where(['id'=>$result['uid']])->update($update);
                    }
                    if($result['user_type']==2){
                        $sys = Db::name('system')->value('user_vip');
                        $sys = explode(',',$sys);
                        $vip = 0;
                        foreach ($sys as $key=>$val){
                            if($Money==$val)$vip = $key + 1;
                        }
                        if(!$vip)throw new Exception('error');
                        $users = Db::name('users')->where(['id'=>$result['uid']])->find();
                        $update['vip'] = 1;
                        $update['vip_time'] = $users['vip_time'] + ($vip * 90 * 24 * 3600);
                        if($users['vip_time'] < time())$update['vip_time'] = time() + ($vip * 90 * 24 * 3600);
                        Db::name('users')->where(['id'=>$result['uid']])->update($update);
                    }
                    $m = $vip * 3;
                    $time = date('Y-m-d H:i:s',$update['vip_time']);
                    $memo = "购买{$m}个月会员使用支付宝支付{$Money}元,到期时间{$time}";
                    if(!$this->writeVip($Money,$memo,$update['vip_time'],$result['uid'],$result['user_type']))throw new Exception('error');
                }else{
                    $str = "银锭";
                    if($result['user_type']==1){//商家
                        $seller = Db::name('seller')->where(['id'=>$result['uid']])->find();
                        if(!in_array($result['recharge_type'],[1,2]))throw new Exception('error');
                        if($result['recharge_type']==1){//押金
                            $seller_update['balance'] = $seller['balance'] + $Money;
                            $str = "押金";
                        }
                        if($result['recharge_type']==2){//银锭
                            $seller_update['reward'] = $seller['reward'] + $Money;
                        }
                        Db::name('seller')->where(['id'=>$result['uid']])->update($seller_update);
                    }
                    if($result['user_type']==2){//买手
                        $users = Db::name('users')->where(['id'=>$result['uid']])->find();
                        if(!in_array($result['recharge_type'],[1,2]))throw new Exception('error');
                        if($result['recharge_type']==1){//本金
                            $str = "本金";
                            $users_update['balance'] = $users['balance'] + $Money;
                        }
                        if($result['recharge_type']==2){//银锭
                            $users_update['reward'] = $users['reward'] + $Money;
                        }
                        Db::name('users')->where(['id'=>$result['uid']])->update($users_update);
                    }
                    if(!finance($result['uid'],$result['user_type'],$Money,$result['recharge_type'],2,"支付宝：充值{$str}支付{$Money}元"))throw new Exception('error');
                }
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                Db::name('test')->insert(['test'=>$e]);
                Db::name('test')->insert(['test'=>$this->seller]);

                exit('error');
            }
            exit('Success');//支付成功
            //==================================================================================================
        }else{
            exit('Fail');//Sign签名验证失败
        }
    }

    /**
     * @notes vip充值记录写入
     * @date 2019/7/29
     * @time 16:21
     * @param $price 金额
     * @param $memo 描述
     * @param $vip_time 到期时间
     * @return int|string
     */
    private function writeVip($price,$memo,$vip_time,$uid,$utype){
        if($utype==1){//商家
            $user_name = Db::name('seller')->where(['id'=>$uid])->value('seller_name');
        }else{//买手
            $user_name = Db::name('users')->where(['id'=>$uid])->value('username');
        }
        $add['uid'] = $uid;
        $add['utype'] = $utype;
        $add['user_name'] = $user_name;
        $add['price'] = $price;
        $add['remarks'] = $memo;
        $add['expire_time'] = $vip_time;
        $add['create_time'] = time();
        $res = Db::name('vip_record')->insert($add);
        return $res;
    }

    public function Notify(Request $request)
    {
        $data = $request->param();
        $dd=db('test')->insert(['test'=>json_encode($data)]);
        if($data['key']=="6e48af9cfe058e33e346941a4f83beef"){
            $tradeNo = isset($data['ddh'])?$data['ddh']:'';
            $Money = isset($data['money'])?$data['money']:0;
            $title = isset($data['name'])?$data['name']:'';
            $result = Db::name('recharge')->where(['number'=>$title,'price'=>$Money,'state'=>0])->find();//读取支付前保存的订单信息
            if(!$result) exit('IncorrectOrder');//订单不存在
            if($result['price']!=$Money) exit('Fail');//实际支付金额不匹配
            Db::startTrans();
            try{
                $recharge_update['trade_no'] = $tradeNo;
                $recharge_update['state'] = 1;
                Db::name('recharge')->where(['id'=>$result['id']])->update($recharge_update);
                if(!in_array($result['type'],[1,2]))throw new Exception('error');
                if(!in_array($result['user_type'],[1,2]))throw new Exception('error');
                if($result['type']==2){
                    if($result['user_type']==1){
                        $sys = Db::name('system')->value('seller_vip');
                        $sys = explode(',',$sys);
                        $vip = 0;
                        foreach ($sys as $key=>$val){
                            if($Money==$val) $vip = $key + 1;
                        }
                        if(!$vip)throw new Exception('购买金额不存在');
                        $seller = Db::name('seller')->where(['id'=>$result['uid']])->find();
                        $update['vip'] = 1;
                        $update['vip_time'] = $seller['vip_time'] + ($vip * 90 * 24 * 3600);
                        if($seller['vip_time'] < time())$update['vip_time'] = time() + ($vip * 90 * 24 * 3600);
                        Db::name('seller')->where(['id'=>$result['uid']])->update($update);
                    }
                    if($result['user_type']==2){
                        $sys = Db::name('system')->value('user_vip');
                        $sys = explode(',',$sys);
                        $vip = 0;
                        foreach ($sys as $key=>$val){
                            if($Money==$val)$vip = $key + 1;
                        }
                        if(!$vip)throw new Exception('error');
                        $users = Db::name('users')->where(['id'=>$result['uid']])->find();
                        $update['vip'] = 1;
                        $update['vip_time'] = $users['vip_time'] + ($vip * 90 * 24 * 3600);
                        if($users['vip_time'] < time())$update['vip_time'] = time() + ($vip * 90 * 24 * 3600);
                        Db::name('users')->where(['id'=>$result['uid']])->update($update);
                    }
                    $m = $vip * 3;
                    $time = date('Y-m-d H:i:s',$update['vip_time']);
                    $memo = "购买{$m}个月会员使用支付宝支付{$Money}元,到期时间{$time}";
                    if(!$this->writeVip($Money,$memo,$update['vip_time'],$result['uid'],$result['user_type']))throw new Exception('error');
                }else{
                    $str = "银锭";
                    if($result['user_type']==1){//商家
                        $seller = Db::name('seller')->where(['id'=>$result['uid']])->find();
                        if(!in_array($result['recharge_type'],[1,2]))throw new Exception('error');
                        if($result['recharge_type']==1){//押金
                            $seller_update['balance'] = $seller['balance'] + $Money;
                            $str = "押金";
                        }
                        if($result['recharge_type']==2){//银锭
                            $seller_update['reward'] = $seller['reward'] + $Money;
                        }
                        Db::name('seller')->where(['id'=>$result['uid']])->update($seller_update);
                    }
                    if($result['user_type']==2){//买手
                        $users = Db::name('users')->where(['id'=>$result['uid']])->find();
                        if(!in_array($result['recharge_type'],[1,2]))throw new Exception('error');
                        if($result['recharge_type']==1){//本金
                            $str = "本金";
                            $users_update['balance'] = $users['balance'] + $Money;
                        }
                        if($result['recharge_type']==2){//银锭
                            $users_update['reward'] = $users['reward'] + $Money;
                        }
                        Db::name('users')->where(['id'=>$result['uid']])->update($users_update);
                    }
                    if(!finance($result['uid'],$result['user_type'],$Money,$result['recharge_type'],2,"支付宝：充值{$str}支付{$Money}元"))throw new Exception('error');
                }
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                echo 'error';
            }
            echo "ok";//支付成功
        }
        //  $dd=db('test')->insert(['test'=>json_encode($data)]);
        // dump($dd);exit;
    }


}
