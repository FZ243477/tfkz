<?php


namespace app\seller\controller;


use think\Db;
use think\Exception;
use think\Request;

class Release extends Base
{
    public function release(Request $request){
        return $this->success('发布成功');
        $data = $request->param();
        if(!isset($data['id']))return $this->error('参数错误');
        $id = $data['id'];
        if(!$id)return $this->error('参数错误');
        try{
            Db::startTrans();
            $filed="seller_id,shop_id,task_type,goods_id,terminal,goods_unit_price,goods_num,plan_name,tao_word,qr_code,
            channel_name,memo,is_free_shiping,postage,margin,weight,add_reward,	is_timing_pay,timing_time,timing_pay,
            is_timing_publish,publish_time,timing_publish_pay,union_interval,union_interval_time,receipt_time,is_cycle_time,
            cycle_time,cycle,is_praise,praise_fee,is_img_praise,img_praise_fee,is_video_praise,video_praise_fee,goods_price,
            goods_money,num,incomplete_num,complete_num,postage_money,deposit,silver_ingot,service_price,goods_more_fee,
            refund_service_price,phone_fee,pc_fee,next_day,next_day_fee,user_divided,address,shop_name";
            $list = Db::name('seller_task')->where(['id'=>$id])->field($filed)->find();
            $list['task_number'] = time().rand(100000,999999);
            $list['create_time'] = time();
            $filed_praise = "goods_id,type,content";
            $list_praise = Db::name('seller_task_praise')->where(['seller_task_id'=>$id])->field($filed_praise)->select();
            $list_praise = $list_praise ? $list_praise->toArray() : [];
            $task_id = Db::name('seller_task')->insertGetId($list);
            foreach ($list_praise as $item){
                $item['create_time'] = time();
                $item['seller_task_id'] = $task_id;
                Db::name('seller_task_praise')->insert($item);
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('发布成功！',url('task/gotopay',['id'=>$task_id]));
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
        $id = $data['id'];
        Db::name('seller_task')->where(['id'=>$id,'status'=>1])->delete();
        return $this->success();

    }
}