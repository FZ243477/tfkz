<?php
namespace app\mobile\controller;
use app\buy\model\SellerTask;
use think\Controller;
use think\Db;
use app\common\model\Sms; //引入手机验证码
use app\common\controller\Img;
use think\Exception;
use think\Request;
use app\common\model\Api;
use app\common\controller\aliyunOss;

class Task extends Base
{
    public function index(Request $request){
        if(request()->isAjax()) {
            $data = $request->param();
            $now=time();
            $where['state']=1;
            $where['status']=3;
            $where['incomplete_num'] = array('neq',0);
            if(!empty($data['task_type'])){
                if ($data['task_type'] != 6) {
                    $where['task_type'] = $data['task_type'];
                }else if($data['task_type'] == 6){
                    $where['is_ys'] =1;
                }}
            if (!empty($data['datetime1']) || !empty($data['datetime2'])) {
                $datetime1=$data['datetime1']/1000;
                $datetime2=$data['datetime2']/1000;
                $datetime1=date('Y-m-d',$datetime1);
                $datetime2=date('Y-m-d',$datetime2);
                $where['create_time'] = ['between',[strtotime($datetime1),strtotime($datetime2)]];
            }
            if($data['terminal']){
                $where['terminal']=$data['terminal'];
            }
            if($data['getprice']){
                $where['goods_price'] =$data['getprice'];
                //dump($data['getprice']);exit;
                if($data['getprice'] ==1){
                    $where['goods_price'] = ['between', [0, 200]];
                }
                if($data['getprice'] ==2){
                    $where['goods_price'] = ['between', [200, 500]];
                }
                if($data['getprice'] ==3){
                    $where['goods_price'] = ['between', [500, 1000]];
                }
                if($data['getprice'] ==4){
                    $where['goods_price'] = ['between', [1000, 2000]];
                }
                if($data['getprice'] ==5){
                    $where['goods_price'] = array('gt',2000);
                }
            }
            if($data['buyno_id']){
                $userbuyno = Db::name('user_buyno')->where('id', $data['buyno_id'])->find();
                $list = SellerTask::where($where)
                    ->where('publish_time', '<', $now)
                    //->limit(($data['page'] - 1) * $data['size'], $data['size'])
                    ->order('id desc')
                    ->select();
                foreach ($list as $k => &$v) {
                    if ($v['is_repay'] == 1) {
                        $user_task = Db::name('user_task')->where('user_buyno_id', $userbuyno['id'])->where('shop_id', $v['shop_id'])->where('state', 1)->count();
                        if ($user_task == 0) {
                            $whare[$k] = $v['id'];
                        }
                    }
                }
                if (!empty($whare)) {
                    $whare = implode(',', $whare);
                    $where['id'] = array('neq', $whare);
                }
                if ($userbuyno['star'] == 1) {
                    $star_price = 100;
                    $panduanstar = 1;
                }
                if ($userbuyno['star'] == 2) {
                    $star_price = 500;
                    $panduanstar = 1;
                }
                if ($userbuyno['star'] == 3) {
                    $star_price = 1000;
                    $panduanstar = 1;
                }
                if ($userbuyno['star'] == 4) {
                    $star_price = 2000;
                    $panduanstar = 1;
                }
                if ($userbuyno['star'] == 5) {
                    $panduanstar = 2;
                }
                if ($userbuyno['star'] == 0) {
                    $panduanstar = 3;
                }
                if ($panduanstar == 1) {
                    $total = SellerTask::where($where)
                        ->where('publish_time', '<', $now)//查找发布时间<现在的时间
                        ->where('goods_price', '<', $star_price)
                        ->count('id');
                    $list = SellerTask::where($where)
                        ->where('publish_time', '<', $now)
                        ->where('goods_price', '<', $star_price)
                        ->limit(($data['page'] - 1) * 10, 10)
                        ->order('id desc')
                        ->select();
                }
                if ($panduanstar == 2) {
                    $total = SellerTask::where($where)
                        ->where('publish_time', '<', $now)//查找发布时间<现在的时间
                        ->count('id');
                    $list = SellerTask::where($where)
                        ->where('publish_time', '<', $now)
                        ->limit(($data['page'] - 1) * 10, 10)
                        ->order('id desc')
                        ->select();
                }
                if ($panduanstar == 3) {
                    $total = SellerTask::where($where)
                        ->where('publish_time', '<', $now)//查找发布时间<现在的时间
                        ->count('id');
                    $list = SellerTask::where($where)
                        ->where('publish_time', '<', $now)
                        ->limit(($data['page'] - 1) * 10, 10)
                        ->order('id desc')
                        ->select();
                }
            }else{
                $list = SellerTask::where($where)
                    ->where('publish_time', '<', $now)
                    //->limit(($data['page'] - 1) * $data['size'], $data['size'])
                    ->order('id desc')
                    ->select();
                $users = Db::name('users')->where('id', $this->id)->find();
                foreach ($list as $k => &$v) {
                    if ($v['is_repay'] == 1) {
                        $user_task = Db::name('user_task')->where('user_id', $users['id'])->where('shop_id', $v['shop_id'])->where('state', 1)->count();
                        if ($user_task == 0) {
                            $whare[$k] = $v['id'];
                        }
                    }
                }
                if (!empty($whare)) {
                    $whare = implode(',', $whare);
                    $where['id'] = array('neq', $whare);
                }
                $total = SellerTask::where($where)
                    ->where('publish_time', '<', $now)//查找发布时间<现在的时间
                    ->count('id');
                $list = SellerTask::where($where)
                    ->where('publish_time', '<', $now)
                    ->limit(($data['page'] - 1) * 10, 10)
                    ->order('id desc')
                    ->select();
            }
            foreach($list as $k =>&$v){
                $v['total_price']=$v['goods_price'];
                $commission=Db::name('commission')
                    ->where('max_goods_price','>=',$v['total_price'])
                    ->order('max_goods_price ASC')
                    ->find();
                $v['user_reward']=$commission['user_reward'];
                $v['incomplete_num']=$v['num']-$v['incomplete_num'];
                $v['progress']=$v['incomplete_num']/$v['num']*100;
                if($v['progress']<0){
                    $v['progress'] = 0;
                }
                $v['user_divided ']=$v['user_divided']/$v['num'];
                $shop_name=Db::name('seller')
                    ->where('id',$v['seller_id'])
                    ->find();
                $yinc_mobile = substr_replace($shop_name['mobile'],'****',3,5);
                $v['mobile']=$yinc_mobile;
                $v['seller_name']=$shop_name['seller_name'];
            }
            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }
        $now=time();
        $buyno=Db::name('user_buyno')
            ->where(['uid'=>$this->id])
            ->where(['state'=>1])
            ->where('frozen_time','<',$now)
            ->select()->toArray();
        foreach ($buyno as $k=>$v){
            $where['user_buyno_id'] = $v['id'];
            $map['state'] = ['neq',2];
            $maps['create_time'] = ['gt',strtotime(date('Y-m-d'))];
            $count =Db::name('user_task')->where($where)->where($map)->where($maps)->count();
            $count=4-$count;
            $buyno[$k]['count'] =$count;
        }
        $this->assign('buyno',$buyno);
        $topnav=5;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }

    /**
     * @notes 买手接任务
     * @date 2019/10/17
     * @time 14:11
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_task(Request $request)
    {
        $data = $request->param();
        if (!$data['buyno_id']) return $this->error('买号不能为空，请选择买号');
        if (!$data['terminal']) return $this->error('请先选择终端');
        $now = time();
        $user = Db::name('users')->where('id', $this->id)->find();
        if ($user['vip_time'] < $now || $user['vip'] != 1) return $this->error('您还不是VIP,无法接单!');
        if ($user['reward'] < 1) return $this->error('银锭不足,请充值!');
        $this->redis->rpush($data['task_number'], $this->id);
        $this->redis->expire($data['task_number'], 5);
        $res = $this->paiDui($data['task_number'], time());
        if (!$res) return $this->error('任务领取失败！');
        $seller_task = Db::name('seller_task')->where(['task_number' => $data['task_number']])->find();
        $user_task_number = Db::name('user_task')->where('user_id', $user['id'])->where('state', 1)->count();

        if ($seller_task['incomplete_num'] == 0) {
            $this->redis->lrem($data['task_number'], $this->id, 0);
            return $this->error('此任务已全部被领取！');
        }
        if ($seller_task['status'] != 3) {
            $this->redis->lrem($data['task_number'], $this->id, 0);
            return $this->error('此任务无法领取！');
        }
        if ($seller_task['receipt_time'] + ($seller_task['union_interval_time'] * 60) > $now) return $this->error('未达到商家设定的接单间隔时间！');
        $buyno = Db::name('user_buyno')->where(['id' => $data['buyno_id']])->find();
        $res = $this->buyno($buyno['id'], $buyno['wwid'], $seller_task['seller_id'], $seller_task['shop_id'],$seller_task['id']);
        if ($res['code'] == 0) {
            $this->redis->lrem($data['task_number'], $this->id, 0);
            return $this->error($res['msg']);
        }
        $res = $this->user($this->id, $seller_task['seller_id']);
        if ($res['code'] == 0) {
            $this->redis->lrem($data['task_number'], $this->id, 0);
            return $this->error($res['msg']);
        }
        $res = $this->orders($seller_task['id']);
        if ($res['code'] == 0) {
            $this->redis->lrem($data['task_number'], $this->id, 0);
            return $this->error($res['msg']);
        }
        if ($buyno['star'] == 1 && $seller_task['goods_price'] > 100) {
            return $this->error('买号经验值还不够！');
        }
        if ($buyno['star'] == 2 && $seller_task['goods_price'] > 500) {
            return $this->error('买号经验值还不够！');
        }
        if ($buyno['star'] == 3 && $seller_task['goods_price'] > 1000) {
            return $this->error('买号经验值还不够！');
        }
        if ($buyno['star'] == 4 && $seller_task['goods_price'] > 2000) {
            return $this->error('买号经验值还不够！');
        }
        if($buyno['frozen_time'] ){
            if($now<$buyno['frozen_time']){
                return $this->error('买号已被冻结！');
            }
        }
        if($seller_task['is_repay'] ==1){
            $user_task_have_repay=Db::name('user_task')->where('shop_id',$seller_task['shop_id'])
                ->where('user_buyno_id',$buyno['id'])->where('state',1)->count();
            if($user_task_have_repay ==0){
                return $this->error('此任务是回购任务，无法接取！');
            }
        }
        $user_task_number = Db::name('user_task')->where('user_buyno_id', $buyno['id'])->where('state', 1)->count();
        if ($user_task_number < 30) {
            $star = 1;
        }
        if (30 <= $user_task_number && $user_task_number < 60) {
            $star = 2;
        }
        if (60 <= $user_task_number && $user_task_number < 90) {
            $star = 3;
        }
        if (90 <= $user_task_number && $user_task_number < 120) {
            $star = 4;
        }
        if ($user_task_number >= 120) {
            $star = 5;
        }
        if ($seller_task['is_timing_pay'] == 1) {  //定时付款任务
            $task_type = 3;
            $ending_time = $seller_task['timing_time'] + 120 * 60;
            //$ending_time=time()+30;
        } else if ($seller_task['next_day'] == 1) { //隔天任务
            $task_type = 2;
            $next_day_four_pm = strtotime(date('Y-m-d', time())) + 40 * 60 * 60 + 40 * 60;//隔天下午4点40
            $ending_time = $next_day_four_pm;
        } else {                                  //普通任务
            if($seller_task['is_ys']==1){
                $task_type = 1;
            }else{
                $task_type = 1;
            }
            $ending_time = $now + 120 * 60;
        }
        $selltask_change = [
            'incomplete_num' => $seller_task['incomplete_num'] - 1,
            'receipt_time' => time(),
        ];

        $reward_change = [
            'reward' => $user['reward'] - 1,
            'last_time' => time()
        ];
        $commission = $data['commission'];//佣金
        $user_divided = $seller_task['user_divided'] / $seller_task['num'];//每单的加赏佣金
        $user_divided = round($user_divided, 2);
        $task = [
            'user_id' => $this->id,
            'seller_id' => $seller_task['seller_id'],
            'seller_task_id' => $seller_task['id'],
            'task_number' => $seller_task['task_number'] . '-' . time() . rand(100, 999),
            'goods_id' => $seller_task['goods_id'],
            'shop_id' => $seller_task['shop_id'],
            'goods_unit_price' => $seller_task['goods_unit_price'],
            'goods_num' => $seller_task['goods_num'],
            'user_buyno_id' => $buyno['id'],
            'user_buyno_wangwang' => $buyno['wwid'],
            'principal' => $data['total_price'],
            'commission' => $commission,
            'terminal' => $seller_task['terminal'],
            'state' => 0,
            'create_time' => time(),
            'update_time' => time(),
            'address' => $buyno['addresspro'] . ',' . $buyno['addresscity'] . ',' . $buyno['addressarea'] . ',' . $buyno['addresstext'],
            'task_type' => $task_type,
            'ending_time' => $ending_time,
            'task_step' => 1,
            'shipping_address' => $seller_task['address'],
            'shop_name' => $seller_task['shop_name'],
            'user_divided' => $user_divided,
            'addressname' => $buyno['addressname'],
            'addressphone' => $buyno['addressphone'],
            'delivery_status' => $seller_task['is_free_shiping'] == 3 ? 1 : 0,
            'is_shengji' => $seller_task['is_shengji'],
            'is_ys'=> $seller_task['is_ys'],
            'ys_time'=> $seller_task['ys_time'],
            'yf_price'=> $seller_task['yf_price'],
            'wk_price'=> $seller_task['wk_price'],
            'ys_fee'=> $seller_task['ys_fee'],
            'code' => $seller_task['code'],
        ];
        Db::startTrans();
        try {
            if ($seller_task['is_shengji'] == 1) {
                $goods_id = json_decode($seller_task['goods_id'])[0];
                $res = $this->getKey($goods_id);
                $goods_ids = json_decode($seller_task['goods_id']);
            }
            if ($seller_task['is_shengji'] == 2) {
                $task_goods = Db::name('task_goods')->where('task_id', $seller_task['id'])->select();
                $ids = [];
                $goods_ids = [];
                foreach ($task_goods as $key => $goods) {
                    $res_list = Db::name('task_word')->where(['task_id' => $seller_task['id'], 'goods_id' => $goods['goods_id'], 'ynum' => ['gt', 0]])->find();
                    if ($key == 0) $res['key_word'] = '关键词：' . $res_list['key_word'];
                    $ids[] = $res_list['id'];
                    $goods_ids[] = $goods['goods_id'];
                    Db::name('task_word')->where(['id'=>$res_list['id']])->setDec('ynum',1);
                }
                $res['key_id'] = implode(',', $ids);
            }
            $task['key_id'] = $res['key_id'];
            $task['key'] = $res['key_word'];
            $ids = [];
            if ($seller_task['is_praise']) {
                $list1 = $this->praise($seller_task['id'], $goods_ids);
                $task['text_praise'] = json_encode($list1['praise']);
                $ids = $list1['id'];
            }
            if ($seller_task['is_img_praise']) {
                $list2 = $this->praiseImg($seller_task['id'], $goods_ids, $seller_task['is_shengji']);
                $task['img_praise'] = $list2['content'];
                if ($seller_task['is_shengji'] == 1) $ids[] = $list2['id'];
                if ($seller_task['is_shengji'] == 2) {
                    foreach ($list2['id'] as $id) {
                        $ids[] = $id;
                    }
                }
            }
            if ($seller_task['is_video_praise']) {
                $list3 = $this->praiseVideo($seller_task['id']);
                $task['video_praise'] = $list3['content'];
                $ids[] = $list3['id'];
            }
            $task['ids'] = json_encode($ids);
            if($buyno['star']<$star){
                $reward_star = [
                    'star' => $star,
                ];
                $rea = Db::name('user_buyno')->where('id', $buyno['id'])->update($reward_star);
            }
            $res = Db::name('users')->where('id', $this->id)->update($reward_change);
            if ($res) {
                finance($this->id, 2, -1, 2, 4, "买手接任务{$task['task_number']},冻结1银锭");
            }
            Db::name('seller_task')->where('id', $seller_task['id'])->update($selltask_change);
            $task_now_id = Db::name('user_task')->insertGetId($task);
            $return_error = Api::api($task_now_id, $task['task_number'], 1, '', $task['ending_time'], '');
            if ($task_type != 1) {
                $end_else_time = time() + 7200;
                $return_error = Api::api($task_now_id, $task['task_number'], 1, '', $end_else_time, '');

            }
            if ($task_type == 3) {
                $memo = "您有编号为{$task['task_number']}的定时任务等待您继续完成！";
                $time_time = $seller_task['timing_time'];
                Api::api($task_now_id, $task['task_number'], 0, $user['mobile'], $time_time, $memo);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->redis->lrem($data['task_number'], $this->id, 0);
            return $this->error($e->getMessage());
        }
        $this->redis->lrem($data['task_number'], $this->id, 0);
        return $this->success('任务领取成功', 'Task/maketask');

    }

    /**
     * @notes 接任务排队
     * @date 2019/12/6
     * @time 17:36
     * @param $num
     * @param $time
     * @return bool
     */
    public function paiDui($num,$time){
        if(!$this->redis->lrange($num,0,0)){
            return true;
        }
        $id = $this->redis->lrange($num,0,0)[0];
        if($id == $this->id){
            return true;
        }
        $ids = $this->redis->lrange($num,0,-1);
        if(!in_array($this->id,$ids)){
            $this->redis->lrem($num,$this->id,0);
            return false;
        }
        if(time()-$time > 5){
            $this->redis->lrem($num,$this->id,0);
            return false;
        }
        return $this->paiDui($num,$time);
    }

    public function orders($sell_task_id){
        $task=Db::name('seller_task')
            ->where('id',$sell_task_id)
            ->find();
        $half_timing_time=$task['timing_time']-30*60;
        $now=time();
        if($task && $task['is_timing_pay'] ==1 && $now>$half_timing_time){
            return ['code'=>0,'msg'=>"此任务超时，请领取有效任务"];
        }else{
            return ['code'=>1];
        }
    }

    /**
     * @notes 判断买号
     * @date 2019/10/16
     * @time 18:28
     * @param $id 买号id
     * @param $wwid 买号旺旺号
     * @param $seller_id 商家id
     * @param $shop_id 店铺id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function buyno($id,$wwid,$seller_id,$shop_id,$seller_task_id){
        $where['user_buyno_id'] = $id;
        $map['state'] = ['neq',2];
        $maps['create_time'] = ['gt',strtotime(date('Y-m-d'))];
        $count = Db::name('user_task')->where($where)->where($map)->where($maps)->count();
        if($count >= 4)return ['code'=>0,'msg'=>'买号今天已接4单，达到上限！'];
        $where['shop_id'] = $shop_id;
        $list = Db::name('user_task')->where($where)->where($map)->order('id desc')->find();
        if($list){
            $times = Db::name('seller_task')->where(['id'=>$seller_task_id])->value('cycle_time');
            $times = $times ? $times : 0;
            $month=$times+30;
            if($list['create_time']+($month*24*3600) > time())return ['code'=>0,'msg'=>"该商家设置买家购物周期为{$month}天"];
        }
        $list_limit = Db::name('seller_limit')->where(['wangwang'=>$wwid,'seller_id'=>$seller_id,'status'=>1])->find();
        if($list_limit){
            if($list_limit['state']==0 || ($list_limit['state']==1 && $list_limit['end_time'] > time()))return ['code'=>0,'msg'=>'当前买号已被商家拉黑！'];
        }
        return ['code'=>1];
    }

    /**
     * @notes 判断用户
     * @date 2019/10/16
     * @time 18:29
     * @param $user_id 用户id
     * @param $seller_id 商家id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user($user_id,$seller_id){
        $user = Db::name('users')->where(['id'=>$user_id])->find();
        //if($user['star']==0)return ['code'=>0,'msg'=>'您的买号质量下降严重，无法领取任务！'];
        $map['user_id'] = $user_id;
        $map['state'] = ['neq',2];
        $map['create_time'] = ['gt',strtotime(date('Y-m-01'))];
        $count = Db::name('user_task')->where($map)->count();
        if($count >= 220)return ['code'=>0,'msg'=>'本月已接单220单，达到上限！'];
        $where['user_id'] = $user_id;
        $where['state'] = ['neq',2];
        $user_task = Db::name('user_task')->where($where)->order('id desc')->find();
        if($user_task){
            if($user_task['task_type'] == 1){
                if($user_task['is_ys']==1 && $user_task['task_step'] !=4){
                    var_dump($user_task['id']);exit;
                    return ['code' => 0, 'msg' => '有未完成任务，请完成后再接！'];
                }else if($user_task['state'] == 0 && $user_task['is_ys']==0){
                    return ['code' => 0, 'msg' => '有未完成任务，请完成后再接！'];
                }
            }else{
                if($user_task['task_step'] == 1) return ['code'=>0,'msg'=>'有未完成任务，请完成后再接！'];
            }
        }
        $where['create_time'] = ['gt',strtotime(date('Y-m-d'))];
        $where['seller_id'] = $seller_id;
        $list = Db::name('user_task')->where($where)->find();
        if($list)return ['code'=>0,'msg'=>'今天已接该商家任务，请明天继续！'];
        return ['code'=>1];
    }


    /**
     * @notes 获取关键词
     * @date 2019/10/14
     * @time 19:27
     * @param $goods_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getKey($goods_id){
        $goods_key_id = Db::name('goods')->where(['id'=>$goods_id])->value('goods_key_id');
        $keys = Db::name('goods_key_world')->where(['goods_key_id'=>$goods_key_id])->select();
        $keys = $keys ? $keys->toArray() : [];
        if(count($keys) > 2){
            $nums = array_rand($keys,2);
            $keyss = [];
            foreach ($nums as $val){
                $keyss[] = $keys[$val];
            }
            $keys = $keyss;
        }
        $key_word = '';
        foreach ($keys as $val){
            if($key_word==''){
                $key_word .= '关键词：'.$val['key_world'];
            }else{
                $key_word .= " 备选关键词：".$val['key_world'];
            }
        }
        $key_id = $keys[0]['id'];
        return [
            'key_id'=>$key_id,
            'key_word'=>$key_word,
        ];
    }

    /**
     * @notes 获取文字好评
     * @date 2019/10/14
     * @time 16:33
     * @param $task_id 任务id
     * @param $goods_id 商品id
     * @return false|string
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function praise($task_id, $goods_id)
    {
        $praise = [];
        $id = [];
        foreach ($goods_id as $item) {
            $where = ['seller_task_id' => $task_id, 'goods_id' => $item, 'state' => 0];
            $list = Db::name('seller_task_praise')->where($where)->field('id,content')->order('id asc')->find();
            $praise[] = $list['content'];
            $id[] = $list['id'];
            Db::name('seller_task_praise')->where(['id' => $list['id']])->update(['state' => 1]);
        }
        return ['praise' => $praise, 'id' => $id];
    }

    /**
     * @notes 获取图片和视频好评
     * @date 2019/10/14
     * @time 16:32
     * @param $task_id 任务id
     * @param $type 好评类型
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function praiseImg($task_id, $goods_id, $is_shengji)
    {
        if ($is_shengji == 1) {
            $where = ['seller_task_id' => $task_id, 'type' => 2, 'state' => 0];
            $list = Db::name('seller_task_praise')->where($where)->field('id,content')->order('id asc')->find();
            Db::name('seller_task_praise')->where(['id' => $list['id']])->update(['state' => 1]);
        } else if ($is_shengji == 2) {
            $where = ['seller_task_id' => $task_id, 'type' => 2, 'state' => 0];
            $list['id'] = [];
            $list['content'] = '';
            foreach ($goods_id as $item) {
                $where['goods_id'] = $item;
                $res = Db::name('seller_task_praise')->where($where)->field('id,content')->order('id asc')->find();
                Db::name('seller_task_praise')->where(['id' => $res['id']])->update(['state' => 1]);
                $list['id'][] = $res['id'];
            }
        }
        return $list;

    }

    public function praiseVideo($task_id)
    {
        $where = ['seller_task_id' => $task_id, 'type' => 3, 'state' => 0];
        $list = Db::name('seller_task_praise')->where($where)->field('id,content')->order('id asc')->find();
        Db::name('seller_task_praise')->where(['id' => $list['id']])->update(['state' => 1]);
        return $list;
    }
    //做任务列表
    public function maketask(){
        $have_task=Db::name('user_task')
            ->field('b.id,b.task_type,b.seller_id,b.state,b.terminal,a.user_divided,a.id,a.principal,a.commission,a.user_buyno_wangwang,a.seller_task_id,a.task_step,a.is_ys')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->where('a.state',0)
            ->where('b.state',1)
            ->where('a.user_id',$this->id)
            ->order('a.id desc')
            ->select();
        $have_task = $have_task ? $have_task->toArray() : [];
        foreach($have_task as &$item){
            $seller=Db::name('seller')
                ->where('id',$item['seller_id'])
                ->find();
            $shop=Db::name('shop')
                ->where('seller_id',$item['seller_id'])
                ->find();
            $item['seller']=substr_replace($shop['mobile'],'****',3,5);
            $type_array=array(
                '1'=>"文字好评",
                '2'=>"淘口令",
                '3'=>"二维码",
                '4'=>"直通车",
                '5'=>"通道任务",
            );
            $item['task_type']=$type_array[$item['task_type']];
        }
//        $task_step=1;
//        $this->assign('task_step',$task_step);
        $this->assign('have_task',$have_task);
        $topnav=5;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }
    //做任务列表 取消任务
    public function del_task(){
        $now=time();
        $data=input();
        $user_task=Db::name('user_task')
            ->where('id',$data['id'])
            ->find();
        if(!$user_task){
            return $this->error('此任务出现错误，请联系客服');
        }
        if($user_task['state'] == 2){
            return $this->error('此任务已取消');
        }
        $begin_day=strtotime(date('Y-m-d',time()));
        $begin_day_nine=$begin_day+9*3600;
        $begin_day_elevn=$begin_day+23*3600;
        $user=Db::name('users')->where('id',$this->id)->find();
        $return_reward=$user['reward']+1;
        if($now<$begin_day_nine || $now>$begin_day_elevn){
            $return=[
                'reward'=>$return_reward,
            ];
        } //当前时间如果是晚上11点到第二天9点 取消任务返还1银锭
        $delcounttime['cancel_time'] = ['between', [$begin_day_nine,$begin_day_elevn]];
        $return_task_count=Db::name('user_task')
            ->where('state',2)
            ->where('user_id',$this->id)
            ->where($delcounttime)
            ->count();
        if ($return_task_count <=2) {
            $return = [
                'reward' => $return_reward,
            ];
        } //买手白天9点到晚上11点接单可以自行放弃2个任务，系统不扣脉点。
        $between_time_day['complete_time']=['between',[$begin_day,$now]];
        $sell_task=Db::name('seller_task')
            ->where('id',$user_task['seller_task_id'])
            ->find();
        $del=[
            'state'=>2,
            'cancel_reason'=>'买手自主取消任务',
            'cancel_time'=>time(),
            'deltask_type'=>2
        ];
        $lately_user_task=Db::name('user_task')->where('seller_task_id',$user_task['seller_task_id'])
            ->max('id');
        if($lately_user_task==$user_task['id']){
            $return_task = [
                'incomplete_num' => $sell_task['incomplete_num'] + 1,
                'receipt_time'=>0,
            ];
        }else{
            $return_task = [
                'incomplete_num' => $sell_task['incomplete_num'] + 1,
            ];
        }
        Db::startTrans();
        try{
            $have_del_task=Db::name('user_task')->where('id',$user_task['id'])->update($del);
            Db::name('seller_task')->where('id',$sell_task['id'])->update($return_task);
            if($have_del_task){
                if($now<$begin_day_nine || $now>$begin_day_elevn){
                    $nine_return=Db::name('users')->where('id',$this->id)->update($return);
                    finance($this->id, 2, +1, 2, 13, "客服不上班期间（23点-9点）自己放弃任务{$user_task['task_number']},解除冻结1银锭");
                }else{
                    if ($return_task_count <2) {
                        $return_task_count_jia=$return_task_count+1;
                        $nine_return = Db::name('users')->where('id', $this->id)->update($return);
                        finance($this->id, 2, +1, 2, 13, "每天前2单任务自行放弃不扣银锭，第{$return_task_count_jia}单：{$user_task['task_number']}, 放弃后解除冻结1银锭 系统会自动返还");
                    }else{
                        finance($this->id, 2, -1, 2, 13, "用户自行放弃任务{$user_task['task_number']},扣除冻结的1银锭");
                    }
                }
            }
            $praise_ids = json_decode($user_task['ids']);
            if($user_task['ids'] && $praise_ids){
                Db::name('seller_task_praise')->where(['id'=>['in',$praise_ids]])->update(['state'=>0]);
            }
            if($user_task['is_shengji']==2){
                $key_ids = explode(',',$user_task['key_id']);
                if($user_task['key_id'] && $key_ids){
                    Db::name('task_word')->where(['id'=>['in',$key_ids]])->setInc('ynum',1);
                }
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('操作成功','mobile/task/maketask');
    }

    //做任务列表 自动取消任务
    public function  automatic_del_task(){
        $data=input();
        $user_task=Db::name('user_task')
            ->where('id',$data['id'])
            ->where('state',0)
            ->find();
        if(!$user_task){
            return $this->error('此任务出现错误，请联系客服');
        }
        if($user_task['state'] == 2){
            return $this->error('此任务已取消');
        }
        $sell_task=Db::name('seller_task')
            ->where('id',$user_task['seller_task_id'])
            ->find();
        $del=[
            'state'=>2,
            'cancel_reason'=>'任务时间超时，自动取消任务',
            'cancel_time'=>time(),
            'deltask_type'=>1
        ];
        $lately_user_task=Db::name('user_task')->where('seller_task_id',$user_task['seller_task_id'])
            ->max('id');
        if($lately_user_task==$user_task['id']){
            $return_task = [
                'incomplete_num' => $sell_task['incomplete_num'] + 1,
                'receipt_time'=>0,
            ];
        }else{
            $return_task = [
                'incomplete_num' => $sell_task['incomplete_num'] + 1,
            ];
        }
        Db::startTrans();
        try{
            $have_del_task=Db::name('user_task')->where('id',$user_task['id'])->update($del);
            Db::name('seller_task')->where('id',$sell_task['id'])->update($return_task);
            if($have_del_task){
                finance($this->id, 2, -1, 2, 13, "任务{$user_task['task_number']}超时取消,扣除冻结的1银锭");
            }
            $praise_ids = json_decode($user_task['ids']);
            if($user_task['ids'] && $praise_ids){
                Db::name('seller_task_praise')->where(['id'=>['in'=>$praise_ids]])->update(['state'=>0]);
            }
            if($user_task['is_shengji']==2){
                $key_ids = explode(',',$user_task['key_id']);
                if($user_task['key_id'] && $key_ids){
                    Db::name('task_word')->where(['id'=>['in',$key_ids]])->setInc('ynum',1);
                }
            }

            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->error('已超过任务时间，任务自动取消','Task/maketask');
    }
    //任务步骤
    public function taskstep(){
        $now=time();
        $admin_limit=Db::name('system')->where('id',1)->find();
        $admin_limit_switch=$admin_limit['switch'];//查询商品数字是否核对
        $params = input('param.');
        $user_task=Db::name('user_task')
            ->where('id',$params['id'])
            ->find();
        if($user_task['is_ys']==1){
            $user_task['principal']=$user_task['yf_price'].'+'.$user_task['wk_price'];
        }else{
        }
        $user_principal_limit=$user_task['principal']+100;
        $principal=$user_task['principal'];
        $user_principal_limit_min=$user_task['principal']-100;
        if($user_principal_limit_min<=0){
            $user_principal_limit_min=0;
        }else{
            $user_principal_limit_min=$user_principal_limit_min;
        }
        $end_time=$user_task['ending_time']*1000;
        $payment_time=$user_task['ending_time']-2*3600;
        $payment_end = $user_task['ending_time'];
        $sell_task=Db::name('seller_task')
            ->where('id',$user_task['seller_task_id'])
            ->find();
        $type_array=array(
            '1'=>"文字好评",
            '2'=>"淘口令",
            '3'=>"二维码",
            '4'=>"直通车",
            '5'=>"通道任务",
        );
        $shop=Db::name('shop')
            ->where('id',$sell_task['shop_id'])
            ->find();
        $key_word = $user_task['key'];
        if($sell_task['is_shengji']==1){
            $goods_id=json_decode($sell_task['goods_id']);                      //所有商品ID
            $goods_num=json_decode($sell_task['goods_num']);                    //所有商品数量
            $goods_unit_price=json_decode($sell_task['goods_unit_price']);      //所有商品价格
            $product_guige=explode('{}',$sell_task['goods_spec']);   //商品规格
            $main_product_id=$goods_id[0];
            $goods_key_id = Db::name('goods')->where(['id'=>$main_product_id])->value('goods_key_id');
            $main_product_message = Db::name('goods_key_world')->where(['id'=>$user_task['key_id']])->find();
            if($main_product_message){
                $filter=explode(',',$main_product_message['filter']);
                $arr['class1'] = isset($filter[0]) ? $filter[0] : '';
                $arr['class2'] = isset($filter[1]) ? $filter[1] : '';
                $arr['class3'] = isset($filter[2]) ? $filter[2] : '';
                $arr['class4'] = isset($filter[3]) ? $filter[3] : '';
                $discount = $main_product_message['discount'];
            }else{
                $arr = '';
                $discount = '';
            }
            $main_product_message_filter1=$arr?$arr['class1']:'';
            $main_product_message_filter2=$arr?$arr['class2']:'';
            $main_product_message_filter3=$arr?$arr['class3']:'';
            $main_product_message_filter4=$arr?$arr['class4']:'';
            if(!$main_product_message){
                $main_product_message['min_price'] = '';
                $main_product_message['max_price'] = '';
                $main_product_message['province'] = '';
            }
            $main_product_num=$goods_num[0];
            $main_product_price=$goods_unit_price[0];
            $main_product=Db::name('goods')
                ->where('id',$main_product_id)
                ->find();
            $goods_info = [];
            $num = 1;
            foreach($goods_id as $k =>$v){
                $good = Db::name('goods')->where('id',$v)->find();
                $good['pc_img'] = json_decode($good['pc_img']);
                $good['goods_id'] = $good['id'];
                foreach ($good['pc_img'] as &$item){
                    $item = '/user'.$item;
                }
                if($num == 1){
                    $res['key_word'] = $user_task['key'];
                }else{
                    $res = $this->getKey($good['id']);
                }
                $num++;
                $good['key'] = $res['key_word'];
                $goods_info[]=$good;
                $goods_info[$k]['shop_name']=$shop['shop_name'];
                if($k==0){
                    $goods_info[$k]['leixing']='主商品';
                }else{
                    $goods_info[$k]['leixing']='副商品';
                }
                $goods_info[$k]['buy_num']=$goods_num[$k];
                $goods_info[$k]['buy_price']=$goods_unit_price[$k];
                $goods_info[$k]['goods_spec']=$product_guige[$k];
                $goods_spec=$goods_info[0]['goods_spec'];
            }
        }elseif($sell_task['is_shengji']==2){
            $goods_info = Db::name('task_goods')->where(['task_id'=>$sell_task['id']])->select()->toArray();
            $main_product = $goods_info[0];
            $main_product_message = Db::name('task_word')->where(['task_id'=>$sell_task['id'],'goods_id'=>$main_product['goods_id'],'id'=>['in',explode(',',$user_task['key_id'])]])->find();
            if($main_product_message)$filter = explode(',',$main_product_message['filter']);
            $main_product_message_filter1 = isset($filter[0]) ? $filter[0] : '';
            $main_product_message_filter2 = isset($filter[1]) ? $filter[1] : '';
            $main_product_message_filter3 = isset($filter[2]) ? $filter[2] : '';
            $main_product_message_filter4 = isset($filter[3]) ? $filter[3] : '';
            $discount = $main_product_message ? $main_product_message['discount'] : '';
            $main_product_num = $main_product['num'];
            $main_product_price = $main_product['price'];
            $goods_spec = $main_product['goods_spec'];
            foreach ($goods_info as $key=>$item){
                $goods_info[$key]['leixing'] = $key==0 ? '主商品' : '副商品'.$key;
                $goods_info[$key]['shop_name'] = $shop['shop_name'];
                $goods_info[$key]['buy_num'] = $item['num'];
                $goods_info[$key]['buy_price'] = $item['price'];
                $goods_info[$key]['key_word'] = Db::name('task_word')->where(['task_id'=>$sell_task['id'],'goods_id'=>$item['goods_id'],'id'=>['in',explode(',',$user_task['key_id'])]])->value('key_word');
                $img = json_decode(Db::name('goods')->where(['id'=>$item['goods_id']])->value('pc_img'));
                foreach ($img as $k=>$item){
                    $img[$k] = '/user'.$item;
                }
                $goods_info[$key]['pc_img'] = $img;
            }
        }
        if(request()->isAjax()){

            $res = [
                'user_task_id'=>$user_task['id'],
                'admin_limit_switch'=>$admin_limit_switch,
                'user_task'=>$user_task,
                'end_time'=>$end_time,
                'sell_task'=>$sell_task,
                'main_product_message_filter1'=>$main_product_message_filter1,//主商品分类1
                'main_product_message_filter2'=>$main_product_message_filter2,//主商品分类2
                'main_product_message_filter3'=>$main_product_message_filter3,//主商品分类3
                'main_product_message_filter4'=>$main_product_message_filter4,//主商品分类4
                'main_product_message'=>$main_product_message,//主商品信息
                'main_product_num'=>$main_product_num,//主商品数量
                'main_product_price'=>$main_product_price,//主商品价格
                'main_product'=>$main_product,
                'shop'=>$shop,
                'goods_info'=>$goods_info,
            ];
            $this->success('succress','',$res);

        }
        $this->assign('type_array',$type_array);
        $this->assign('admin_limit_switch',$admin_limit_switch);
        $this->assign('user_task',$user_task);
        $this->assign('user_principal_limit',$user_principal_limit);//买手自填本金上限不能超过100
        $this->assign('principal',$principal);//买手默认自填本金
        $this->assign('user_principal_limit_min',$user_principal_limit_min);//买手自填本金下限不能超过100
        $this->assign('end_time',$end_time);
        $this->assign('payment_time',$payment_time);
        $this->assign('payment_end',$payment_end);
        $this->assign('sell_task',$sell_task);
        $this->assign('main_product_message_filter1',$main_product_message_filter1); //主商品分类1
        $this->assign('main_product_message_filter2',$main_product_message_filter2); //主商品分类2
        $this->assign('main_product_message_filter3',$main_product_message_filter3); //主商品分类3
        $this->assign('main_product_message_filter4',$main_product_message_filter4); //主商品分类4
        $this->assign('discount',$discount); //折扣服务
        $this->assign('main_product_message',$main_product_message); //主商品信息
        $this->assign('main_product_num',$main_product_num); //主商品数量
        $this->assign('main_product_price',$main_product_price); //主商品价格
        $this->assign('main_product',$main_product);
        $this->assign('shop',$shop);
        $this->assign('goods_info',$goods_info);
        $this->assign('goods_spec',$goods_spec);
        $this->assign('id',$params['id']);
        $this->assign('key_word',$key_word);
        return view();
    }
    //任务第二部 验证与上传数据
    public function task_two(){
        $data=input();
        $path = 'uploads' . DS . 'task' . DS;
        $res = aliyunOss::uploadBase64($data['keywordimg'],$path);
        $res2 = aliyunOss::uploadBase64($data['chatimg'],$path);
//        $res = Img::base64_image_content($data['keywordimg'],'./uploads/task/');
//        if($res['code']==0){
//            return $this->error('搜索关键词截图'.$res['data']);
//        }
//        $res2 = Img::base64_image_content($data['chatimg'],'./uploads/task/');
//        if($res2['code']==0){
//            return $this->error('聊天截图'.$res2['data']);
//        }
        $link=$data['inputall'];//商品地址核对
        foreach ($link as $k=>$v){
            $goods=Db::name('goods')
                ->where('id',$v['id'])
                ->find();
            if(!$v['input']){
                return $this->error('请核对商品链接');
            }
            $url=$v['input'];
            $url=str_replace("amp;","",$url);
            $resa = $this->convertUrlQuery($url);
            if(!isset($resa['id']) || $resa['id'] != $goods['taobao_id']){
                try {
                    $post_data['tkl'] =$url;
                    $post_data['apikey'] = "mN1phT7B23rkpX2BGowVVDiJWNaKAhah";
                    $dd = http_curl('http://api.tbk.dingdanxia.com/tkl/query', $post_data);
                    $num_iid=json_decode($dd)->data->num_iid;
                }catch (Exception $e){
                    return $this->error('请填写正确的链接');
                }
                if($num_iid != $goods['taobao_id']){
                    return $this->error('请填写正确的链接');
                }
            }

        }
        $user_task=Db::name('user_task')->where('id',$data['user_task_id'])->find();
        if($user_task['state'] != 0){
            return $this->error('此任务出现错误，请检查');
        }
        $admin_limit=Db::name('system')->where('id',1)->find();
        $admin_limit_switch=$admin_limit['switch'];//查询商品数字是否核对
        if($admin_limit_switch ==1){
            $goodsnum=$data['inputallnum'];//商品数字核对
            foreach ($goodsnum as $k=>$v){
                $goods=Db::name('goods')
                    ->where('id',$v['id'])
                    ->find();
                if(!$v['inputnum']){
                    return $this->error('请核对商品数字');
                }
                $num=$v['inputnum'];
                if($num != $goods['number']){
                    return $this->error('商品核对数字不正确');
                }
            }
        }
        $edit_user_task=[
            'keywordimg' => 'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res,
            'chatimg' => 'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res2,
            'else_link1'=>$data['dizhi1'],
            'else_link2'=>$data['dizhi2'],
            'task_step'=>3,
            'step_two_complete'=>time(),
            'update_time'=>time()
        ];
        Db::name('user_task')->where('id',$data['user_task_id'])->update($edit_user_task);
        return $this->success('核对成功，进入第三步');
    }

    //淘口令核对
    public function task_hedui(){
        $data=input();
        if(!$data['input']){
            return $this->error('商品地址为空');
        }
        $goods=Db::name('goods')
            ->where('id',$data['id'])
            ->find();
        $url=$data['input'];
        $url=str_replace("amp;","",$url);
        $res = $this->convertUrlQuery($url);
        if(!isset($res['id']) || $res['id'] != $goods['taobao_id']){
            try {
                $post_data['tkl'] =$url;
                $post_data['apikey'] = "mN1phT7B23rkpX2BGowVVDiJWNaKAhah";
                $dd = http_curl('http://api.tbk.dingdanxia.com/tkl/query', $post_data);
                $num_iid=json_decode($dd)->data->num_iid;
            }catch (Exception $e){
                return $this->error('请填写正确的链接');
            }
            if($num_iid != $goods['taobao_id']){
                return $this->error('请填写正确的链接');
            }
        }
        return $this->success('商品核对地址正确');
    }

    /**
     * @notes 识别链接id
     * @date 2019/10/15
     * @time 13:56
     * @param string $query 链接
     * @return array|bool
     */
    public function convertUrlQuery($query="https://item.taobao.com/item.htm?id=603687399746")
    {
        $url_info = parse_url($query);
        if(!isset($url_info['query']))return false;
        $queryParts = explode('&', $url_info['query']);
        $params = array();
        foreach ($queryParts as $param)
        {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }

        return $params;
    }
    //淘口令核对
    public function task_hedui_ce(){
        $url="【真人娃娃机抖音同款商用零食商用抓娃娃机商场摆摊户外设备广场】https://m.tb.cn/h.eEKbYJ1?sm=b9a5c7 點ゞ撃°鏈バ接，再选择瀏覽●噐○咑ぺ鐦；或復ず■淛这句话₳04jJYxAtwZP₳后咑閞綯℡寳";
        try {
            $post_data['tkl'] =$url;
            $post_data['apikey'] = "j2m61j2Bn4kNWOukPawOAwiMxJi8Esba";
            $dd = http_curl('http://api.tbk.dingdanxia.com/tkl/query', $post_data);
            $num_iid=json_decode($dd)->data->num_iid;
        }catch (Exception $e){
            return $this->error('请填写正确的链接');
        }
        if($num_iid != "582620397075"){
            return $this->error('商品核对地址不正确');
        }else{
            return $this->success('商品核对地址正确');
        }
    }
    //核对数字
    public function task_heduinum(){
        $data=input();
        if(!$data['inputnum']){
            return $this->error('数字核对不能为空');
        }
        $goods_num=$data['inputnum'];
        $goods=Db::name('goods')
            ->where('id',$data['id'])
            ->find();
        if($goods_num != $goods['number']){
            return $this->error('商品数字核对不正确');
        }else{
            return $this->success('商品数字核对正确');
        }
    }
    //任务第三部
    public function task_three(){
        $data=input();
        if(!$data['table_order_id']){
            return $this->error('请输入订单号.');
        }
        if(!$data['order_detail_img']){
            return $this->error('请上传付款截图.');
        }
        $path = 'uploads' . DS . 'task' . DS;
        $res = aliyunOss::uploadBase64($data['order_detail_img'],$path);
//        $res = Img::base64_image_content($data['order_detail_img'],'./uploads/task/');
//        if($res['code']==0){
//            return $this->error('订单详情截图'.$res['data']);
//        }
        $now=time();
        $judge_time=Db::name('user_task')->where('id',$data['user_task_id'])->find();
        if($judge_time['state']!=0)return $this->error('订单状态不正确或已取消');  //2019/12/04/13/37修改，添加订单状态验证
        $disparity_time=date('Y-m-d',$judge_time['create_time']);
        $disparity_time=strtotime($disparity_time);
        $disparity_time=$disparity_time+24*3600;
        $ten_cant_time=60*10+$judge_time['create_time'];
        if($judge_time['task_type'] ==2 && time()<$disparity_time){
            return $this->error('隔天任务不能在当天提交。');
        }
        $plan_time_end=$judge_time['ending_time']-7200;
        if($judge_time['task_type'] ==3 && time()<$plan_time_end){
            return $this->error('定时付款任务，未达到付款时间。');
        }
        if($now<$ten_cant_time){
            return $this->error('接单时间十分钟内不能提交');
        }
        $user_task=Db::name('user_task')->where('id',$data['user_task_id'])->find();
        $user_principal_limit=$user_task['principal']+100;
        $user_principal_limit_min=$user_task['principal']-100;
        if($user_principal_limit_min<=0){
            $user_principal_limit_min=0;
        }else{
            $user_principal_limit_min=$user_principal_limit_min;
        }
        if($user_task['is_ys']==1){
            $user_principal_limit=$user_task['yf_price']+100;
            $user_principal_limit_min=$user_task['yf_price']-100;
            if($user_principal_limit_min<=0){
                $user_principal_limit_min=0;
            }
        }
        if($data['user_principal']<$user_principal_limit_min || $data['user_principal']>$user_principal_limit){
            return $this->error('实际付款金额不得超过或者小于订单金额100元');
        }
        if($user_task['state'] != 0){
            return $this->error('此任务出现错误，请检查');
        }
        if($user_task['is_ys'] ==1){
            $state =0;
            $task_step=4;
        }else{
            $state =3;
            $task_step=3;
        }
        if($data['threeradio'] == 2){
            if(empty($data['province'])){
                return $this->error('请选择地址。');
            }
            if(empty($data['inputstreet'])){
                return $this->error('请填写详细地址。');
            }
            if(empty($data['adressperson'])){
                return $this->error('请填写收货人姓名。');
            }
            if(empty($data['addressphone'])){
                return $this->error('请填写收货人收货人手机号码。');
            }
            $user=Db::name('users')->where('id',$this->id)->find();
            if($user['qualified'] >= 5){
                return $this->error('每个人每月最多修改五次订单收货地址。');
            }
            $task_type=Db::name('user_task')->where('id',$data['user_task_id'])->find();
            $task_type_seller=Db::name('seller_task')->where('id',$task_type['seller_task_id'])->find();
            if($task_type['task_type'] ==3){
                if($task_type_seller['timing_time']>time()){
                    return $this->error('此单为定时任务，不能提前上传订单号.');
                }
            }
            $qualified=$user['qualified']+1;
            $edit_user=[
                'qualified'=>$qualified,
            ];
            $edit_user_task=[
                'table_order_id'=>$data['table_order_id'],
                'user_principal'=>$data['user_principal'],
                'seller_principal'=>$data['user_principal'],
                'consignee'=>$data['consignee'],
                'order_detail_img' => 'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res,
                'state'=>$state,
                'task_step'=>$task_step,
                'update_time'=>time(),
                'address'=>$data['province'].'-'.$data['city'].'-'.$data['block'].'-'.$data['inputstreet'],
                'addressname'=>$data['adressperson'],
                'addressphone'=>$data['addressphone'],
                'upload_order_time'=>time(),
                'update_time'=>time(),
                'qualified_state'=>1,
            ];
//            $edit_seller_task=[
//                'receipt_time'=>time(),
//            ];
            Db::name('users')->where('id',$this->id)->update($edit_user);
            Db::name('user_task')->where('id',$data['user_task_id'])->update($edit_user_task);
            //  Db::name('seller_task')->where('id', $task_type_seller['id'])->update($edit_seller_task);
            return $this->success('提交任务成功','My/index');
        }else{
            $task_type = Db::name('user_task')->where('id', $data['user_task_id'])->find();
            $task_type_seller = Db::name('seller_task')->where('id', $task_type['seller_task_id'])->find();
            $edit_user_task=[
                'table_order_id'=>$data['table_order_id'],
                'user_principal'=>$data['user_principal'],
                'seller_principal'=>$data['user_principal'],
                'consignee'=>$data['consignee'],
                'order_detail_img'=>$res['data'],
                'state' => $state,
                'task_step'=>$task_step,
                'upload_order_time'=>time(),
                'update_time'=>time()
            ];
//            $edit_seller_task=[
//                'receipt_time'=>time(),
//            ];
            Db::name('user_task')->where('id',$data['user_task_id'])->update($edit_user_task);
            //  Db::name('seller_task')->where('id', $task_type_seller['id'])->update($edit_seller_task);
            return $this->success('提交任务成功','My/index');
        }

    }

    //预售任务 上传尾款截图
    public function wk(){
        $data=input('param.id');
        $list=Db::name('user_task')
            ->field('a.id,a.seller_task_id,a.terminal,a.state,a.task_number,a.seller_task_id,
            a.create_time,a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,a.ending_time,a.principal,
            a.commission,a.user_principal,a.keywordimg,a.chatimg,a.seller_principal,a.is_ys,a.yf_price,a.wk_price,
            a.delivery,a.delivery_num,a.update_time,a.text_praise,a.img_praise,a.ids,a.is_shengji,
            a.video_praise,a.ys_time,b.publish_time,b.task_type,b.memo,c.wwid,d.shop_name')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->join('user_buyno c','a.user_buyno_id = c.id')
            ->join('shop d','b.shop_id =d.id')
            ->where('a.id',$data)
            ->find();
        //dump($data);exit;
        if($list['is_ys']==1){
            $list['principal']=$list['yf_price'].'+'.$list['wk_price'];
        }else{

        }
        $list['ys_time']=date('Y-m-d H:i:s',$list['ys_time']);
        $list['create_time']=date('Y-m-d H:i:s',$list['create_time']);
        $list['update_time']=date('Y-m-d H:i:s',$list['update_time']);
        $terminal_type=array(
            '1'=>"电脑",
            '2'=>"手机"
        );
        $list['terminal']=$terminal_type[$list['terminal']];
        $type_array=array(
            '1'=>"文字好评",
            '2'=>"淘口令",
            '3'=>"二维码",
            '4'=>"直通车",
            '5'=>"通道任务",
        );
        $list['task_type']=$type_array[$list['task_type']];
        $state_array=array(
            '0'=>"正在进行",
            '1'=>"已完成",
            '2'=>"已取消",
            '3'=>"待发货",
            '4'=>"已收货",
            '5'=>"待返款",
            '6'=>"待确认返款",
        );
        $list['state']=$state_array[$list['state']];
        $goods = [];
        if($list['is_shengji']==1){
            $goods_id=json_decode($list['goods_id']);
            $text_praise=json_decode($list['text_praise']);
            foreach ($goods_id as $k => $v){
                $good=Db::name('goods')->where('id',$v)->find();
                $good['goods_id'] = $good['id'];
                $main_product_pc_img=json_decode($goods[$k]['pc_img']);
                $good['pc_img']=$main_product_pc_img[0];
                $good['text_praise']=isset($text_praise[$k]) ? $text_praise[$k] : '';
                if($k==0){
                    $good['img_praise'] = json_decode($list['img_praise']);
                    $good['video_praise'] = $list['video_praise'];
                }else{
                    $good['img_praise'] = [];
                    $good['video_praise'] = '';
                }
                $goods[] = $good;
            }
        }elseif($list['is_shengji']==2){
            $ids = json_decode($list['ids']);
            $goods=Db::name('task_goods')->where('task_id',$list['seller_task_id'])->select()->toArray();
            foreach ($goods as $k =>$v){
                $goods[$k]['text_praise']=Db::name('seller_task_praise')->where(['seller_task_id'=>$list['seller_task_id'],'type'=>1,'id'=>['in',$ids]])->value('content');
                $img = Db::name('seller_task_praise')->where(['seller_task_id'=>$list['seller_task_id'],'type'=>2,'id'=>['in',$ids]])->value('content');
                $array=$img ? json_decode($img) : [];
                $goods[$k]['img_praise'] = implode(",", $array);
                $goods[$k]['video_praise']=Db::name('seller_task_praise')->where(['seller_task_id'=>$list['seller_task_id'],'type'=>3,'id'=>['in',$ids]])->value('content');
            }
        }
        // dump($goods);
        //dump($list);
        // exit;
        $this->assign('product',$goods);
        $this->assign('list',$list);
        return view();
    }

    public function take_wk(){
        $data=input();
        $path = 'uploads' . DS . 'task' . DS;
        $res = aliyunOss::uploadBase64($data['high_praise_img'],$path);
        //$res = Img::base64_image_content($data['high_praise_img'],'./uploads/task/');
//        if($res['code']==0){
//            return $this->error('尾款截图'.$res['data']);
//        }
        $take_delivery=[
            'wk_praise_img'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res,
            'state'=>3,
            'update_time'=>time(),
            'wk_praise_time'=>time(),
            'wk_input_detail'=>$data['wk_input_detail'],
            'wk_order_detail'=>$data['wk_order_detail']
        ];
        $user_task=Db::name('user_task')->where('id',$data['task_id'])->find();
        $max=$user_task['wk_price']+100;
        $min=$user_task['wk_price']-100;
        if($min<0){
            $min=0;
        }
        $number = $data['wk_input_detail'];
        if ($number < $min || $number > $max) {
            return $this->error('实际付款金额不得超过或者小于订单金额100元');
        }
        $now=time();
        $now_zero=strtotime(date('Ymd'));
        $now_two_four=$now_zero+24*3600;
        if($user_task['ys_time']<=$now_zero || $user_task['ys_time']>=$now_two_four){
            return $this->error('不在预售时间之内，不能上传尾款截图。');
        }
        if($user_task['state'] !=0){
            return $this->error('此任务出现错误，请检查。');
        }
        $resa=Db::name('user_task')
            ->where('id',$data['task_id'])
            ->update($take_delivery);
        if($resa){
            return $this->success('上传尾款截图成功',url('My/index'));
        }
    }
    public function tasknumberchange(Request $request){
        $data = $request->param();
        $max=$data['user_principal_limit'];
        $min=$data['user_principal_limit_min'];
        $number=$data['number'];
        $str=strlen($number);
        if($str>=1 && $str<=11){
        }else{
            return $this->error('超出限制');
        }
        if($number< $min || $number>$max){
            return $this->error('实际付款金额不得超过或者小于订单金额100元');
        }else{
            return $this->success('实际付款金额填写成功');
        }
    }

    /**
     * @notes 买手推荐奖励发放
     * @date 2019/9/25
     * @time 12:39
     * @param $user_id 买手id
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function userTjjAng($user_id, $task_id)
    {
        $user = Db::name('users')->where(['id' => $user_id])->field('id,username,tjuser,tjuser_state')->find();
        $task_id = $task_id;
        if ($user) {
            if ($user['tjuser_state'] == 1) {
                $tjuser = Db::name('users')->where(['username' => $user['tjuser']])->find();
            } else {
                $tjuser = Db::name('seller')->where(['seller_name' => $user['tjuser']])->find();
            }
            if ($tjuser) {
                $price = 0.5;
                $str = '';
                $user_update['reward'] = $tjuser['reward'] + $price;
                $user_update['tj_award'] = $tjuser['tj_award'] + $price;
                $user_update['tj_award_day'] = $tjuser['tj_award_day'] + $price;
                if ($user['tjuser_state'] == 1) {
                    $prices = Db::name('user_reward_recharge')->where(['uid' => $tjuser['id'], 'fromuser' => $user['id'], 'type' => 6])->sum('price');
                    if ($prices >= 1000) return false;
                    if($prices==0){
                        $price = 5;
                        $str = '首单';
                        $user_update['reward'] = $tjuser['reward'] + $price;
                        $user_update['tj_award'] = $tjuser['tj_award'] + $price;
                        $user_update['tj_award_day'] = $tjuser['tj_award_day'] + $price;
                    }
                    Db::name('users')->where(['id' => $tjuser['id']])->update($user_update);
                    if (!finance($tjuser['id'], 2, $price, 2, 6, "推广买家({$user['username']})任务{$task_id['task_number']}已完成,{$str}奖励{$price}银锭", 1, $user_id)) throw new Exception('财务写入失败！');
                } else {
                    $prices = Db::name('seller_reward_recharge')->where(['uid' => $tjuser['id'], 'fromuser' => $user['id'], 'type' => 6])->sum('price');
                    if ($prices >= 1000) return false;
                    if($prices==0){
                        $price = 5;
                        $str = '首单';
                        $user_update['reward'] = $tjuser['reward'] + $price;
                        $user_update['tj_award'] = $tjuser['tj_award'] + $price;
                        $user_update['tj_award_day'] = $tjuser['tj_award_day'] + $price;
                    }
                    Db::name('seller')->where(['id' => $tjuser['id']])->update($user_update);
                    if (!finance($tjuser['id'], 1, $price, 2, 6, "推广买家({$user['username']})任务{$task_id['task_number']}已完成,{$str}奖励{$price}银锭", 1, $user_id)) throw new Exception('财务写入失败！');
                }
            }
        }
    }
    /**
     * @notes 商家推荐奖励发放
     * @date 2019/9/25
     * @time 12:39
     * @param $seller_id 商家id
     * @param $user_task_id 商家id
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function sellerTjjAng($seller_id,$user_task_id){
        $user_task = Db::name('user_task')->where(['id'=>$user_task_id])->field('seller_task_id,task_number')->find();
        $seller_task_id = $user_task['seller_task_id'];
        $num = Db::name('user_task')->where(['seller_task_id'=>$seller_task_id,'state'=>1])->count('id');
        if($num != 1)return false;
        $seller = Db::name('seller')->where(['id'=>$seller_id])->field('id,seller_name,tjuser,tjuser_state')->find();
        if($seller){
            if($seller['tjuser_state']==1){
                $tjseller = Db::name('users')->where(['username'=>$seller['tjuser']])->find();
            }else{
                $tjseller = Db::name('seller')->where(['seller_name'=>$seller['tjuser']])->find();
            }
            if($tjseller){
                $price = 1;
                $seller_update['reward'] = $tjseller['reward'] + $price;
                $seller_update['tj_award'] = $tjseller['tj_award'] + $price;
                $seller_update['tj_award_day'] = $tjseller['tj_award_day'] + $price;
                if($seller['tjuser_state']==1){
                    $prices = Db::name('user_reward_recharge')->where(['uid'=>$tjseller['id'],'fromuser'=>$seller['id'],'type'=>6])->sum('price');
                    if($prices >= 500)return false;
                    Db::name('users')->where(['id'=>$tjseller['id']])->update($seller_update);
                    if(!finance($tjseller['id'],2,$price,2,6,"推广商家({$seller['seller_name']})发布任务{$user_task['task_number']}完成首单,发放推荐奖励{$price}银锭",2,$seller_id))throw new Exception('财务写入失败！');
                }else{
                    $prices = Db::name('seller_reward_recharge')->where(['uid'=>$tjseller['id'],'fromuser'=>$seller['id'],'type'=>6])->sum('price');
                    if($prices >= 500)return false;
                    if($seller['tjuser_state']==2)Db::name('seller')->where(['id'=>$tjseller['id']])->update($seller_update);
                    if(!finance($tjseller['id'],1,$price,2,6,"推广商家({$seller['seller_name']})发布任务{$user_task['task_number']}完成首单,发放推荐奖励{$price}银锭",2,$seller_id))throw new Exception('财务写入失败！');
                }

            }
        }
    }

    //最后一步 买手确认返款
    public function confirm_refund(){
        $data=input();
        $refund_task=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where('id',$data['id'])
            ->find();
        if($refund_task['state'] != 6){
            return $this->error('此订单错误，请联系客服.');
        }
        $user=Db::name('users')
            ->where('id',$this->id)
            ->find();
        if(empty($refund_task)){
            return $this->error('此订单错误，请联系客服.');
        }else{
            $add_balance=$user['balance']+$refund_task['seller_principal'];//返款佣金 买手本身的佣金+商家确认返还的佣金
            $detail_commission=$refund_task['commission']+$refund_task['user_divided']; //总共获得的佣金 佣金+分成佣金
            $return_one_reward=$user['reward']+1;
            $add_reward=$refund_task['commission']+$refund_task['user_divided']+$return_one_reward;//返款银锭 佣金+佣金分成+买手本身的银锭+接任务时抵押的1银锭
            $refund_one_reward=[
                'reward'=>$return_one_reward,
                'mc_task_num'=>$user['mc_task_num']+1,
            ];
            $refund_message=[
                'balance'=>$add_balance,
                'reward'=>$add_reward,
            ];
            $edit_user_task=[
                'state'=>1,
                'complete_time'=>time(),
                'update_time'=>time()
            ];
            Db::startTrans();
            try {
                Db::name('user_task')
                    ->where('user_id',$refund_task['user_id'])
                    ->where('id',$refund_task['id'])
                    ->update($edit_user_task);
                $return_one_res=Db::name('users')->where('id', $this->id)->update($refund_one_reward);
                if($return_one_res){
                    finance($this->id, 2, +1, 2, 11, "任务{$refund_task['task_number']}已完成，退还冻结的1银锭");
                }
                $res = Db::name('users')->where('id', $this->id)->update($refund_message);
                if($res) {
                    finance($this->id, 2, +$detail_commission, 2, 7, "任务{$refund_task['task_number']}已完成，佣金{$detail_commission}银锭");
                    finance($this->id, 2, +$refund_task['seller_principal'], 1, 7, "任务{$refund_task['task_number']}已完成,退还本金{$refund_task['seller_principal']}元");
                }

                Db::name('seller_task')->where(['id'=>$refund_task['seller_task_id']])->setInc('complete_num',1);
                $this->userTjjAng($this->id,$refund_task);
                $this->sellerTjjAng($refund_task['seller_id'],$refund_task['id']);
                $this->eJiang($this->id);
                $nums = Db::name('seller_task')->where(['id'=>$refund_task['seller_task_id']])->field('num,complete_num')->find();
                if($nums['num']<=$nums['complete_num']){
                    Db::name('seller_task')->where(['id'=>$refund_task['seller_task_id']])->update(['status'=>6,'complete_time'=>time()]);
                }
                $month=strtotime(date('Ym'));
                $where['create_time']=['gt',$month];
                $user_invited=Db::name('user_invited')
                    ->where('user_id',$this->id)
                    ->where($where)
                    ->find();
                if(!$user_invited){
                    $insert_invited=[
                        'user_id'=>$this->id,
                        'create_time'=>time(),
                        'task_number'=>1,
                    ];
                    Db::name('user_invited')->insert($insert_invited);
                }else{
                    $task_number=$user_invited['task_number']+1;
                    $update_invited=[
                        'task_number'=>$task_number,
                    ];
                    Db::name('user_invited')->where('id',$user_invited['id'])->update($update_invited);
                }
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                if($e){
                    return $this->error('操作失败。');
                    //return $this->error($e->getMessage());
                }

            }
            $value=4;
            return $this->success('返款成功',url('My/taskmanagement',['value'=>$value]));
        }

    }

    //买手确认一键返款数据
    public function  all_seller_principal(Request $request){
        $data = $request->param();
        if(empty($data['taskid'])){
            return $this->error('请先勾选任务.');
        }
        $task_id=$data['taskid'];
        foreach ($task_id as $k =>$v){
            $seller_principal[$k]=Db::name('user_task')->where('id',$v)->find();
            $principal[$k]=$seller_principal[$k]['seller_principal'];
        }
        $all_seller_principal = array_sum(array_column($seller_principal, 'seller_principal'));
        $res['all_seller_principal']=$all_seller_principal;
        $res['principal']=$principal;
        return $this->success('success','',$res);
    }
    //买手确认一键返款
    public function allfankuan(Request $request){
        $data = $request->param();
        if(empty($data['taskid'])){
            return $this->error('请先勾选任务.');
        }
        $task_id=$data['taskid'];
        foreach ($task_id as $k =>$v){
            $refund_task[$k]=Db::name('user_task')->where('id',$v)->find();
        }

        foreach ($refund_task as $k=>$v){
            if($v['state'] != 6){
                return $this->error('订单有错误，无法一键返款.');
            }
            $user=Db::name('users')
                ->where('id',$this->id)
                ->find();
            if(empty($v)){
                return $this->error('订单有错误，无法一键返款.');
            }else{
                $add_balance[$k]=$user['balance']+$v['seller_principal'];
                $detail_commission[$k]=$v['commission']+$v['user_divided'];
                $return_one_reward[$k]=$user['reward']+1;
                $add_reward[$k]=$v['commission']+$v['user_divided']+$return_one_reward[$k];
                $refund_one_reward[$k]=[
                    'reward'=>$return_one_reward[$k],
                    'mc_task_num'=>$user['mc_task_num']+1,
                ];
                $refund_message[$k]=[
                    'balance'=>$add_balance[$k],
                    'reward'=>$add_reward[$k],
                ];
                $edit_user_task[$k]=[
                    'state'=>1,
                    'complete_time'=>time(),
                    'update_time'=>time()
                ];
                Db::startTrans();
                try {
                    Db::name('user_task')
                        ->where('user_id',$v['user_id'])
                        ->where('id',$v['id'])
                        ->update($edit_user_task[$k]);
                    $return_one_res=Db::name('users')->where('id', $this->id)->update($refund_one_reward[$k]);
                    if($return_one_res){
                        finance($this->id, 2, +1, 2, 11, "任务{$v['task_number']}已完成，退还冻结的1银锭");
                    }
                    $res = Db::name('users')->where('id', $this->id)->update($refund_message[$k]);
                    if($res) {
                        finance($this->id, 2, +$detail_commission[$k], 2, 7, "任务{$v['task_number']}已完成，佣金{$detail_commission[$k]}银锭");
                        finance($this->id, 2, +$v['seller_principal'], 1, 7, "任务{$v['task_number']}已完成,退还本金{$v['seller_principal']}元");
                    }

                    Db::name('seller_task')->where(['id'=>$v['seller_task_id']])->setInc('complete_num',1);
                    $this->userTjjAng($this->id,$v);
                    $this->sellerTjjAng($v['seller_id'],$v['id']);
                    $this->eJiang($this->id);
                    $nums[$k] = Db::name('seller_task')->where(['id'=>$v['seller_task_id']])->field('num,complete_num')->find();
                    if($nums[$k]['num']<=$nums[$k]['complete_num']){
                        Db::name('seller_task')->where(['id'=>$v['seller_task_id']])->update(['status'=>6,'complete_time'=>time()]);
                    }
                    $month=strtotime(date('Ym'));
                    $where['create_time']=['gt',$month];
                    $user_invited=Db::name('user_invited')
                        ->where('user_id',$this->id)
                        ->where($where)
                        ->find();
                    if(!$user_invited){
                        $insert_invited=[
                            'user_id'=>$this->id,
                            'create_time'=>time(),
                            'task_number'=>1,
                        ];
                        Db::name('user_invited')->insert($insert_invited);
                    }else{
                        $task_number=$user_invited['task_number']+1;
                        $update_invited=[
                            'task_number'=>$task_number,
                        ];
                        Db::name('user_invited')->where('id',$user_invited['id'])->update($update_invited);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    if($e){
                        return $this->error('操作失败。');
                        //return $this->error($e->getMessage());
                    }

                }
            }
        }
        $value=4;
        return $this->success('返款成功', url('My/taskmanagement',['value'=>$value]));
    }

    /**
     * @Function  完成单数额外奖励发放
     * @Author 扬风
     * @Date: 2020/2/27
     * @Time: 22:05
     * @param $user_id 用户id
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eJiang($user_id){
        $user = Db::name('users')->where(['id'=>$user_id])->find();
        $arr = [50,100,150,200];
        $arrt = [
            50=>10,
            100=>25,
            150=>45,
            200=>70,
        ];
        if(!in_array($user['mc_task_num'],$arr))return false;
        $price = $arrt[$user['mc_task_num']];
        //给推荐人发奖励
        if($user['tjuser_state']==1){    //买手
            $tjuser = Db::name('users')->where(['username'=>$user['tjuser']])->find();
            Db::name('users')->where(['id'=>$tjuser['id']])->setInc('reward',$price);
            if(!finance($tjuser['id'], 2, $price, 2, 20, "推荐的买手{$user['username']}本月完成{$user['mc_task_num']}单,额外奖励{$price}银锭")) throw new Exception('财务写入错误!');
        }else if($user['tjuser_state']==2){   //商家
            $tjuser = Db::name('seller')->where(['seller_name'=>$user['tjuser']])->find();
            Db::name('seller')->where(['id'=>$tjuser['id']])->setInc('reward',$price);
            if(!finance($tjuser['id'], 1, $price, 2, 20, "推荐的买手{$user['username']}本月完成{$user['mc_task_num']}单,额外奖励{$price}银锭!")) throw new Exception('财务写入错误!');
        }

    }
}
