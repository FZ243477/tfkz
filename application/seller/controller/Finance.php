<?php


namespace app\seller\controller;


use think\Db;
use think\Request;
use app\seller\model\SellerCash;
use app\seller\model\SellerDepositRecharge;
use app\seller\model\SellerRewardRecharge;


class Finance extends Base
{
    /**
     * @notes 收支记录页面
     * @date 2019/8/10
     * @time 10:27
     * @return \think\response\View
     */
    public function finance(){
        $this->assign('menu','3-2');
        return view();
    }

    /**
     * @notes 收支记录第一次获取页面数据
     * @date 2019/8/10
     * @time 10:27
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function financeDo(Request $request){
        $data = $request->param();
        $data['size']=10;
        $where['uid'] = $this->seller['id'];
        $total1 = Db::name('seller_deposit_recharge')->where($where)->count('id');
        $list1 = SellerDepositRecharge::where($where)->order('id desc')->limit(0,$data['size'])->select();
        if($list1)$list1 = $list1->toArray();
        $res['list1'] = $list1;
        $res['total1'] = $total1;
        $total2 = Db::name('seller_cash')->where($where)->count('id');
        $list2 = SellerCash::where($where)->order('id desc')->limit(0,$data['size'])->select();
        if($list2)$list2 = $list2->toArray();
        $res['list2'] = $list2;
        $res['total2'] = $total2;
        $total3 = Db::name('seller_reward_recharge')->where($where)->count('id');
        $list3 = SellerRewardRecharge::where($where)->order('id desc')->limit(0,$data['size'])->select();
        if($list3)$list3 = $list3->toArray();
        $res['list3'] = $list3;
        $res['total3'] = $total3;
        $res['config'] = config('finance.sellerFinanceClass');
        return $this->success('success','',$res);
    }


    /**
     * @notes 押金财务数据
     * @date 2019/8/10
     * @time 13:49
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function depositRecharge(Request $request){
        $data = $request->param();
        $where['uid'] = $this->seller['id'];
        if($data['datetime']){
            $where['create_time'] = ['between',[strtotime($data['datetime'][0]),strtotime($data['datetime'][1])]];
        }
        if($data['type']!=''){
            $where['type'] = $data['type'];
        }
        $total = Db::name('seller_deposit_recharge')->where($where)->count('id');
        $list = SellerDepositRecharge::where($where)->order('id desc')->limit(($data['page']-1)*$data['size'],$data['size'])->select();
        if($list)$list = $list->toArray();
        $res['list'] = $list;
        $res['total'] = $total;
        return $this->success('success','',$res);
    }

    /**
     * @notes 银锭财务数据
     * @date 2019/8/10
     * @time 13:50
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rewardRecharge(Request $request){
        $data = $request->param();
        $where['uid'] = $this->seller['id'];
        if($data['datetime']){
            $where['create_time'] = ['between',[strtotime($data['datetime'][0]),strtotime($data['datetime'][1])]];
        }if($data['type']!=''){
            $where['type'] = $data['type'];
        }
        $total = Db::name('seller_reward_recharge')->where($where)->count('id');
        $list = SellerRewardRecharge::where($where)->order('id desc')->limit(($data['page']-1)*$data['size'],$data['size'])->select();
        if($list)$list = $list->toArray();
        $res['list'] = $list;
        $res['total'] = $total;
        return $this->success('success','',$res);
        
    }

    /**
     * @notes 提现数据
     * @date 2019/8/10
     * @time 13:50
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cash(Request $request){
        $data = $request->param();
        $where['uid'] = $this->seller['id'];
        if($data['datetime']){
            $where['create_time'] = ['between',[strtotime($data['datetime'][0]),strtotime($data['datetime'][1])]];
        }
        $total = Db::name('seller_cash')->where($where)->count('id');
        $list = SellerCash::where($where)->order('id desc')->limit(($data['page']-1)*$data['size'],$data['size'])->select();
        if($list)$list = $list->toArray();
        $res['list'] = $list;
        $res['total'] = $total;
        return $this->success('success','',$res);

    }

    /**押金财务导出
     * @notes
     * @date 2019/8/10
     * @time 13:50
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelDeposit(Request $request){
        $data = $request->param();
        $where['uid'] = $this->seller['id'];
        if(isset($data['start']) && $data['start'] && isset($data['end']) && $data['end']){
            $where['create_time'] = ['between',[strtotime($data['start']),strtotime($data['end'])]];
            if(strtotime($data['end']) - strtotime($data['start']) > 31*24*3600){
                exit('最多下载时间区间为31天');
            }
        }
        if(isset($data['type']) && $data['type']!='' && is_numeric($data['type'])){
            $where['type'] = $data['type'];
        }
        $list = SellerDepositRecharge::where($where)->field('price,type,yprice,memo,create_time')->select();
        if($list)$list = $list->toArray();
        $title = ['金额','财务类型','账户余额','财务描述','财务写入时间'];
        Phpexcel::exportExcel($title,$list,'押金财务导出表');
    }

    /** 银锭财务导出
     * @notes
     * @date 2019/8/10
     * @time 13:50
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelReward(Request $request){
        $data = $request->param();
        $where['uid'] = $this->seller['id'];
        if(isset($data['start']) && $data['start'] && isset($data['end']) && $data['end']){
            $where['create_time'] = ['between',[strtotime($data['start']),strtotime($data['end'])]];
            if(strtotime($data['end']) - strtotime($data['start']) > 31*24*3600){
                exit('最多下载时间区间为31天');
            }
        }
        if(isset($data['type']) && $data['type']!='' && is_numeric($data['type'])){
            $where['type'] = $data['type'];
        }
        $list = SellerRewardRecharge::where($where)->field('price,type,yprice,memo,create_time')->select();
        if($list)$list = $list->toArray();
        $title = ['金额','财务类型','账户余额','财务描述','财务写入时间'];
        Phpexcel::exportExcel($title,$list,'银锭财务导出表');
    }

    /**
     * @notes 数据表插入数据
     * @date 2019/8/10
     * @time 13:51
     */
    public function add(){
        set_time_limit(0);
       /* $add['uid'] = 1;
        $add['price'] = 100;
        $add['type'] = 1;
        $add['yprice'] = 0;
        $add['memo'] = "支付宝：100充值押金";*/
        $addd['create_time'] = time();
        $addd['title'] = "煞笔一号";
        $addd['content'] = "煞笔一号的煞笔事件";
        for($i = 0;$i < 600;$i++){
            $addd['create_time'] = $addd['create_time'] + 600;
            Db::name('notice')->insert($addd);
        }
    }
}