<?php


namespace app\common\controller;


use think\Controller;
use think\Db;
use think\Exception;
use think\Request;

class api extends Controller
{
    public function index(Request $request)
    {
        $data = $request->param();
        if(!isset($data['password']))return json_encode(['code'=>0,'msg'=>'密码错误','data'=>'']);
        if($data['password'] != md5('tfapi'))return json_encode(['code'=>0,'msg'=>'密码不正确','data'=>'']);
        unset($data['password']);
        if(!$data)return json_encode(['code'=>1,'msg'=>'执行成功','data'=>[]]);
        $ids = [];
        foreach ($data as $item){
            $list = Db::name('user_task')->where(['id'=>$item])->field('id,seller_task_id,state')->find();
            if($list && $list['state']==0){
                try{
                    Db::startTrans();
                    Db::name('user_task')->where(['id'=>$list['id']])->update(['state'=>2,'cancel_time'=>time()]);
                    Db::name('seller_task')->where(['id'=>$list['seller_task_id']])->setInc('incomplete_num',1);
                    $ids[] = $list['id'];
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                }
            }else{
                $ids[] = $list['id'];
            }
        }
        return json_encode(['code'=>1,'msg'=>'写入成功！','data'=>$ids]);
    }
}