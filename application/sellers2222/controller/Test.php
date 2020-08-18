<?php


namespace app\seller\controller;


use think\Controller;
use think\Db;
use think\Cache;
use think\Exception;
use think\Request;

class Test extends Controller
{

    public function index(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        dump($redis->keys('*'));
        $redis->rpush("test_test","333");
        $redis->expire ("test_test",5);
        dump($redis->ttl ("test_test"));
        //dump($redis->lrange("1575943490889014",0,-1));
        /*foreach($redis->keys('*') as $item){
            $redis->del($item);
        }*/
        //dump($redis->keys('*'));
        exit;

        $redis->rpush("test_test","333");
        $redis->rpush("test_test","333");
        $redis->rpush("test_test","444");
        $redis->rpush("test_test","555");
        dump($redis->lrange("test_test",0,-1));
        $redis->lrem('test_test','333',0);
        $redis->lrem('test_test','555',0);
        dump($redis->lrange("test_test",0,-1));
    }

    public function test(){
        exit;
        $list = Db::name('seller_task')->where(['rand_num'=>''])->limit(1000)->select()->toArray();
        if(count($list) < 1000)dump($list);
        foreach ($list as $item){
            $rand_num = $item['id'] . rand(1000,9999);
            Db::name("seller_task")->where(['id'=>$item['id']])->update(['rand_num'=>$rand_num]);
        }

    }


    public function get_task(Request $request){
        $data = $request->param();
        if(!$data['buyno_id'])return $this->error('买号不能为空，请选择买号');
        if(!$data['terminal'])return $this->error('请先选择终端');
        $now=time();
        $user=Db::name('users')->where('id',$this->id)->find();
        if($user['vip_time'] <$now || $user['vip']!=1)return $this->error('您还不是VIP,无法接单!');
        if($user['reward'] < 1)return $this->error('银锭不足,请充值!');
        $seller_task = Db::name('seller_task')->where(['task_number'=>$data['task_number']])->find();
        if($seller_task['incomplete_num'] == 0){
            return $this->error('此任务已全部被领取！');
        }
        if($seller_task['receipt_time'] + ($seller_task['union_interval_time'] * 60) > $now)return $this->error('未达到商家设定的接单间隔时间！');
        $buyno = Db::name('user_buyno')->where(['id'=>$data['buyno_id']])->find();
        $res = $this->buyno($buyno['id'],$buyno['wwid'],$seller_task['seller_id'],$seller_task['shop_id']);
        if($res['code']==0)return $this->error($res['msg']);
        $res = $this->user($this->id,$seller_task['shop_id']);
        if($res['code']==0)return $this->error($res['msg']);
        $res = $this->order($seller_task['id']);
        if($res['code']==0)return $this->error($res['msg']);
        if($seller_task['is_timing_pay'] ==1){  //定时付款任务
            $task_type=3;
            $ending_time=$seller_task['timing_time']+120*60;
            //$ending_time=time()+30;
        }else if($seller_task['next_day'] ==1){ //隔天任务
            $task_type=2;
            $next_day_four_pm=strtotime(date('Y-m-d',time()))+40*60*60+40*60;//隔天下午4点40
            $ending_time=$next_day_four_pm;
        }else{                                  //普通任务
            $task_type=1;
            $ending_time=$now+120*60;
        }
        $selltask_change=[
            'incomplete_num'=>$seller_task['incomplete_num']-1,
            'receipt_time'=>time(),
        ];

        $reward_change=[
            'reward'=>$user['reward']-1,
            'last_time'=>time(),
        ];
        $commission=$data['commission'];//佣金
        $user_divided =$seller_task['user_divided']/$seller_task['num'];//每单的加赏佣金
        $user_divided=round($user_divided, 2);
        $task=[
            'user_id'=>$this->id,
            'seller_id'=>$seller_task['seller_id'],
            'seller_task_id'=>$seller_task['id'],
            'task_number'=>$seller_task['task_number'].'-'.time().rand(100,999),
            'goods_id'=>$seller_task['goods_id'],
            'shop_id'=>$seller_task['shop_id'],
            'goods_unit_price'=>$seller_task['goods_unit_price'],
            'goods_num'=>$seller_task['goods_num'],
            'user_buyno_id'=>$buyno['id'],
            'user_buyno_wangwang'=>$buyno['wwid'],
            'principal'=>$data['total_price'],
            'commission'=>$commission,
            'terminal'=>$seller_task['terminal'],
            'state'=>0,
            'create_time'=>time(),
            'update_time'=>time(),
            'address'=>$buyno['addresspro'].','.$buyno['addresscity'].','.$buyno['addressarea'].','.$buyno['addresstext'],
            'task_type'=>$task_type,
            'ending_time'=>$ending_time,
            'task_step'=>1,
            'shipping_address'=>$seller_task['address'],
            'shop_name'=>$seller_task['shop_name'],
            'user_divided'=>$user_divided,
            'addressname'=>$buyno['addressname'],
            'addressphone'=>$buyno['addressphone'],
            'delivery_status'=>$seller_task['is_free_shiping'] == 3 ? 1 : 0,
        ];
        $goods_id = json_decode($seller_task['goods_id'])[0];
        $res = $this->getKey($goods_id);
        $task['key_id'] = $res['key_id'];
        $task['key'] = $res['key_word'];
        Db::startTrans();
        try{
            $ids = [];
            if($seller_task['is_praise']){
                $list1 = $this->praise($seller_task['id'],$seller_task['goods_id']);
                $task['text_praise'] = json_encode($list1['praise']);
                $ids = $list1['id'];
            }
            if($seller_task['is_img_praise']){
                $list2 = $this->praiseImg($seller_task['id'],2);
                $task['img_praise'] = $list2['content'];
                $ids[] = $list2['id'];
            }
            if($seller_task['is_video_praise']){
                $list3 = $this->praiseImg($seller_task['id'],3);
                $task['video_praise']  = $list3['content'];
                $ids[] = $list3['id'];
            }
            $task['ids'] = json_encode($ids);
            $res=Db::name('users')->where('id',$this->id)->update($reward_change);
            if($res) {
                finance($this->id, 2, -1, 2, 4, "买手接任务{$task['task_number']},冻结1银锭");
            }
            Db::name('seller_task')->where('id',$seller_task['id'])->update($selltask_change);
            $task_now_id=Db::name('user_task')->insertGetId($task);
            $return_error=Api::api($task_now_id,$task['task_number'],1,'',$task['ending_time'],'');
            if($task_type != 1){
                $end_else_time=time()+7200;
                $return_error=Api::api($task_now_id,$task['task_number'],1,'',$end_else_time,'');

            }
            if($task_type==3){
                $memo = "您有编号为{$task['task_number']}的定时任务等待您继续完成！";
                $time_time = $seller_task['timing_time'];
                Api::api($task_now_id,$task['task_number'],0,$user['mobile'],$time_time,$memo);
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('任务领取成功','Task/maketask');

    }
}