<?php


namespace app\seller\controller;


use think\Db;
use think\Exception;
use think\Request;

class Release extends Base
{
    public function release(Request $request){
        $data = $request->param();
        if(!isset($data['id']))return $this->error('参数错误');
        $id = $data['id'];
        if(!$id)return $this->error('参数错误');
        try{
            Db::startTrans();
            $filed="seller_id,shop_id,task_type,terminal,plan_name,tao_word,qr_code,
            channel_img,memo,is_free_shiping,postage,margin,weight,add_reward,is_timing_pay,timing_time,timing_pay,
            is_timing_publish,publish_time,timing_publish_pay,union_interval,union_interval_time,receipt_time,is_cycle_time,
            cycle_time,cycle,is_praise,praise_fee,is_img_praise,img_praise_fee,is_video_praise,video_praise_fee,goods_price,
            goods_money,num,incomplete_num,postage_money,deposit,silver_ingot,service_price,goods_more_fee,
            refund_service_price,phone_fee,pc_fee,next_day,next_day_fee,user_divided,shop_name,is_shengji";
            $list = Db::name('seller_task')->where(['id'=>$id])->field($filed)->find();
            $shop = Db::name('shop')->where(['id'=>$list['shop_id'],'state'=>1])->find();
            if(!$shop)throw new Exception('该任务不能一键发布!');
            if($list['is_shengji'] < 2)throw new Exception('该任务不能一键发布！');
            $list['address'] = $shop['province'].'-'.$shop['city'].'-'.$shop['area'].'-'.$shop['address'].'-'.$shop['mobile'];
            $list['code'] = $shop['code'];
            if($shop['logistics'] == 0){
                $list['is_free_shiping'] = 3;
                $list['postage'] = 0;
                $list['margin'] = 0;
            }
            $list['task_number'] = time().rand(100000,999999);
            $list['create_time'] = time();
            $list['step'] = 1;
            $filed_praise = "goods_id,type,content";
            $list_praise = Db::name('seller_task_praise')->where(['seller_task_id'=>$id])->field($filed_praise)->select();
            $list_praise = $list_praise ? $list_praise->toArray() : [];
            $list_goods = Db::name('task_goods')->where(['task_id'=>$id])->select()->toArray();
            $list_word = Db::name('task_word')->where(['task_id'=>$id])->select()->toArray();
            Db::name('seller_task')->where(['seller_id'=>$this->seller['id'],'status'=>1])->delete();
            $task_id = Db::name('seller_task')->insertGetId($list);
            $rand_num = $task_id . rand(1000,9999);
            Db::name("seller_task")->where(['id'=>$task_id])->update(['rand_num'=>$rand_num]);
            foreach ($list_praise as $item){
                unset($item['id']);
                $item['create_time'] = time();
                $item['seller_task_id'] = $task_id;
                Db::name('seller_task_praise')->insert($item);
            }
            foreach ($list_goods as $item){
                unset($item['id']);
                $item['create_time'] = time();
                $item['task_id'] = $task_id;
                Db::name('task_goods')->insert($item);
            }
            foreach ($list_word as $item){
                unset($item['id']);
                $item['create_time'] = time();
                $item['task_id'] = $task_id;
                $item['ynum'] = $item['num'];
                Db::name('task_word')->insert($item);
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('发布成功！',url('task/taskTow',['id'=>$task_id]));
    }


    /**
     * @notes 取消发布任务
     * @date 2019/10/23
     * @time 16:48
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function cancel(Request $request){
        $data = $request->param();
        if(!isset($data['id']))return $this->success('取消成功！',url('index/index'));
        $id = $data['id'];
        $list = Db::name('seller_task')->where(['id'=>$id])->find();
        if(!$list)return $this->success('取消成功！',url('index/index'));
        if($list['status']!=1)return $this->error('状态不正确！');
        try{
            Db::startTrans();
            Db::name('seller_task')->where(['id'=>$id,'status'=>1])->delete();
            Db::name('task_goods')->where(['task_id'=>$id])->delete();
            Db::name('task_word')->where(['task_id'=>$id])->delete();
            Db::name('seller_task_praise')->where(['seller_task_id'=>$id])->delete();
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('取消成功！',url('index/index'));

    }
}