<?php


namespace app\seller\controller;


use app\common\controller\Img;
use app\seller\model\SellerTask;
use app\seller\model\UserTask;
use app\seller\model\SellerTaskPraise;
use think\Db;
use think\Exception;
use think\Request;

class Task extends Base
{
    public function editor()
    {
        $this->assign('seller',$this->seller);
        $this->assign('system',$this->system);
        $setTips = Db::name('set_tips')->where(['type'=>1])->select();
        $setTips = $setTips ? $setTips->toArray() : [];
        $pics = [];
        foreach ($setTips as $tip){
            $pics[$tip['id']] = $tip['content'];
        }
        $this->assign('pics',$pics);
        return view();
    }

    /**
     * @notes 获取店铺数据
     * @date 2019/8/29
     * @time 16:59
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shop(){
        $shop = Db::name('shop')->where(['state'=>1,'seller_id'=>$this->seller['id']])->field('id,shop_name')->select();
        $shop = $shop ? $shop->toArray() : [];
        return $this->success('','',$shop);
    }

    /**
     * @notes 判断是否有任务为支付
     * @date 2019/10/16
     * @time 9:53
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskJudge(){
        $list = Db::name('seller_task')->where(['status'=>1,'seller_id'=>$this->seller['id']])->find();
        if($list)return $this->error('有未支付订单，请先去支付或取消',url('task/task'));
        return $this->success();
    }

    /**
     * @notes 获取商品数据
     * @date 2019/8/29
     * @time 16:58
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoods(Request $request){
        $data = $request->param();
        $where['a.seller_id'] = $this->seller['id'];
        $where['a.state'] = 1;
        if($data['shop_id'])$where['a.shop_id'] = $data['shop_id'];
        if($data['search'])$where['a.name'] = ['like','%'.$data['search'].'%'];
        if($data['max_price'] && $data['min_price'])$where['a.price'] = ['between',[$data['min_price'],$data['max_price']]];
        if($data['max_price'] && !$data['min_price'])$where['a.price'] = ['lt',$data['max_price']];
        if(!$data['max_price'] && $data['min_price'])$where['a.price'] = ['gt',$data['min_price']];
        $firse = ($data['page'] - 1) * $data['size'];
        $field = "b.shop_name,b.type,a.name,a.id,a.pc_img,a.price,a.show_price,a.num";
        $total = Db::table('tfkz_goods')->alias('a')->join("tfkz_shop b","a.shop_id=b.id")->where($where)->count('a.id');
        $list = Db::table('tfkz_goods')->alias('a')->join("tfkz_shop b","a.shop_id=b.id")->where($where)->order('a.id desc')->field($field)->limit($firse,$data['size'])->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            $img = json_decode($item['pc_img']);
            $item['pc_img'] = $img ? $img[0] : '';
            $arr = [
                1=>'淘宝',
                2=>'天猫',
                3=>'飞猪',
            ];
            $arr2 = [
                1=>'/static/seller/img/taobao.png',
                2=>'/static/seller/img/tianmao.png',
                3=>'/static/seller/img/feizhu.png',
            ];
            $item['pic'] = $arr2[$item['type']];
            $item['type'] = $arr[$item['type']];
        }
        $res = [
            'total' => $total,
            'list' => $list,
        ];
        return $this->success('success','',$res);
    }

    public function goodsKey(Request $request){
        $data = $request->param();
        if(!isset($data['goods_id'][0]))return $this->error('请选择商品！');
        $goods_id = $data['goods_id'][0];
        if(!isset($data['type']))return $this->error('请选择做任务终端');
        $goods_key_id = Db::name('goods')->where(['id'=>$goods_id])->value('goods_key_id');
        $list = Db::name('goods_key_world')->where(['goods_key_id'=>$goods_key_id])->column('key_world');
        if(!$list)return $this->error("主商品没有关键词");
        return $this->success('','',implode('/',$list));

    }

    public function taskAdd(Request $request){
        $data = $request->param();
        //判断有没有未支付订单
        $list = Db::name('seller_task')->where(['status'=>1,'seller_id'=>$this->seller['id']])->find();
        if($list){//删除未支付订单
            Db::name('seller_task')->delete($list['id']);
            Db::name('seller_task_praise')->where(['seller_task_id'=>$list['id']])->delete();
        }
        $result = $this->validate($data,'Task.add');
        if(true !== $result){
            return $this->error($result);
        }
        if (!$data['shop_id']) return $this->error('请选择店铺!');
        if (!$data['task_type']) return $this->error('请选择任务类型!');
        if (!$data['shop_id']) return $this->error('请选择商品!');
        if (!$data['terminal']) return $this->error('请选择终端类型!');
        if ($data['add_reward'] > 20 || $data['add_reward']==1) return $this->error('加赏佣金金额不正确!');
        if ($data['task_type']==2 && $data['tao_word']=='') return $this->error('淘口令不能为空!');
        if ($data['task_type']==3 && $data['qr_code']=='') return $this->error('二维码不能为空!');
        if ($data['task_type']==5 && $data['channel_name']=='') return $this->error('通道名称不能为空!');
        if(!$data['num'])return $this->error('请填写单数');
        if($data['is_cycle_time']=='true' && !$data['cycle_time'])return $this->error('请选择延长时间');
        if($data['is_timing_pay']=='true' && !$data['timing_time'])return $this->error('请选择定时付款时间');
        if($data['is_timing_pay']=='true' && $data['text_day']=='true')return $this->error('定时付款和隔天任务只能选择其中一种！');
        if($data['is_timing_pay']=='true' && (strtotime($data['timing_time'])-7200 < time()))return $this->error('定时付款必须定在2小时以后！');
        if($data['is_timing_publish']=='true' && (strtotime($data['publish_time'])-30 < time()))return $this->error('定时发布时间只能选择在5分钟之后');
        if($data['is_praise']=='true' && !$data['praise'])return $this->error('请填写文字好评内容！');
        if($data['is_img_praise']=='true' && !$data['img'])return $this->error('请上传好评图片！');
        if($data['is_img_praise']=='true' && ($data['num'] > 5 || count($data['goods_id']) > 1))return $this->error('图片好评任务只能发布单商品任务并且不能超过5单');
        if($data['is_video_praise']=='true' && !$data['video'])return $this->error('请上传好评视频！');
        if($data['is_video_praise']=='true' && ($data['num'] > 1 || count($data['goods_id']) > 1))return $this->error('视频好评仅限单连接任务');
        if(!$data['is_free_shiping'])return $this->error('请选择是否包邮！');
        $shop = Db::name('shop')->where(['id'=>$data['shop_id'],'seller_id'=>$this->seller['id']])->field('shop_name,mobile,province,city,area,address,logistics')->find();
        if(!$shop)return $this->error('所选择的店铺不存在！');
        $data['address'] = $shop['province'].'-'.$shop['city'].'-'.$shop['area'].'-'.$shop['address'].'-'.$shop['mobile'];
        $data['shop_name'] = $shop['shop_name'];
        if($shop['logistics']==0){
            $data['is_free_shiping'] = 3;
            $data['postage'] = 0;
            $data['margin'] = 0;
        }else{
            $data['postage'] = $this->system['postage']; //运费保证金
            if($data['is_free_shiping'] == 1){
                $data['margin'] = 0; //商家保证金
            }else{
                $data['margin'] = 10; //商家保证金
            }

        }
        $listData = $this->handleData($data);
        $add = $listData['add'];
        $price = round($listData['price'],2);
        if($data['qr_code']){
            $res = Img::base64_image_content($data['qr_code'],"./uploads/task/");
            if($res['code']==1){
                $add['qr_code'] = $res['data'];
            }else{
                return $this->error('二维码'.$res['data']);
            }
        }
        Db::startTrans();
        try{
            $id = Db::name("seller_task")->insertGetId($add);
            $rand_num = $id . rand(1000,9999);
            Db::name("seller_task")->where(['id'=>$id])->update(['rand_num'=>$rand_num]);
            if($data['is_praise']=='true'){
                if($data['num'] != count($data['praise']))throw new Exception('好评文字上传组数和单数不符');
                $this->praiseInsert($data['praise'],$id,$data['goods_id']);
            }
            if($data['is_img_praise']=='true'){
                if($data['num'] != count($data['img']))throw new Exception('好评图片上传组数和单数不符');
                $this->imgInsert($data['img'],$id);
            }
            if($data['is_video_praise']=='true')$this->videoInsert($data['video'],$id,$data['goods_id'][0]);
            Db::name('seller')->where(['id'=>$this->seller['id']])->update(['last_time'=>time()]);
            Db::commit();
       }catch (Exception $e){
           Db::rollback();
           return $this->error($e->getMessage());
       }
        return $this->success('任务发布成功！','',['id'=>$id,'yinding'=>$price,'fee'=>round($listData['fee'],2),'is_free_shiping'=>$data['is_free_shiping']]);
    }

    /**
     * @notes 整理任务数据
     * @date 2019/10/13
     * @time 11:40
     * @param $data 提价数据
     * @return array 整理后数据
     */
    public function handleData($data){
        $divide_price = 0;
        $goods_price = 0;
        $add['seller_id'] = $this->seller['id'];
        $add['shop_id'] = $data['shop_id'];
        $add['address'] = $data['address'];
        $add['shop_name'] = $data['shop_name'];
        $add['is_free_shiping'] = $data['is_free_shiping'];
        $add['postage'] = $data['postage'];
        $add['weight'] = $data['weight'];
        $add['task_number'] = time().rand(100000,999999);
        $add['task_type'] = $data['task_type'];
        $add['terminal'] = $data['terminal'];
        $add['tao_word'] = $data['tao_word'];
        $add['qr_code'] = $data['qr_code'];
        $add['channel_name'] = $data['channel_name'];
        $add['memo'] = $data['memo'];
        $add['num'] = $data['num'];
        $add['incomplete_num'] = $data['num'];
        $add['create_time'] = time();
        $add['goods_unit_price'] = json_encode($data['goods_price']);
        $add['goods_num'] = json_encode($data['goods_num']);
        $add['goods_id'] = json_encode($data['goods_id']);
        $goods_specs = '';
        foreach ($data['goods_id'] as $item){
            $list_spec = Db::name('goods')->where(['id'=>['in',$item]])->field('spec_name,spec_value')->find();
            if($goods_specs==''){
                $goods_specs = $list_spec['spec_name'].'/'.$list_spec['spec_value'];
            }else{
                $goods_specs .= '{}'.$list_spec['spec_name'].'/'.$list_spec['spec_value'];
            }
        }
        /*$goods_spec = Db::name('goods')->where(['id'=>['in',$data['goods_id']]])->field('spec_name,spec_value')->select()->toArray();
        foreach ($goods_spec as $spec){
            if($goods_specs==''){
                $goods_specs = $spec['spec_name'].'/'.$spec['spec_value'];
            }else{
                $goods_specs .= '{}'.$spec['spec_name'].'/'.$spec['spec_value'];
            }
        }*/
        $add['goods_spec'] = $goods_specs;
        /*商品价格计算*/
        foreach ($data['goods_price'] as $key=>$datum){
            $goods_price += $datum * $data['goods_num'][$key];
        }
        $add['goods_price'] = $goods_price;
        $add['goods_money'] = $goods_price * $data['num'];
        $add['goods_more_fee'] = $this->system['goods_more_fee'] * (count($data['goods_id'])-1); //多商品费用
        /*套餐服务费计算*/
        $add['service_price'] = $this->service($goods_price); //套餐服务费
        /*返款服务费用计算*/
        $add['refund_service_price'] = round($this->system['refund_service_price'] * $goods_price,2);
        /*需要分成的银锭统计*/
        $divide_price += $add['goods_more_fee'] * $data['num'];//多商品服务费
        if($add['task_type']==4 || $add['task_type']==5){ //直通车或通道任务服务费
            $divide_price += 2 * $data['num'];
        }

        if($data['terminal']==2){
            $add['phone_fee'] = $this->system['phone_fee']; //手机端加成服务费用
            $divide_price += $add['phone_fee'] * $data['num'];
        }else{
            $add['pc_fee'] = $this->system['pc_fee']; //pc端加成服务费用
            $divide_price += $add['pc_fee'] * $data['num'];
        }
        $add['is_praise'] = $data['is_praise']=='true' ? 1 : 0; //文字好评
        $divide_price +=  $data['is_praise']=='true' ? ($this->system['praise'] * $data['num']) : 0;
        $add['praise_fee'] = $data['is_praise']=='true' ? $this->system['praise'] : 0; //文字好评费用
        $add['is_img_praise'] = $data['is_img_praise']=='true' ? 1 : 0; //图片好评
        $divide_price +=  $data['is_img_praise']=='true' ? ($this->system['img_praise'] * $data['num']) : 0;
        $add['img_praise_fee'] = $data['is_img_praise']=='true' ? $this->system['img_praise'] : 0; //图片好评费用
        $add['is_video_praise'] = $data['is_video_praise']=='true' ? 1 : 0;// 视频好评
        $divide_price +=  $data['is_video_praise']=='true' ? ($this->system['video_praise'] * $data['num']) : 0;
        $add['video_praise_fee'] = $data['is_video_praise']=='true' ? $this->system['video_praise'] : 0; //视频好评费用
        $add['add_reward'] = $data['add_reward'];//加赏任务佣金
        $divide_price += $data['add_reward'] * $data['num'];
        //是否延长买号购物周期
        if($data['is_cycle_time']=='true'){
            $add['is_cycle_time'] =1;
            $add['cycle_time'] = $data['cycle_time'];
            $add['cycle'] = $data['cycle_time']/30;
            $divide_price += ($data['cycle_time']/30) * $data['num'];  //延长购物周期服务费
        }else{
            $add['is_cycle_time'] = 0;
            $add['cycle_time'] = 0;
            $add['cycle'] = 0;
        }

        /*定时付款计算*/
        if($data['is_timing_pay']=='true'){
            $add['is_timing_pay'] = 1;
            $divide_price += $this->system['timing_pay']  * $data['num'];
            $add['timing_pay'] = $this->system['timing_pay'];
        }else{
            $add['is_timing_pay'] = 0;
            $add['timing_pay'] = 0;
        }
        $add['timing_time'] = $data['is_timing_pay']=='true' ? strtotime($data['timing_time']) : ''; //定时付款时间
        /*隔天任务计算*/
        if($data['next_day']=='true'){
            $add['next_day'] = 1;
            $divide_price += $this->system['next_day'] * $data['num'];
            $add['next_day_fee'] = $this->system['next_day'];
        }
        $add['user_divided'] = round($divide_price * $this->system['divided'],2); /*买手任务佣金分成*/
        /*银锭统计*/
        $price = $divide_price;
        /*定时发布统计*/
        if($data['is_timing_publish']=='true'){
            $add['is_timing_publish'] = 1;
            $price += $this->system['timing_publish'];
            $add['timing_publish_pay'] = $this->system['timing_publish'];
        }else{
            $add['is_timing_publish'] = 0;
            $add['timing_publish_pay'] = 0;
        }
        $add['publish_time'] = $data['is_timing_publish']=='true' ? strtotime($data['publish_time']) : time();//发布时间
        /*接单间隔时间(分钟)*/
        $add['union_interval_time'] = $data['union_interval_time'];
        $add['union_interval'] = $data['union_interval_time']>0 ? $this->system['union_interval'] : 0;
        $price += $add['refund_service_price'] * $data['num'];//返款服务费
        $price += $add['union_interval'];
        $price += $add['service_price'] * $data['num'];//套餐服务费
        $add['postage_money'] = $data['postage'] * $data['num']; //运费总计
        $add['margin'] = $data['margin']; //商家保证金
        $add['deposit'] = $add['postage_money'] + $add['goods_money'] + ($data['margin'] * $data['num']); //押金总计
        $add['silver_ingot'] = $price; //银锭总计

        return [
            'add'=>$add,
            'price'=>$price,
            'fee'=>$add['service_price'],
        ];
    }

    /**
     * @notes 文字好评储存
     * @date 2019/10/13
     * @time 10:13
     * @param $praise 好评内容
     * @param $id  任务id
     * @param $goods_ids  商品id数组
     */

    public function praiseInsert($praise,$id,$goods_ids){
        foreach ($praise as $item){
            foreach ($item as $k=>$val){
                if($val){
                    if(!$val['str'])throw new Exception('好评文字不能为空！');
                    $create['content'] = $val['str'] ? trim($val['str']) : '好评为空';
                    $create['goods_id'] = $goods_ids[$k];
                    $create['seller_task_id'] = $id;
                    $create['type'] = 1;
                    $create['create_time'] = time();
                    Db::name('seller_task_praise')->insert($create);
                }
            }
        }
    }

    /**
     * @notes 图片好评上传
     * @date 2019/10/13
     * @time 10:12
     * @param $imgs 图片
     * @param $id  任务id
     * @throws Exception
     */
    public function imgInsert($imgs,$id){
        foreach ($imgs as $item){
            $img = [];
            foreach ($item as $val){
                $res = Img::base64_image_content($val,"./uploads/task/");
                if($res['code']==0)throw new Exception('好评'.$res['data']);
                $img[] = $res['data'];
            }
            $create['content'] = json_encode($img);
            $create['seller_task_id'] = $id;
            $create['type'] = 2;
            $create['create_time'] = time();
            Db::name('seller_task_praise')->insert($create);
        }
    }

    /**
     * @notes 商品好评视频上传
     * @date 2019/10/13
     * @time 10:11
     * @param $video  视频编码
     * @param $id 任务id
     * @param $goods_id  商品id
     * @throws Exception
     */
    public function videoInsert($video,$id,$goods_id){
        //$res = Img::base64_image_video($video,"./uploads/task/");
        //if($res['code']==0)throw new Exception('视频'.$res['data']);
        //$create['content'] = $res['data'];
        if(!$video)throw new Exception('请上传视频!');
        $create['content'] = $video;
        $create['goods_id'] = $goods_id;
        $create['seller_task_id'] = $id;
        $create['type'] = 3;
        $create['create_time'] = time();
        Db::name('seller_task_praise')->insert($create);
    }


    /**
     * @notes 获取佣金比例
     * @date 2019/10/19
     * @time 11:58
     * @param $price
     * @param int $type
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service($price,$type=1){
        $res = 0;
        $list = Db::name('commission')->where(['max_goods_price'=>['egt',$price]])->order('max_goods_price asc')->find();
        if($list){
            if($type==1)$res = $list['seller_reward'];
            if($type==2)$res = $list['user_reward'];
        }
        return $res;
    }

    /**
     * @notes  发布任务支付
     * @date 2019/9/11
     * @time 14:20
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pay(Request $request){
        $data = $request->param();
        $list = Db::name('seller_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->find();
        if(!$list)return $this->error('数据不存在，请重新发布！');
        if($this->seller['vip_time'] < time())return $this->error('您不是会员，请先充值会员');
        if($this->seller['vip'] != 1)return $this->error('您不是会员，请先充值会员');
        if($list['status']!=1)return $this->error('该订单已支付，请勿重复支付');
        $shop = Db::name('shop')->where(['id'=>$list['shop_id']])->value('shop_name');
        try{
            Db::startTrans();
            $prices = 0;
            if($data['is_reward']!='true'){
                $price = $list['deposit'] + $list['silver_ingot'];
                $price1 = $list['deposit'];
                $price2 = $list['silver_ingot'];
                if($price > $this->seller['balance'])throw new Exception('押金余额不足，请充值！！');
            }else{
                $price1 = $list['deposit'];
                $price2 = 0;
                if(($this->seller['balance'] + $this->seller['reward']) < ($list['deposit']+$list['silver_ingot'])){
                    throw new Exception('押金余额不足，请充值！');
                }
                if($list['deposit'] > $this->seller['balance'])throw new Exception('押金余额不足，请充值');
                if($list['silver_ingot'] > $this->seller['reward']){
                    $prices = $this->seller['reward'];
                    $price2 += $list['silver_ingot'] - $this->seller['reward'];
                    $update['reward'] = 0;
                }else{
                    $prices = $list['silver_ingot'];
                    $update['reward'] = $this->seller['reward'] - $list['silver_ingot'];
                }

            }
            $update['balance'] = $this->seller['balance'] - $price1;
            Db::name('seller')->where(['id'=>$this->seller['id']])->update($update);
            $task_update['status'] = 2;
            $task_update['pay_state'] = 1;
            $task_update['pay_time'] = time();
            $task_update['yajin'] = $price1+$price2;
            $task_update['yinding'] = $prices;
            Db::name('seller_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->update($task_update);
            if($prices > 0){
                if(!finance($this->seller['id'],1,-$prices,2,5,"使用银锭发布《{$shop}》店铺任务{$list['task_number']}扣除银锭{$prices}银锭"))throw new Exception('银锭财务写入失败');
            }
            if(!finance($this->seller['id'],1,-$price1,1,5,"使用押金发布《{$shop}》店铺任务{$list['task_number']}扣除押金{$price1}元"))throw new Exception('押金财务写入失败！');
            if($price2 > 0){
                Db::name('seller')->where(['id'=>$this->seller['id']])->setDec('balance',$price2);
                if(!finance($this->seller['id'],1,-$price2,1,15,"使用押金代付银锭发布《{$shop}》店铺任务{$list['task_number']}扣除押金{$price2}元"))throw new Exception('押金代付财务写入失败！');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('支付完成',url('task/task'));
    }

    /**
     * @notes 任务列表
     * @date 2019/9/9
     * @time 14:29
     * @return \think\response\View
     */
    public function task(){
        $this->assign('menu','2-1');
        return view();
    }

    /**
     * @notes 待处理
     * @date 2019/9/9
     * @time 14:30
     * @return \think\response\View
     */
    public function pending(){
        $this->assign('menu','2-2');
        return view();
    }

    /**
     * @notes 获取任务数据
     * @date 2019/9/16
     * @time 15:00
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getData(Request $request){
        $data = $request->param();
        if($data['terminal'])$where['terminal'] = $data['terminal'];
        if($data['shop_id'])$where['shop_id'] = $data['shop_id'];
        if($data['searchTime']){
            $time1 = strtotime($data['searchTime'][0]);
            $time2 = strtotime($data['searchTime'][1]);
            $where['publish_time'] = ['between',[$time1,$time2]];
        }
        if($data['search'])$where['task_number'] = $data['search'];
        if($data['order_number']){
            $ids = Db::name('user_task')->where(['table_order_id'=>$data['order_number']])->column('seller_task_id');
            if(!$ids)$ids = [];
            $where['id'] = ['in',$ids];
        }
        if($data['is_day']){
            $day = strtotime(date('Y-m-d'));
            $where['create_time'] = ['gt',$day];
        }
        if($data['status']){
            $where['status'] = $data['status'];
        }
        if($data['task_type']){
            $where['task_type'] = $data['task_type'];
        }
        $first = ($data['page'] -1) * $data['size'];
        if($data['shop_id'])$where['shop_id'] = $data['shop_id'];
        $where['seller_id'] = $this->seller['id'];
        $total = Db::name('seller_task')->where($where)->count('id');
        $list = SellerTask::where($where)->order('id desc')->limit($first,$data['size'])->select();
        $list = $list ? $list->toArray() : [];
        $arr = [
            1=>'待支付',
            2=>'待审核',
            3=>'已通过',
            4=>'已拒绝',
            5=>'已撤销',
            6=>'已完成'
        ];
        foreach ($list as &$item){
            $item['nums'] = 0;
            if(in_array($item['status'],[1,2])){
                $item['nums'] = 1;
            }
            if($item['status']==3 && $item['num'] == $item['incomplete_num']){
                $item['nums'] = 1;
            }
            $item['status_type'] = $arr[$item['status']];
            if($item['status']==5 && $item['pay_state']==0)$item['status_type'] = '已取消';
            if($item['status']==3 && $item['is_timing_pay']==1){
                if($item['timing_time'] < time())$item['status_type'] = '已超时';
            }
            $item['jxz'] = Db::name('user_task')->where(['seller_task_id'=>$item['id'],'state'=>0])->count('id');
            $item['dfh'] = Db::name('user_task')->where(['seller_task_id'=>$item['id'],'state'=>3])->count('id');
            $item['dsh'] = Db::name('user_task')->where(['seller_task_id'=>$item['id'],'state'=>4])->count('id');
            $item['dfk'] = Db::name('user_task')->where(['seller_task_id'=>$item['id'],'state'=>5])->count('id');
            $item['ywc'] = Db::name('user_task')->where(['seller_task_id'=>$item['id'],'state'=>1])->count('id');
        }
        return $this->success('succress','',['list'=>$list,'total'=>$total]);
    }


    /**
     * @notes 待处理任务获取数据
     * @date 2019/9/24
     * @time 14:36
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserTask(Request $request){
        $data = $request->param();
        $firse = ($data['page'] -1) * $data['size'];
        if($data['type'] != ''){
            if($data['type']==11 || $data['type']==3){
                $where['state'] = 3;
                if($data['type']==11)$where['delivery_state']=1;
                if($data['type']==3)$where['delivery_state']=0;
            }else{
                $where['state'] = $data['type'];
            }

        }else{
            $where['state'] = ['in',[0,1,3,4,5]];
        }
        if($data['task_number'])$where['task_number'] = ['like',$data['task_number'].'%'];
        if($data['shop_id'])$where['shop_id'] = $data['shop_id'];
        $where['seller_id'] = $this->seller['id'];
        $total = Db::name('user_task')->where($where)->count('id');
        $list = UserTask::where($where)->order('id desc')->limit($firse,$data['size'])->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            $item['checked'] = false;
            $item['shop'] = Db::name('shop')->where(['id'=>$item['shop_id']])->value('shop_name');
        }
        $res = [
            'list'=>$list,
            'total'=>$total
        ];
        return $this->success('success','',$res);
    }

    /**
     * @notes 订单导出
     * @date 2019/10/16
     * @time 10:08
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excel(Request $request){
        $data = $request->param();
        if(isset($data['type']) && $data['type'] != ''){
            if($data['type']==11 || $data['type']==3){
                $where['state'] = 3;
                if($data['type']==11)$where['delivery_state']=1;
                if($data['type']==3)$where['delivery_state']=0;
            }else{
                $where['state'] = $data['type'];
            }

        }else{
            $where['state'] = ['in',[0,1,3,4,5]];
        }
        $where['seller_id'] = $this->seller['id'];
        $list = Db::name('user_task')->where($where)->field('seller_task_id,task_number,user_buyno_wangwang,table_order_id,principal,user_principal,delivery,delivery_num,upload_order_time')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            $shop_id = Db::name('seller_task')->where(['id'=>$item['seller_task_id']])->value('shop_id');
            $item['seller_task_id'] = Db::name('shop')->where(['id'=>$shop_id])->value('shop_name');
            $item['upload_order_time'] = $item['upload_order_time'] ? date('Y-m-d H:i:s',$item['upload_order_time']) : '';
        }
        $title = ['店铺名','任务编号','旺旺号','淘宝订单号','任务金额','付款金额','快递类型','快递单号','支付时间'];
        Phpexcel::exportExcel($title,$list,'发货任务导出表');
    }

    /**
     * @notes 详情页面
     * @date 2019/9/21
     * @time 14:04
     * @param Request $request
     * @return \think\response\View|void
     */
    public function status(Request $request){
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('参数错误！');
        $this->assign('id',$data['id']);
        $this->assign('system',$this->system);
        return view();
    }

    /**
     * @notes 去支付页面
     * @date 2019/9/21
     * @time 14:05
     * @param Request $request
     * @return \think\response\View|void
     */
    public function gotopay(Request $request){
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('参数错误！');
        $this->assign('id',$data['id']);
        $this->assign('system',$this->system);
        $this->assign('seller',$this->seller);
        return view();
    }


    /**
     * @notes 详情页面数据
     * @date 2019/9/21
     * @time 14:32
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTaskData(Request $request){
        set_time_limit(0);
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('参数错误！');
        $list = SellerTask::where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->find();
        $goods_price = json_decode($list['goods_unit_price']);
        $goods_num = json_decode($list['goods_num']);
        $arr = [];
        $type = [
            '主商品',
            '副商品1',
            '副商品2',
        ];
        foreach ($goods_price as $key=>$item){
            $arr[] = [
                'price'=>$item,
                'num'=>$goods_num[$key],
                'type'=>$type[$key]
            ];
        }
        $list['goods'] = $arr;
        if(!$list)return $this->error('未找到数据');
        $key = Db::name('goods_key_world')->where(['goods_key_id'=>$list['goods_id'][0]['goods_key_id']])->column('key_world');
        $list['key'] = $key ? implode('/',$key) : '';
        $field = "id,seller_principal,commission,state,create_time,task_number,principal,user_principal,user_divided,user_buyno_wangwang";
        $userList = UserTask::where(['seller_task_id'=>$list['id']])->field($field)->select();
        $userList = $userList ? $userList->toArray() : [];
        $num = 0;
        $price = 0;
        $commission = 0;
        foreach ($userList as &$item){
            if($item['state'] == '已完成'){
                $num++;
                $price += $item['seller_principal'];
                $commission += $item['commission'];
            }
        }
        $list['is_time'] = 0;
        if($list['status']==3 && $list['is_timing_pay']==1){
            if($list['timing_time'] < time())$list['is_time'] = 1;
        }
        $list['timing_time'] = $list['timing_time'] ? date('Y-m-d H:i:s',$list['timing_time']) : '';
        $list['returnPay'] = $price;
        $list['returnNum'] = $num;
        $list['commission'] = $this->service($list['goods_price'],2) + round($list['user_divided']/$list['num'],2);
        $list['user_commission'] = $commission;
        $list['user'] = $userList;
        return $this->success('success','',$list);

    }

    /**
     * @notes 去支付页面数据
     * @date 2019/9/21
     * @time 14:05
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayData(Request $request){
        set_time_limit(0);
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('参数错误！');
        $list = Db::name('seller_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->find();
        if(!$list)return $this->error('未找到数据',url('task/task'));
        if($list['status']!=1)return $this->error('该订单状态不正确！',url('task/task'));
        $goods_price = json_decode($list['goods_unit_price']);
        $goods_num = json_decode($list['goods_num']);
        $arr = [];
        $type = [
            '主商品',
            '副商品1',
            '副商品2',
        ];
        foreach ($goods_price as $key=>$item){
            $arr[] = [
                'price'=>$item,
                'num'=>$goods_num[$key],
                'type'=>$type[$key]
            ];
        }
        $list['goods'] = $arr;
        return $this->success('success','',$list);
    }

    /**
     * @notes 子订单详情页
     * @date 2019/9/24
     * @time 14:39
     * @param Request $request
     * @return \think\response\View
     */
    public function detail(Request $request){
        $data = $request->param();
        $this->assign('id',$data['id']);
        $this->assign('system',$this->system);
        $this->assign('seller',$this->seller);
        return view();
    }

    /**
     * @notes 子订单详情页面数据
     * @date 2019/9/24
     * @time 14:39
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetail(Request $request){
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('参数错误！');
        $userList = Db::name('user_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->find();
        if(!$userList)return error('未找到数据！');
        $list = Db::name('seller_task')->where(['id'=>$userList['seller_task_id']])->field('id,shop_id,task_number,task_type')->find();
        $userList['shop'] = Db::name('shop')->where(['id'=>$list['shop_id']])->value('shop_name');
        $arr = [
            '1'=>'文字好评',
            '2'=>'淘口令',
            '3'=>'二维码',
            '4'=>'直通车',
            '5'=>'通道任务',
        ];
        $arr2 = [
            0=>'正在进行',
            1=>'已完成',
            2=>'已取消',
            3=>'待发货',
            4=>'待收货',
            5=>'待返款',
            6=>'待买手确认返款'
        ];
        $arr3 = [];
        $arr3[0] = $userList['step_two_complete'] ? date('Y-m-d H:i:s',$userList['step_two_complete']) : date('Y-m-d H:i:s',$userList['create_time']);
        $arr3[1] = $userList['complete_time'] ? date('Y-m-d H:i:s',$userList['complete_time']) : '';
        $arr3[2] = $userList['cancel_time'] ? date('Y-m-d H:i:s',$userList['cancel_time']) : '';
        $arr3[3] = $userList['upload_order_time'] ? date('Y-m-d H:i:s',$userList['upload_order_time']) : '';
        $arr3[4] = $userList['delivery_time'] ? date('Y-m-d H:i:s',$userList['delivery_time']) : '';
        $arr3[5] = $userList['high_praise_time'] ? date('Y-m-d H:i:s',$userList['high_praise_time']) : '';
        $arr3[6] = $userList['platform_refund_time'] ? date('Y-m-d H:i:s',$userList['platform_refund_time']) : '';
        $userList['task_type'] = $arr[$list['task_type']];
        $userList['time'] ='';// $arr3[$userList['state']];
        $userList['state'] = $arr2[$userList['state']];
        $userList['step_two_complete'] = $userList['step_two_complete'] ? date('Y-m-d H:i:s',$userList['step_two_complete']) :'';
        $userList['upload_order_time'] = $userList['upload_order_time'] ? date('Y-m-d H:i:s',$userList['upload_order_time']) :'';
        $userList['delivery_time'] = $userList['delivery_time'] ? date('Y-m-d H:i:s',$userList['delivery_time']) :'';
        $userList['high_praise_time'] = $userList['high_praise_time'] ? date('Y-m-d H:i:s',$userList['high_praise_time']) :'';
        $userList['terminal'] = $userList['terminal']==1 ? '电脑': '移动';
        $userList['create_time'] = date('Y-m-d H:i:s',$userList['create_time']);
        return $this->success('success','',$userList);
    }

    /**
     * @notes 单条返款
     * @date 2019/9/25
     * @time 12:38
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnPay(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('请选择要返款的任务单！');
        if(!$data['price'])return $this->error('请填写返款金额！');
        if(!is_numeric($data['price']))return $this->error('请正确的填写返款金额！');
        $list = Db::name('user_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id'],'state'=>5])->field("id,seller_task_id,task_number,principal,state")->find();
        if(!$list)return $this->error('未找到数据或数据状态不正确！请刷新重试');
        $margin = Db::name('seller_task')->where(['id'=>$list['seller_task_id']])->value('margin');
        $margin = $margin ? $margin : 0;
        if($data['price'] < $list['principal'] * 0.8)return $this->error('返款金额下调不得低于80%');
        if($data['price'] > $list['principal'] * 1.2)return $this->error('返款金额上调不得高于20%');
        $price = $data['price'] - $list['principal'] - $margin;
        if($this->seller['balance'] < $price)return $this->error('返款有差额，账户余额不足补差价');
        try{
            Db::startTrans();
            if($price != 0){
                $update['balance'] = $this->seller['balance'] - $price;
                Db::name('seller')->where(['id'=>$this->seller['id']])->update($update);
                if($price < 0){
                    $prices = -$price;
                    $str = '退回';
                }else{
                    $prices = $price;
                    $str = '扣除';
                }
                if(!finance($this->seller['id'],1, -$price,1,9,"任务{$list['task_number']}返款补差额{$str}押金{$prices}"))throw new Exception('财务写入失败！');
            }
            $task['seller_principal'] = $data['price'];
            $task['platform_refund_time'] = time();
            $task['state'] = 6;
            Db::name('user_task')->where(['id'=>$list['id']])->update($task);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('返款成功！');
    }

    /**
     * @notes 看是否需要商品校核码
     * @date 2019/10/8
     * @time 17:44
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function verifyGoods(Request $request){
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('未找到商品数据！');
        $goods = Db::name('goods')->where(['seller_id'=>$this->seller['id'],'id'=>$data['id']])->find();
        if(!$goods)return $this->error('未找到商品数据！');
        $system = Db::name('system')->find();
        if(!$goods['number'] && $system['switch']==1)return $this->error('做任务需要商品校验码，该商品没有！');
        return $this->success('success');
    }

    /**
     * @notes 文字数据
     * @date 2019/10/14
     * @time 9:58
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function praise(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误');
        $list = Db::name('seller_task_praise')->where(['seller_task_id'=>$data['id'],'type'=>1])->select();
        $list = $list ? $list->toArray() : [];
        $res = [];
        $praise = [];
        foreach ($list as $item){
            $res[$item['goods_id']][] = $item;
        }
        foreach ($res as $key=>$item){
            $info['praise'] = $item;
            $goods = Db::name('goods')->where(['id'=>$key])->field('id,name,pc_img')->find();
            $goods['pc_img'] = json_decode($goods['pc_img'])[0];
            $info['goods'] = $goods;
            $praise[] = $info;
        }
        return $this->success('success','',$praise);
    }

    /**
     * @notes 图片数据
     * @date 2019/10/14
     * @time 9:58
     * @param Request $request
     */
    public function imgPraise(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误');
        $list = SellerTaskPraise::where(['seller_task_id'=>$data['id'],'type'=>2])->select();
        $list = $list ? $list->toArray() : [];
        return $this->success('success','',$list);
    }

    /**
     * @notes 视频数据
     * @date 2019/10/14
     * @time 9:58
     * @param Request $request
     */
    public function videoPraise(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误');
        $list = Db::name('seller_task_praise')->where(['seller_task_id'=>$data['id'],'type'=>3])->value('content');
        return $this->success('success','',$list);
    }

    /**
     * @notes 取消任务
     * @date 2019/10/14
     * @time 18:17
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskCancel(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误');
        $list = Db::name('seller_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->find();
        if(!in_array($list['status'],[1,2,3]))return $this->error('任务状态不正确！');
        if($list['status']==3 && $list['num'] != $list['incomplete_num'])return $this->error('已有人接单不能取消！');
        try{
            Db::startTrans();
            Db::name('seller_task')->where(['id'=>$data['id'],'seller_id'=>$this->seller['id']])->update(['status'=>5,'cancel_time'=>time()]);
            if($list['status']!=1){
                Db::name('seller')->where(['id'=>$this->seller['id']])->setInc('balance',$list['yajin']);
                Db::name('seller')->where(['id'=>$this->seller['id']])->setInc('reward',$list['yinding']);
                if(!finance($this->seller['id'],1,$list['yajin'],1,10,"商家取消任务{$list['task_number']}退回押金{$list['yajin']}元"))throw new Exception('财务写入失败！');
                if($list['yinding']>0){
                    if(!finance($this->seller['id'],1,$list['yinding'],2,10,"商家取消任务{$list['task_number']}退回银锭{$list['yinding']}银锭"))throw new Exception('财务写入失败！');
                }
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('取消成功！');

    }

    public function uploadVideo(Request $request){
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext'=>'mp4'])->move(ROOT_PATH . 'public' . DS . 'uploads'. DS .'video');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getSaveName();
            $path = DS .'uploads'. DS .'video'. DS .$info->getSaveName();
            return json(['code'=>1,'data'=>$path]);
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
        }else{
            // 上传失败获取错误信息
            return json(['code'=>0,'msg'=>$file->getError()]);
        }
    }



}
