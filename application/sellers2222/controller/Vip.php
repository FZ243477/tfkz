<?php


namespace app\seller\controller;


use think\Db;
use think\Exception;
use think\Request;
use app\common\model\VipRecord;

class Vip extends Base
{
    /**
     * @notes 会员vip页面
     * @date 2019/7/29
     * @time 16:20
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(){
        $sys = Db::name('system')->value('seller_vip');
        $sys = explode(',',$sys);
        $this->assign('seller',$this->seller);
        $this->assign('sys',$sys);
        $this->assign('menu','1-3');
        return view();



    }
    public function record(Request $request){
        if(request()->isAjax()){
            $data = $request->param();
            $where['uid'] = $this->seller['id'];
            $where['utype'] = 1;
            if($data['datetime']){
                $where['create_time'] = ['between',[strtotime($data['datetime'][0]),strtotime($data['datetime'][1])]];
            }
            $total = Db::name('vip_record')->where($where)->count('id');
            $list = VipRecord::where($where)->order('id desc')->limit(($data['page']-1)*$data['size'],$data['size'])->select();
            if($list)$list = $list->toArray();
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }
        return view();
    }



    /**
     * @notes 充值vip
     * @date 2019/7/29
     * @time 16:20
     * @param Request $request
     * @return bool|void
     */
    public function rechargeVip(Request $request){
        $data = $request->param();
        if(!$data['vip'])return $this->error('请选择要充值的套餐！');
        if(!$data['pay_type'] || !in_array($data['pay_type'],[1,2,3]))return $this->error('请选择支付方式！');
        $sys = Db::name('system')->value('seller_vip');
        $sys = explode(',',$sys);
        if(!isset($sys[$data['vip']-1]))return $this->error('参数有误！');
        $m = $data['vip']*3;
        if($data['pay_type']==1){//支付宝支付
            $id = Db::name('recharge')->order('id desc')->value('id');
            $add['uid'] = $this->seller['id'];
            $add['number'] = '1'.$this->seller['id'].$id;
            $add['user_type'] = 1;
            $add['price'] = $sys[$data['vip']-1];
            $add['type'] = 2;
            $add['create_time'] = time();
            Db::name('recharge')->insert($add);
            //return $this->success('正在跳转到支付宝支付页面',url('Recharge/pay',['price'=>$sys[$data['vip']-1],'title'=>$add['number']]));//TODO:跳转支付
            return $this->success('正在跳转到支付宝支付页面',url('pay/codepay',['money'=>$sys[$data['vip']-1],'data'=>$add['number']]));//TODO:跳转支付
        }
        Db::startTrans();
        try{
            if($data['pay_type']==2){//押金支付
                if($this->seller['balance'] < $sys[$data['vip']-1])throw new Exception('押金余额不足');
                $update['balance'] = $this->seller['balance'] - $sys[$data['vip']-1];
                $update['vip'] = 1;
                $update['vip_time'] = $this->seller['vip_time'] + ($data['vip'] * 90 * 24 * 3600);
                $memo = "购买{$m}个月会员,使用押金：{$sys[$data['vip']-1]}元，到期时间为".date('Y-m-d H:i:s',$update['vip_time']);
                if($this->seller['vip_time'] < time())$update['vip_time'] = time() + ($data['vip'] * 90 * 24 * 3600);
                Db::name('seller')->where(['id'=>$this->seller['id']])->update($update);
                $price_type = 1;
            }
            if($data['pay_type']==3){//银锭支付
                if($this->seller['reward'] < $sys[$data['vip']-1])throw new Exception('银锭余额不足');
                $update['reward'] = $this->seller['reward'] - $sys[$data['vip']-1];
                $update['vip'] = 1;
                $update['vip_time'] = $this->seller['vip_time'] + ($data['vip'] * 90 * 24 * 3600);
                $memo = "购买{$m}个月会员，使用银锭：{$sys[$data['vip']-1]}银锭，到期时间为".date('Y-m-d H:i:s',$update['vip_time']);
                if($this->seller['vip_time'] < time())$update['vip_time'] = time() + ($data['vip'] * 90 * 24 * 3600);
                Db::name('seller')->where(['id'=>$this->seller['id']])->update($update);
                $price_type = 2;
            }
            if(!finance($this->seller['id'],1,-$sys[$data['vip']-1],$price_type,1,$memo))throw new Exception('财务添加失败');
            if(!$this->writeVip($sys[$data['vip']-1],$memo,$update['vip_time']))throw new Exception('添加记录失败');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('支付成功!',url('vip/record'));

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
    private function writeVip($price,$memo,$vip_time){
        $add['uid'] = $this->seller['id'];
        $add['utype'] = 1;
        $add['user_name'] = $this->seller['seller_name'];
        $add['price'] = $price;
        $add['remarks'] = "$memo";
        $add['expire_time'] = $vip_time;
        $add['create_time'] = time();
        $res = Db::name('vip_record')->insert($add);
        return $res;
    }
}