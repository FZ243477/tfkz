<?php


namespace app\seller\controller;


use app\common\controller\Img;
use app\seller\model\ReviewTask;
use think\Db;
use think\Exception;
use think\Request;

class Review extends Base
{
    /**
     * @notes 追评页面
     * @date 2020/1/11
     * @time 17:30
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request){
        $data = $request->param();
        //$data['id'] = 21329;
        $this->assign('id',$data['id']);
        return view();
    }

    /**
     * @notes 追评获取数据
     * @date 2020/1/7
     * @time 14:23
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getData(Request $request){
        $data = $request->param();
        if(!isset($data['id']))return $this->error('参数错误！');
        $id = $data['id'];
        $list = Db::name('user_task')->where(['id'=>$id,'is_shengji'=>2,'state'=>1])->field('id,task_number,seller_task_id,shop_id,user_buyno_wangwang,user_principal,table_order_id,is_zp')->find();
        if(!$list)return $this->error('未找到数据或数据状态不正确！');
        if($list['is_zp']!=0)return $this->error('已发布追评不能重复发布！');
        $list['shop_name'] = Db::name('shop')->where(['id'=>$list['shop_id']])->value('shop_name');
        $goods = Db::name('task_goods')->where(['task_id'=>$list['seller_task_id']])->select()->toArray();
        foreach ($goods as $key=>$good){
            $goods[$key]['is_praise'] = false;
            $goods[$key]['is_img_praise'] = false;
            $goods[$key]['is_video_praise'] = false;
            $goods[$key]['praise'] = '';
            $goods[$key]['img'] = [];
            $goods[$key]['video'] = '';
        }
        $list['goods'] = $goods;
        return $this->success('success','',$list);
    }

    /**
     * @notes 上传视频！
     * @date 2020/1/7
     * @time 14:23
     * @param Request $request
     * @return \think\response\Json
     */
    public function uploadVideo(Request $request){
        // 获取表单上传文件 例如上传了001.jpg
        $key = $request->param('key');
        $file = $request->file('file');
        $path = 'uploads' . DS . 'video' . DS;
        $res = aliyunOss::uploadVideo($file,$path);
        if($res['code']==1){
            return json(['code'=>1,'data'=>$res['data'],'key'=>$key]);
        }else{
            // 上传失败获取错误信息
            return json(['code'=>0,'msg'=>$res['msg']]);
        }
    }

    /**
     * @notes 添加追评数据
     * @date 2020/1/11
     * @time 17:29
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addTask(Request $request){
        $data = $request->param();
        $list = Db::name('user_task')->where(['id'=>$data['id']])->find();
        if(!$list)return $this->error('未找到数据！');
        if($list['is_shengji']==1 || $list['state']!=1 || $list['is_zp'] !=0)return $this->error('订单状态不正确！');
        $review = Db::name('review_task')->where(['user_task_id'=>$list['id'],'state'=>['in',[0,1,2,3,4,7]]])->find();
        if($review)return $this->error('已存在追评订单！不能操作！');
        $add_task['seller_id'] = $list['seller_id'];
        $add_task['user_id'] = $list['user_id'];
        $add_task['buy_id'] = $list['user_buyno_id'];
        $add_task['shop_id'] = $list['shop_id'];
        $add_task['taobao_number'] = $list['table_order_id'];
        $add_task['pay_price'] = $list['seller_principal'];
        $add_task['task_number'] = 'ZP'.time().rand(10000,999999);
        $add_task['user_task_id'] = $list['id'];
        $add_task['task_id'] = $list['seller_task_id'];
        $add_task['create_time'] = time();
        try{
            Db::startTrans();
            $id = Db::name('review_task')->insertGetId($add_task);
            $price = 0;
            foreach ($data['goods'] as $goods){
                if($goods['is_praise']=='true'){
                    if(!isset($goods['praise']) || $goods['praise']=='')throw new Exception('好评文字不能为空！');
                    $add_praise['task_id'] = $id;
                    $add_praise['goods_id'] = $goods['goods_id'];
                    $add_praise['type'] = 1;
                    $add_praise['content'] = $goods['praise'];
                    $add_praise['create_time'] = time();
                    Db::name('review_task_praise')->insert($add_praise);
                    $price += 2;
                }
                if($goods['is_img_praise']=='true'){
                    if(!isset($goods['img']) || !$goods['img'])throw new Exception('好评图片不能为空！');
                    $add_praise['task_id'] = $id;
                    $add_praise['goods_id'] = $goods['goods_id'];
                    $add_praise['type'] = 2;
                    $add_praise['content'] = implode(',',$goods['img']);
                    $add_praise['create_time'] = time();
                    Db::name('review_task_praise')->insert($add_praise);
                    $price += 3;
                }
                if($goods['is_video_praise']=='true'){
                    if(!isset($goods['video']) || !$goods['video'])throw new Exception('好评视频不能为空！');
                    $add_praise['task_id'] = $id;
                    $add_praise['goods_id'] = $goods['goods_id'];
                    $add_praise['type'] = 3;
                    $add_praise['content'] = $goods['video'];
                    $add_praise['create_time'] = time();
                    Db::name('review_task_praise')->insert($add_praise);
                    $price += 10;
                }
            }
            Db::name('review_task')->where(['id'=>$id])->update(['money'=>$price,'user_money'=>($price*0.5)]);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('success',url('review/pay',['id'=>$id]));
    }

    /**
     * @notes 支付页面
     * @date 2020/1/11
     * @time 17:28
     * @param Request $request
     * @return \think\response\View
     */
    public function pay(Request $request){
        $data = $request->param();
        $id= $data['id'];
        $this->assign('id',$id);
        $this->assign('seller',$this->seller);
        return view();
    }

    /**
     * @notes 支付页面数据
     * @date 2020/1/11
     * @time 17:28
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payData(Request $request){
        $data = $request->param();
        $id= $data['id'];
        $list = Db::name('review_task')->where(['id'=>$id])->find();
        if(!$list)return $this->error('未找到数据！',url('index/index'));
        $list['praise'] = Db::name('review_task_praise')->where(['task_id'=>$list['id'],'type'=>1])->count('id');
        $list['img'] = Db::name('review_task_praise')->where(['task_id'=>$list['id'],'type'=>2])->count('id');
        $list['video'] = Db::name('review_task_praise')->where(['task_id'=>$list['id'],'type'=>3])->count('id');
        return $this->success('success','',$list);
    }

    /**
     * @notes 支付提交
     * @date 2020/1/11
     * @time 17:27
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payDo(Request $request){
        $data = $request->param();
        $id= $data['id'];
        $list = Db::name('review_task')->where(['id'=>$id])->find();
        if($list['money'] <= 0)return $this->error('未选择追评方式！');
        if(!$list)return $this->error('未找到数据！',url('index/index'));
        if($this->seller['vip_time'] < time())return $this->error('您不是会员，请先充值会员');
        if($this->seller['vip'] != 1)return $this->error('您不是会员，请先充值会员');
        if($list['state']!=0)return $this->error('该订单已支付，请勿重复支付');
        $shop = Db::name('shop')->where(['id'=>$list['shop_id']])->value('shop_name');
        try{
            Db::startTrans();
            $prices = 0;
            $price = 0;
            if($data['is_reward']!='true'){
                $price = $list['money'];
                if($price > $this->seller['balance'])throw new Exception('押金余额不足，请充值！！');
                $update['balance'] = $this->seller['balance'] - $price;
            }else{
                if(($this->seller['balance'] + $this->seller['reward']) < $list['money']){
                    throw new Exception('押金余额不足，请充值！');
                }
                if($list['money'] > $this->seller['reward']){
                    $prices = $this->seller['reward'];
                    $price = $list['money'] - $this->seller['reward'];
                    $update['reward'] = 0;
                    $update['balance'] = $this->seller['balance'] - $price;
                }else{
                    $prices = $list['money'];
                    $update['reward'] = $this->seller['reward'] - $list['money'];
                }

            }
            Db::name('seller')->where(['id'=>$this->seller['id']])->update($update);
            $task_update['state'] = 1;
            $task_update['pay_time'] = time();
            $task_update['yjprice'] = $price;
            $task_update['ydprice'] = $prices;
            Db::name('review_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->update($task_update);
            if($prices > 0){
                if(!finance($this->seller['id'],1,-$prices,2,16,"使用银锭发布《{$shop}》店铺追评任务{$list['task_number']}扣除银锭{$prices}银锭"))throw new Exception('银锭财务写入失败');
            }
            if($price > 0){
                if(!finance($this->seller['id'],1,-$price,1,15,"使用押金代付银锭发布《{$shop}》店铺追评任务{$list['task_number']}扣除押金{$price}元"))throw new Exception('押金代付财务写入失败！');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('支付完成',url('review/task'));
    }

    /**
     * @notes 追评任务列表页面
     * @date 2020/1/11
     * @time 17:29
     * @return \think\response\View
     */
    public function task(){
        $this->assign('menu','2-3');
        return view();
    }

    /**
     * @notes 任务列表数据
     * @date 2020/1/9
     * @time 16:56
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTaskData(Request $request){
        $data = $request->param();
        $where = [];
        if($data['shop_id'])$where['shop_id'] = $data['shop_id'];
        if($data['searchTime']){
            $time1 = strtotime($data['searchTime'][0]);
            $time2 = strtotime($data['searchTime'][1]);
            $where['create_time'] = ['between',[$time1,$time2]];
        }
        if($data['search'])$where['task_number'] = $data['search'];
        if($data['type']!=''){
            $where['state'] = $data['type'];
        }
        $first = ($data['page'] -1) * $data['size'];
        if(isset($data['shop_id'])&&$data['shop_id'])$where['shop_id'] = $data['shop_id'];
        $where['seller_id'] = $this->seller['id'];
        $total = Db::name('review_task')->where($where)->count('id');
        $list = ReviewTask::where($where)->order('id desc')->limit($first,$data['size'])->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            $goods= Db::name('task_goods')->where(['task_id'=>$item['task_id']])->field('goods_id,price,num,goods_spec,pc_img,name')->find();
            $item['goods'] = $goods;
        }
        return $this->success('succress','',['list'=>$list,'total'=>$total]);
    }

    /**
     * @notes  详情页面
     * @date 2020/1/10
     * @time 15:45
     * @param Request $request
     * @return \think\response\View
     */
    public function view(Request $request){
        $data = $request->param();
        //$data['id'] = 6;
        $id= $data['id'];
        $this->assign('id',$id);
        $this->assign('seller',$this->seller);
        return view();
    }

    /**
     * @notes 详情页面数据
     * @date 2020/1/10
     * @time 15:45
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function viewData(Request $request){
        $data = $request->param();
        $id= $data['id'];
        $where['id'] = $id;
        $where['seller_id'] = $this->seller['id'];
        $list = ReviewTask::where($where)->order('id desc')->find();
        $goods= Db::name('task_goods')->where(['task_id'=>$list['task_id']])->field('goods_id,price,num,goods_spec,pc_img,name')->select()->toArray();
        foreach ($goods as $key=>$good){
            $praise = Db::name('review_task_praise')->where(['goods_id'=>$good['goods_id'],'task_id'=>$list['id'],'type'=>1])->value('content');
            if($praise){
                $goods[$key]['is_praise'] = 1;
                $goods[$key]['praise'] = $praise;
            }else{
                $goods[$key]['is_praise'] = 0;
                $goods[$key]['praise'] = '';
            }
            $img = Db::name('review_task_praise')->where(['goods_id'=>$good['goods_id'],'task_id'=>$list['id'],'type'=>2])->value('content');
            if($img){
                $goods[$key]['is_img'] = 1;
                $goods[$key]['img'] = explode(',',$img);
            }else{
                $goods[$key]['is_img'] = 0;
                $goods[$key]['img'] = [];
            }
            $video = Db::name('review_task_praise')->where(['goods_id'=>$good['goods_id'],'task_id'=>$list['id'],'type'=>3])->value('content');
            if($video){
                $goods[$key]['is_video'] = 1;
                $goods[$key]['video'] = $video;
            }else{
                $goods[$key]['is_video'] = 0;
                $goods[$key]['video'] = '';
            }

        }
        $list['goods'] = $goods;
        return $this->success('succress','',$list);
    }


    /**
     * @notes 追评任务取消
     * @date 2020/1/11
     * @time 16:52
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function quxiao(Request $request){
        $data = $request->param();
        $id= $data['id'];
        $where['id'] = $id;
        $where['seller_id'] = $this->seller['id'];
        $list = Db::name('review_task')->where($where)->order('id desc')->find();
        if(!$list)return $this->error('未找到数据！');
        if($list['state'] > 2)return $this->error('状态不正确，不允许取消！');
        if($list['state']==0){
            if(!Db::name('review_task')->where(['id'=>$list['id']])->update(['state'=>5,'confirm_time'=>time()]))return $this->error('取消失败！');
            return $this->success('取消成功！');
        }
        $shop = Db::name('shop')->where(['id'=>$list['shop_id']])->value('shop_name');
        if($list['state']==1 || $list['state']==2){
            $update['state'] = 5;
            $update['confirm_time'] = time();
            try{
                Db::startTrans();
                Db::name('review_task')->where(['id'=>$list['id']])->update($update);
                $seller_edit = [];
                if($list['yjprice'] > 0)$seller_edit['balance'] = $this->seller['balance'] + $list['yjprice'];
                if($list['ydprice'] > 0)$seller_edit['reward'] = $this->seller['reward'] + $list['ydprice'];
                Db::name('seller')->where(['id'=>$this->seller['id']])->update($seller_edit);
                if($list['ydprice'] > 0){
                    if(!finance($this->seller['id'],1,$list['ydprice'],2,17,"取消《{$shop}》店铺追评任务{$list['task_number']}退回银锭{$list['ydprice']}银锭"))throw new Exception('银锭退回财务写入失败');
                }
                if($list['yjprice'] > 0){
                    if(!finance($this->seller['id'],1,$list['yjprice'],1,17,"取消《{$shop}》店铺追评任务{$list['task_number']}退回押金{$list['yjprice']}元"))throw new Exception('押金退回财务写入失败！');
                }
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return $this->error($e->getMessage());
            }
            return $this->success('取消成功！');
        }
        return $this->error('网络错误~！');
    }

    /**
     * @notes 商家确认任务完成
     * @date 2020/1/11
     * @time 17:27
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirm(Request $request){
        $data = $request->param();
        $id= $data['id'];
        $where['id'] = $id;
        $where['seller_id'] = $this->seller['id'];
        $list = Db::name('review_task')->where($where)->order('id desc')->find();
        if(!$list)return $this->error('未找到数据！');
        if($list['state'] != 3)return $this->error('状态不正确，不允许操作！');
        $user = Db::name('users')->where(['id'=>$list['user_id']])->find();
        try{
            Db::startTrans();
            $update['state'] = 4;
            $update['confirm_time'] = time();
            Db::name('review_task')->where(['id'=>$list['id']])->update($update);
            $user_edit['reward'] = $user['reward'] + $list['user_money'];
            Db::name('users')->where(['id'=>$list['user_id']])->update($user_edit);
            Db::name('user_task')->where(['id'=>$list['user_task_id']])->update(['is_zp'=>1]);
            if(!finance($user['id'],2,$list['user_money'],2,18,"完成追评任务{$list['task_number']}获得佣金{$list['user_money']}银锭"))throw new Exception('佣金财务写入失败！');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('操作成功！');
    }

}