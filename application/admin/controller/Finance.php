<?php

//财务
namespace app\admin\controller;


use app\common\model\Recharge;
use app\common\model\SellerBank;
use app\common\model\UserBank;
use think\Controller;
use think\Db;
use think\Cache;
use think\Exception;
use think\Request;
use think\Session;
use app\common\model\SellerCash;
use app\common\model\UserCash;
use app\admin\model\SellerCash as cashseller;
use app\admin\model\UserCash as cashuser;
use app\common\model\UserDepositRecharge;
use app\common\model\UserRewardRecharge;
use app\common\model\SellerDepositRecharge;
use app\common\model\SellerRewardRecharge;
use app\common\model\VipRecord;
use app\seller\controller\Phpexcel;

class Finance extends Base
{
    /**
     * 财务记录
     */
    public function index()
    {
        $data = input();
        if ($data['user_type'] == 1) {     //商家财务记录
            $finace_table = 'seller_cash';

        } elseif ($data['user_type'] == 2) {  //买手财务记录
            $finace_table = 'user_cash';
        }
        $finace_list = db($finace_table)->select();
        $this->assign('finace_list', $finace_list);
    }

    /**
     * @return mixed
     * 提现审核列表
     */
    public function PresentationAudit()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['state']) && $date['state'] != '') {
                $where['state'] = $date['state'];
            }
            if (isset($date['type']) && $date['type'] == 2) {
                if (isset($date['name']) && $date['name']) {
                    $uid = db('seller')->where('seller_name', trim($date['name']))->value('id');
                    $where['uid'] = $uid;
                }
                if (isset($date['phone']) && $date['phone']) {
                    $where['mobile'] = trim($date['phone']);
                }
                if (isset($date['time']) && $date['time']) {
                    $time = explode(" - ", $date['time']);
                    $time1 = strtotime($time[0]);
                    $time2 = strtotime($time[1]);
                    $where['create_time'] = ['between', [$time1, $time2]];
                }
                if (isset($date['bank_seller']) && $date['bank_seller']) {
                    //$where['bank_seller'] = $date['bank_seller'];
                    $where['bank_seller'] =['like','%'.trim($date['bank_seller']).'%'];
                }
                if (isset($date['account']) && $date['account']) {
                    $where['bank_number'] =['like','%'.trim($date['account']).'%'];
                    //$where['bank_number'] = $date['account'];
                }
                $dable = SellerCash::where(['id' => ['gt', 0]]);
                $count = Db::name('seller_cash')->where($where)->count('id');
                $notice_list = SellerCash::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,type,yprice,state,memo,create_time,poundage,toaccount,bank_seller,mobile,bank_number,bank_name')->order('id desc')->select();
            } else {
                if (isset($date['name']) && $date['name']) {
                    $uid = db('users')->where('username', trim($date['name']))->value('id');
                    $where['uid'] = $uid;
                }
                if (isset($date['phone']) && $date['phone']) {
                    $where['mobile'] = trim($date['phone']);
                }
                if (isset($date['bank_seller']) && $date['bank_seller']) {
                   // $where['bank_seller'] = $date['bank_seller'];
                    $where['bank_seller'] =['like','%'.trim($date['bank_seller']).'%'];
                }
                if (isset($date['account']) && $date['account']) {
                    $where['bank_number'] =['like','%'.trim($date['account']).'%'];
                    //$where['bank_number'] = $date['account'];
                }
                if (isset($date['time']) && $date['time']) {
                    $time = explode(" - ", $date['time']);
                    $time1 = strtotime($time[0]);
                    $time2 = strtotime($time[1]);
                    $where['create_time'] = ['between', [$time1, $time2]];
                }
                $count = Db::name('user_cash')->where($where)->count('id');
                $notice_list = UserCash::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,type,yprice,state,memo,create_time,poundage,toaccount,bank_seller,mobile,bank_number,bank_name')->order('id desc')->select();
            }
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 提现审核
     */
    public function Cash_to_examine()
    {
        if (request()->isPost()) {
            $data = input();
            $data = $data['data'];
            if (!$data['id'] || $data['state'] == '' || !$data['user_type']) {
                return $this->error('参数错误！');
            }
            $update_data['state'] = $data['state'];
            $update_data['memo'] = $data['memo'];
            $update_data['examine_time'] = time();
            if ($data['user_type'] == "商家") {
                $data_info = db('seller_cash')->where('id', $data['id'])->find();
            }
            if ($data['user_type'] == "买手") {
                $data_info = db('user_cash')->where('id', $data['id'])->find();
            }
            if ($data_info['state'] == 1 && $data['state'] == 0) {
                return $this->error('通过后不能改为待审核！');
            }
            if ($data_info['state'] == 0 && $data['state'] == 3) {
                return $this->error('申请中不能改为已返款！');
            }
            if ($data_info['state'] == 1 && $data['state'] == 2) {
                return $this->error('通过后不能改为拒绝！');
            }
            if ($data_info['state'] == 2) {
                return $this->error('拒绝后不能修改状态！');
            }
            if ($data_info['state'] == 3) {
                return $this->error('返款后不能修改状态！');
            }
            try {
                Db::startTrans();
                if ($data['user_type'] == "商家") {
                    $res = db('seller_cash')->where('id', $data['id'])->update($update_data);
                }
                if ($data['user_type'] == "买手") {
                    $res = db('user_cash')->where('id', $data['id'])->update($update_data);
                }
                if (!$res) {
                    throw new Exception('修改状态失败!');
                }
                if ($data['state'] == 2) {
                    if ($data_info['user_type'] == 1) { //拒绝商家提现，退钱
                        $seller_info = db('seller_name')->where('id', $data_info['uid'])->find();
                        if (!$seller_info) {
                            throw new Exception('退钱商家不存在！');
                        }
                        $incres = db('seller_name')->where('id', $data_info['uid'])->setInc('balance', $data_info['price']);
                        if (!$incres) {
                            throw new Exception('退钱给商家出错！');
                        }
                        $d1 = finance($data_info['uid'], 1, $data_info['price'], 1, 12, "管理员{$this->admin_info['user_name']}操作:,拒绝提现退款{$data_info['price']}");
                        if (!$d1) {
                            throw new Exception('写入财务出错!');
                        }
                    } else {
                        $user_info = db('users')->where('id', $data_info['uid'])->find();
                        if (!$user_info) {
                            throw new Exception('退钱买手不存在！');
                        }
                        if ($data_info['type'] == 1) {
                            $incuser = db('users')->where('id', $data_info['uid'])->setInc('balance', $data_info['price']);
                            $inctype = 1;
                        } else {
                            $incuser = db('users')->where('id', $data_info['uid'])->setInc('reward', $data_info['price']);
                            $inctype = 2;
                        }
                        if (!$incuser) {
                            throw new Exception('退钱给买手出错！');
                        }
                        $d2 = finance($data_info['uid'], 2, $data_info['price'], $inctype, 12, "管理员{$this->admin_info['user_name']}操作:,拒绝提现退款{$data_info['price']}");
                        if (!$d2) {
                            throw new Exception('写入财务出错!');
                        }
                    }

                }

                $res1 = admin_log("{$data['user_type']}提现审核", "{$data['user_type']}提现审核");
                if (!$res1) {
                    throw new Exception('操作日志写入失败！');
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return $this->error($e->getMessage());
            }
            return $this->success('修改成功！');

        }
    }


    /**
     * @return mixed
     * 买手余额记录
     */
    public function UserDepositBalanceRecord()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['name']) && $date['name']) {
                $where_name['username'] = ['like', '%' . trim($date['name']) . '%'];
                $uid = db('users')->where($where_name)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['phone']) && $date['phone']) {
                $where_phone['mobile'] = ['like', '%' . trim($date['phone']) . '%'];
                $uid = db('users')->where($where_phone)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['logType']) && $date['logType'] != '') {
                $where['type'] = trim($date['logType']);
            }

            if (isset($date['account']) && $date['account'] != '') {
                $where['memo'] = ['like', '%' . trim($date['account']) . '%'];
            }
            $dable = UserDepositRecharge::where(['id' => ['gt', 0]]);
            $count = UserDepositRecharge::where($where)->count('id');
            $notice_list = UserDepositRecharge::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,type,yprice,memo,create_time')->order('id desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 买手银锭记录
     */
    public function UserRewardBalanceRecord()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['name']) && $date['name']) {
                $where_name['username'] = ['like', '%' . trim($date['name']) . '%'];
                $uid = db('users')->where($where_name)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['phone']) && $date['phone']) {
                $where_phone['mobile'] = ['like', '%' . trim($date['phone']) . '%'];
                $uid = db('users')->where($where_phone)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['logType']) && $date['logType'] != '') {
                $where['type'] = trim($date['logType']);
            }
            if (isset($date['account']) && $date['account'] != '') {
                $where['memo'] = ['like', '%' . trim($date['account']) . '%'];
            }
            $dable = UserRewardRecharge::where(['id' => ['gt', 0]]);
            $count = UserRewardRecharge::where($where)->count('id');
            $notice_list = UserRewardRecharge::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,type,yprice,memo,create_time')->order('id desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }


    /**
     * @return mixed
     * 商家余额记录
     */
    public function SellerDepositBalanceRecord()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['name']) && $date['name']) {
                $where_name['seller_name'] = ['like', '%' . trim($date['name']) . '%'];
                $uid = db('seller')->where($where_name)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['phone']) && $date['phone']) {
                $where_phone['mobile'] = ['like', '%' . trim($date['phone']) . '%'];
                $uid = db('seller')->where($where_phone)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['logType']) && $date['logType'] != '') {
                $where['type'] = trim($date['logType']);
            }
            if (isset($date['account']) && $date['account'] != '') {
                $where['memo'] = ['like', '%' . trim($date['account']) . '%'];
            }
            $dable = SellerDepositRecharge::where(['id' => ['gt', 0]]);
            $count = SellerDepositRecharge::where($where)->count('id');
            $notice_list = SellerDepositRecharge::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,type,yprice,memo,create_time')->order('id desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 商家银锭记录
     */
    public function SellerRewardBalanceRecord()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['name']) && $date['name']) {
                $where_name['seller_name'] = ['like', '%' . trim($date['name']) . '%'];
                $uid = db('seller')->where($where_name)->column('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['phone']) && $date['phone']) {
                $where_name['mobile'] = ['like', '%' . trim($date['phone']) . '%'];
                $uid = db('seller')->where($where_name)->value('id');
                $where['uid'] = ['in',$uid];
            }
            if (isset($date['logType']) && $date['logType'] != '') {
                $where['type'] = trim($date['logType']);
            }
            if (isset($date['account']) && $date['account'] != '') {
                $where['memo'] = ['like', '%' . trim($date['account']) . '%'];
            }
            $dable = SellerRewardRecharge::where(['id' => ['gt', 0]]);
            $count = SellerRewardRecharge::where($where)->count('id');
            $notice_list = SellerRewardRecharge::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,type,yprice,memo,create_time')->order('id desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 支付宝充值
     */
    public function Recharge()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            //手机号筛选
            if (isset($date['phone']) && $date['phone']) {
                $where_phone['mobile'] =['like','%'.trim($date['phone']).'%'];
                    $uid = db('seller')->where($where_phone)->column('id');
                    if(!$uid){
                        $uid = db('users')->where($where_phone)->column('id');
                    }
                $where['uid'] = ['in',$uid];
            }

            if (isset($date['username']) && $date['username']) {
                $where_seller_name['seller_name'] =['like','%'.trim($date['username']).'%'];
                $where_username['username'] =['like','%'.trim($date['username']).'%'];
                $uid = db('seller')->where($where_seller_name)->column('id');
                if(!$uid){
                    $uid = db('users')->where($where_username)->column('id');
                }
                $where['uid'] = ['in',$uid];
            }

            if (isset($date['qq']) && $date['qq']) {
                $where_qq['qq'] =['like','%'.trim($date['qq']).'%'];
                $uid = db('seller')->where($where_qq)->column('id');
                if(!$uid){
                    $uid = db('users')->where($where_qq)->column('id');
                }
                $where['uid'] = ['in',$uid];
            }
            //添加时间筛选
            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }
            //身份筛选
            if (isset($date['userType']) && $date['userType']) {
                $where['user_type'] = trim($date['userType']);
            }
            //身份筛选
            if (isset($date['number']) && $date['number']) {
                $where['number'] = trim($date['number']);
            }

            //身份筛选
            if (isset($date['pay_type']) && $date['pay_type']!='') {
                $where['state'] = trim($date['pay_type']);
            }

            //货币类型筛选
            if (isset($date['currencyType']) && $date['currencyType']) {
                $where['recharge_type'] = trim($date['currencyType']);
            }
            $count = Recharge::where($where)->count('id');
            $recharge_list = Recharge::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,number,user_type,recharge_type,type,price,state,create_time')->order('id desc')->select();
            if ($recharge_list) $notice_list = $recharge_list->toArray();
            foreach ($recharge_list as $K => $v) {
                if ($v['user_type'] == "商家") {
                    $username = db('seller')->where('id', $v['uid'])->field('qq,seller_name,mobile')->find();
                    $recharge_list[$K]['name'] = $username['seller_name'];
                    $recharge_list[$K]['qq'] = $username['qq'];
                    $recharge_list[$K]['name'] = $username['seller_name'];
                    $recharge_list[$K]['mobile'] = $username['mobile'];
                } elseif ($v['user_type'] == "买手") {
                    $username = db('users')->where('id', $v['uid'])->field('qq,username,mobile')->find();
                    $recharge_list[$K]['name'] = $username['username'];
                    $recharge_list[$K]['qq'] = $username['qq'];
                    $recharge_list[$K]['mobile'] = $username['mobile'];
                }

            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $recharge_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 会员记录
     */
    public function MembershipRecords()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }

            if (isset($date['name']) && $date['name']) {
                $where['user_name'] =['like','%'.trim($date['name']).'%'];

            }
            if (isset($date['userType']) && $date['userType']!='') {
                $where['utype'] = trim($date['userType']);
            }
            $dable = VipRecord::where(['id' => ['gt', 0]]);
            $count = VipRecord::where($where)->count('id');
            $notice_list = VipRecord::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,price,utype,create_time,remarks')->order('create_time desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            foreach ($notice_list as $K => $v) {
                if ($v['utype'] == "商家") {
                    $username = db('seller')->where('id', $v['uid'])->value('seller_name');
                    $notice_list[$K]['name'] = $username;
                } elseif ($v['utype'] == "买手") {
                    $username = db('users')->where('id', $v['uid'])->value('username');
                    $notice_list[$K]['name'] = $username;
                }

            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 商家银行卡审核
     */
    public function SellerBankCardAudit()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            //卡号查询
            if (isset($date['bank_number']) && $date['bank_number']) {
               // $where['bank_number'] = $date['bank_number'];
                $where['bank_number'] =['like','%'.trim($date['bank_number']).'%'];
            }

            //开户人查询
            if (isset($date['bank_seller']) && $date['bank_seller']) {
               // $where['bank_seller'] = $date['bank_seller'];
                $where['bank_seller'] =['like','%'.trim($date['bank_seller']).'%'];
            }

            //姓名查询
            if (isset($date['name']) && $date['name']) {
                $where_name['seller_name'] =['like','%'.trim($date['name']).'%'];
                $where_uid = db('seller')->where($where_name)->column('id');
                $where['uid'] =['in',$where_uid];
            }
            //qq查询
            if (isset($date['qq']) && $date['qq']) {
                $where['uid'] = db('seller')->where('qq',$date['qq'])->value('id');
            }

            //号码查询
            if (isset($date['phone']) && $date['phone']) {
                $where['mobile'] =['like','%'.trim($date['phone']).'%'];
               // $where['mobile'] = $date['phone'];
            }
            //状态查询
            if (isset($date['state']) && $date['state'] != '') {
                $where['state'] = $date['state'];
            }

            //时间查询
            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }
            $count = SellerBank::where($where)->count('id');
            $notice_list = SellerBank::where($where)->limit(($page - 1) * $limit, $limit)->field('id,uid,bank_seller,bank_id,branch_name,city,bank_number,idcard,mobile,idcard_img,state,create_time')->order('id desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }


    /**
     * @return mixed
     * 买手银行卡审核
     */
    public function UserBankCardAudit()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            //卡号查询
            if (isset($date['bank_number']) && $date['bank_number']) {
                //$where['bank_no'] = $date['bank_number'];
                $where['bank_no'] =['like','%'.trim($date['bank_number']).'%'];
            }

            //开户人查询
            if (isset($date['bank_seller']) && $date['bank_seller']) {
                //$where['bank_user'] = $date['bank_seller'];
                $where['bank_user'] =['like','%'.trim($date['bank_seller']).'%'];
            }
            //姓名查询
            if (isset($date['name']) && $date['name']) {
                $where_name['username'] =['like','%'.trim($date['name']).'%'];
                $where_uid = db('users')->where($where_name)->column('id');
                $where['user_id'] =['in',$where_uid];
            }
            //qq查询
            if (isset($date['qq']) && $date['qq']) {
                $where['user_id'] = db('users')->where('qq',$date['qq'])->value('id');
            }
            //电话查询
            if (isset($date['phone']) && $date['phone']) {
                $where['mobile'] =['like','%'.trim($date['phone']).'%'];
                //$where['mobile'] = $date['phone'];
            }
            //状态查询
            if (isset($date['state']) && $date['state'] != '') {
                $where['state'] = $date['state'];
            }

            //时间查询
            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }
            $count = UserBank::where($where)->count('id');
            $notice_list = UserBank::where($where)->limit(($page - 1) * $limit, $limit)->field('id,user_id,bank_user,bank_id,branch_name,city,bank_no,idcard,mobile,idcard_img_a,idcard_img_b,state,create_time')->order('id desc')->select();
            if ($notice_list) $notice_list = $notice_list->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
            return $this->notice_date(1, $date);
        }
        return view();
    }

    /**
     * @return mixed
     * 提现查看
     */
    public function addCommissionRate()
    {
        $data = input();
        if (!$data['id'] || !$data['user_type']) {
            $this->error('参数错误！');
        }
        $data['user_type'] = urldecode($data['user_type']);
        if ($data['user_type'] == "买手") {
            $list = db('user_cash')->where('id', $data['id'])->find();
        } elseif ($data['user_type'] == "商家") {
            $list = db('seller_cash')->where('id', $data['id'])->find();
        }
        if ($list['type'] == 1) {
            $list['leixing'] = "本金";
        } else {
            $list['leixing'] = "银锭";
        }
        $list['user_type'] = $data['user_type'];
        $this->assign('list', $list);
        return view();
    }


    /**
     * @return mixed
     * 银行卡审核
     */
    public function examine()
    {
        $date = input();
        if (!$date['id'] || !$date['type']) {
            $this->error('参数错误！');
        }
        if ($date['type'] == 1) {
            $data_info = db('seller_bank')->where('id', $date['id'])->find();
        }
        if ($date['type'] == 2) {
            $data_info = db('user_bank')->where('id', $date['id'])->find();
        }
        $data_info['user_type'] = $date['type'];
        $this->assign('data_info', $data_info);
        return view();
    }

    /**
     * @return mixed
     * 银行卡删除
     */
    public function bank_del()
    {
        $date = input();
        if (!$date['id'] || !$date['type']) {
            return $this->error('参数错误！');
        }
        if ($date['type'] == 1) {
            $data_info = db('seller_bank')->where('id', $date['id'])->find();

            $bank_number = $data_info['bank_number'];
        }
        if ($date['type'] == 2) {
            $data_info = db('user_bank')->where('id', $date['id'])->find();
            $bank_number = $data_info['bank_no'];
        }
        if (!$data_info) {
            return $this->error('记录不存在！');
        }
        if ($date['type'] == 1) {
            $res = db('seller_bank')->where('id', $date['id'])->delete();
        } elseif ($date['type'] == 2) {
            $res = db('user_bank')->where('id', $date['id'])->delete();
        }
        if ($date['type'] == 1) {
            $user_type = "商家";
        } elseif ($date['type'] == 2) {
            $user_type = "买手";
        }
        if ($res) {
            $res1 = admin_log("{$user_type}银行卡删除", "管理员{$this->admin_info['user_name']}操作:删除卡号{$bank_number}");
            if (!$res1) {
                return $this->error('操作日志写入失败！');
            }
            return $this->success('删除成功！');
        } else {
            return $this->error('删除失败！');
        }
    }

    /**
     * @return mixed
     * 银行卡审核
     */
    public function to_examine()
    {
        if (request()->isPost()) {
            $date = input();
            if (!$date['id'] || $date['state'] == '' || !$date['user_type']) {
                return $this->error('参数错误！');
            }
            $update_data['state'] = $date['state'];
            $update_data['remarks'] = $date['remarks'];
            $update_data['update_time'] = time();
            if ($date['user_type'] == 1) {
                $data_info = db('seller_bank')->where('id', $date['id'])->find();
                $bank_number = $data_info['bank_number'];

            }
            if ($date['user_type'] == 2) {
                $data_info = db('user_bank')->where('id', $date['id'])->find();

                $bank_number = $data_info['bank_no'];
            }
            if ($data_info['state'] == 1 && $date['state'] == 0) {
                return $this->error('通过后不能改为待审核！');
            }
            if ($data_info['state'] == 2 && $date['state'] == 1) {
                return $this->error('拒绝后不能改成通过！');
            }
            if ($date['user_type'] == 1) {
                $res = db('seller_bank')->where('id', $date['id'])->update($update_data);
            }
            if ($date['user_type'] == 2) {
                $res = db('user_bank')->where('id', $date['id'])->update($update_data);
            }
            if ($date['user_type'] == 1) {
                $user_type = "商家";
            } elseif ($date['user_type'] == 2) {
                $user_type = "买手";
            }
            if ($res) {
                $res1 = admin_log("{$user_type}银行卡审核", "管理员{$this->admin_info['user_name']}操作:审核卡号{$bank_number}");
                if (!$res1) {
                    return $this->error('操作日志写入失败！');
                }
                return $this->success('修改成功！');
            } else {
                return $this->error('修改失败！');
            }
        }
    }

    /**
     * @notes 单条审核通过提交方法
     * @date 2019/9/29
     * @time 11:33
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check(Request $request)
    {
        $data = $request->param();
        if (!isset($data['id']) || !$data['id']) return $this->error('参数有误！');
        if (!isset($data['memo'])) return $this->error('参数有误！');
        if (!isset($data['type']) || !$data['type']) return $this->error('参数有误！');
        if ($data['type'] == 1) {
            $table = "user_cash";
        } else {
            $table = "seller_cash";
        }
        $list = Db::name($table)->where(['id' => $data['id'], 'state' => 0])->find();
        if (!$list) return $this->error('未找到数据或状态不正确!');
        $update['state'] = 1;
        $update['memo'] = $data['memo'];
        $update['examine_time'] = time();
        try {
            Db::startTrans();
            Db::name($table)->where(['id' => $data['id']])->update($update);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        return $this->success('审核成功！');
    }

    /**
     * @notes 批量审核通过提交方法
     * @date 2019/9/29
     * @time 11:33
     * @param Request $request
     */
    public function allCheck(Request $request)
    {
        $data = $request->param();
        $data = $data['data'];
        if (!is_array($data)) return $this->error('参数有误！');
        if (!$data) return $this->error('请选择要审核的记录');
        try {
            Db::startTrans();
            $num = 0;
            $nums = 0;
            foreach ($data as $datum) {
                $type = 0;
                if ($datum['uid']['type'] == '买手') $type = 1;
                if ($datum['uid']['type'] == '商家') $type = 2;
                if ($type) {
                    if ($this->checkDo($datum['id'], $type)) {
                        $num++;
                    } else {
                        $nums++;
                    }
                } else {
                    $nums++;
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success("{$num}条审核通过成功！{$nums}条执行失败");
    }

    /**
     * @notes 审核通过执行方法
     * @date 2019/9/29
     * @time 11:32
     * @param $id 记录id
     * @param $type 用户类型
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function checkDo($id, $type)
    {
        if ($type == 1) {
            $table = "user_cash";
        } else {
            $table = "seller_cash";
        }
        $list = Db::name($table)->where(['id' => $id, 'state' => 0])->find();
        if ($list) {
            $update['state'] = 1;
            $update['examine_time'] = time();
            if (Db::name($table)->where(['id' => $id])->update($update)) return true;
        }
        return false;
    }

    /**
     * @notes 单条确认打款提交方法
     * @date 2019/9/29
     * @time 11:32
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmPayment(Request $request)
    {
        $data = $request->param();
        if (!isset($data['id']) || !$data['id']) return $this->error('参数有误！');
        if (!isset($data['memo'])) return $this->error('参数有误！');
        if (!isset($data['type']) || !$data['type']) return $this->error('参数有误！');
        if ($data['type'] == 1) {
            $table = "user_cash";
        } else {
            $table = "seller_cash";
        }
        $list = Db::name($table)->where(['id' => $data['id'], 'state' => 1])->find();
        if (!$list) return $this->error('未找到数据或状态不正确!');
        $update['state'] = 3;
        $update['memo'] = $data['memo'];
        $update['examine_time'] = time();
        try {
            Db::startTrans();
            Db::name($table)->where(['id' => $data['id']])->update($update);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        return $this->success('确认成功！');
    }

    /**
     * @notes 批量确认打款提交方法
     * @date 2019/9/29
     * @time 11:31
     * @param Request $request
     */
    public function confirmPaymentAll(Request $request)
    {
        $data = $request->param();
        $data = $data['data'];
        if (!is_array($data)) return $this->error('参数有误！');
        if (!$data) return $this->error('请选择要确认的记录');
        try {
            Db::startTrans();
            $num = 0;
            $nums = 0;
            foreach ($data as $datum) {
                $type = 0;
                if ($datum['uid']['type'] == '买手') $type = 1;
                if ($datum['uid']['type'] == '商家') $type = 2;
                if ($type) {
                    if ($this->confirm($datum['id'], $type)) {
                        $num++;
                    } else {
                        $nums++;
                    }
                } else {
                    $nums++;
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success("{$num}条确认已返款成功！{$nums}条执行失败");
    }

    /**
     * @notes  确认打款执行方法
     * @date 2019/9/29
     * @time 11:30
     * @param $id 记录id
     * @param $type 用户类型
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function confirm($id, $type)
    {
        if ($type == 1) {
            $table = "user_cash";
        } else {
            $table = "seller_cash";
        }
        $list = Db::name($table)->where(['id' => $id, 'state' => 1])->find();
        if ($list) {
            $update['state'] = 3;
            $update['examine_time'] = time();
            if (Db::name($table)->where(['id' => $id])->update($update)) return true;
        }
        return false;
    }


    /**
     * @return mixed
     * 提现审核列表导出
     */
    public function cash_export()
    {
        $date = input();
        $where = [];
//            if (isset($date['state']) && $date['state']!='' && $date['state']!=88) {
//            $where['state'] =$date['state'];
//            }
        $where['state'] = 1;
        //添加时间筛选
        if (isset($date['time']) && $date['time']) {
            $date['time']=urldecode($date['time']);
            $time = explode(" - ", $date['time']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['create_time'] = ['between', [$time1, $time2]];
        }
        if (isset($date['type']) && $date['type'] == 2) {
            if (isset($date['name']) && $date['name']) {
                $uid = db('seller')->where('seller_name', $date['name'])->value('id');
                $where['uid'] = $uid;
            }
            if (isset($date['phone']) && $date['phone']) {
                $where['mobile'] = $date['phone'];
            }
            $usertype = "商家";
            $notice_list = cashseller::where($where)->field('id,uid,user_type,type,bank_seller,bank_name,bank_number,price,poundage,toaccount,state')->order('id desc')->select();
        } else {

            if (isset($date['name']) && $date['name']) {
                $uid = db('users')->where('username', $date['name'])->value('id');
                $where['uid'] = $uid;
            }
            if (isset($date['phone']) && $date['phone']) {
                $where['mobile'] = $date['phone'];
            }
            $usertype = "买手";

            $notice_list = cashuser::where($where)->field('id,uid,user_type,type,bank_seller,bank_name,bank_number,price,poundage,toaccount,state')->order('id desc')->select();
        }
        if ($notice_list) $notice_list = $notice_list->toArray();
        $title = ['PTID', '用户名', '类型', '提现类型', '姓名', '银行名', '银行账号', '申请金额', '手续费', '到账金额', '状态'];
        Phpexcel::exportExcel($title, $notice_list, "{$usertype}提现导出表");
    }

}
