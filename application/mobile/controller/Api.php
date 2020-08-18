<?php


namespace app\buy\controller;


use app\common\model\Sms;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;

class Api extends Controller
{
    public function api(Request $request)
    {
        $data = $request->param();
        if(!isset($data['password']))return json_encode(['code'=>0,'msg'=>'密码错误','data'=>'']);
        if($data['password'] != md5('tfapi'))return json_encode(['code'=>0,'msg'=>'密码不正确','data'=>'']);
        unset($data['password']);
        if(!$data)return json_encode(['code'=>1,'msg'=>'执行成功','data'=>[]]);
        $ids = [];
        $idss = [];
        foreach ($data as $item){
            $list = Db::name('user_task')->where(['id'=>$item])->field('id,user_id,task_number,task_type,task_step,seller_task_id,state,ending_time,create_time,ids')->find();
            if($list){
                if($list && $list['state']==0){
                    if($list['task_type']==1 && ($list['create_time']+7200) > time())continue;
                    if($list['task_type']==2){
                        if(($list['create_time']+7200) > time())continue;
                        $times = strtotime(date('Y-m-d',$list['create_time']))+24*3600 + 16*3600 + 2400;
                        if($times > time() && $list['task_step'] > 1)continue;
                    }
                    if($list['task_type']==3){
                        if(($list['create_time']+7200) > time())continue;
                        if($list['ending_time'] > time() && $list['task_step'] > 1)continue;
                    }
                    try{
                        Db::startTrans();
                        Db::name('user_task')->where(['id'=>$list['id']])->update(['state'=>2,'cancel_time'=>time(),'deltask_type'=>1]);
                        Db::name('seller_task')->where(['id'=>$list['seller_task_id']])->setInc('incomplete_num',1);
                        if(!finance($list['user_id'],2,-1,2,13,"任务{$list['task_number']}超时取消,扣除冻结1银锭"))throw new Exception('财务写入失败！');
                        $ids[] = $list['id'];
                        $praise_ids = json_decode($list['ids']);
                        if($list['ids'] && $praise_ids){
                            Db::name('seller_task_praise')->where(['id'=>['in',$praise_ids]])->update(['state'=>0]);
                        }
                        Db::commit();
                    }catch (Exception $e){
                        Db::rollback();
                    }
                }else{
                    $ids[] = $list['id'];
                }
            }else{
                $idss[] = $item;
            }
        }
        return json_encode(['code'=>1,'msg'=>'写入成功！','data'=>['ids'=>$ids,'delid'=>$idss]]);
    }

    /**
     * @notes 隔天任务继续完成提醒
     * @date 2019/10/28
     * @time 17:26
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendSms()
    {
        $where['task_type'] = 2;
        $s_time = strtotime(date('Y-m-d')) - 24*3600;
        $end_time = strtotime(date('Y-m-d'));
        $where['create_time'] = ['between',"$s_time,$end_time"];
        $where['state'] = 0;
        $list = Db::name('user_task')->where($where)->field('id,user_id,task_number,task_type,task_step,seller_task_id,state,ending_time,create_time,ids')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as $item){
            $mobile = Db::name('users')->where(['id'=>$item['user_id']])->value('mobile');
            if(!$mobile)continue;
            $check = '/^1[3-9]{1}[0-9]{9}$/';
            if (!preg_match($check, $mobile))continue;
            $memo = "您有编号为{$item['task_number']}的隔天任务等待您继续完成！";
            Sms::send($mobile,$memo);
        }
    }


    public function timer(){
        Db::name('users')->where(['id'=>['gt',0]])->update(['qualified'=>0]);
    }

    public function setTjday(){
        Db::name('users')->where(['tj_award_day'=>['gt',0]])->update(['tj_award_day'=>0]);
        Db::name('seller')->where(['tj_award_day'=>['gt',0]])->update(['tj_award_day'=>0]);
    }
}