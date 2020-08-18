<?php
namespace app\mobile\controller;
use app\buy\model\UserTask;
use app\common\model\UserDepositRecharge;
use app\common\model\UserVipRecord;
use think\Controller;
use think\Db;
use app\common\model\Sms; //引入手机验证码
use app\common\controller\Img;
use think\Exception;
use think\Request;
use think\Session;
use app\common\controller\aliyunOss;

class My extends Base
{

    //个人中心
    public function index()
    {
        $now=time();
        $where_user_principal_freeze['state']=  array('not in','2,1,0');//状态不是已取消 stat不等于2
        $all_user_principal=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where('task_step',3)
            ->where($where_user_principal_freeze)
            ->sum('user_principal');
        $this->assign('all_user_principal',$all_user_principal);//总计垫付本金
        $myself=Db::name('users')
            ->where('id',$this->id)
            ->find();
        $this->assign('myself',$myself);
        $system=Db::name('system')->where('id',1)->find();
        $discounting=$myself['reward']*$system['reward_price'];
        $this->assign('discounting',$discounting);
        $num = Db::name('users')->where(['tjuser'=>$myself['username'],'tjuser_state'=>1])->count();
        $num1 = Db::name('seller')->where(['tjuser'=>$myself['username'],'tjuser_state'=>1])->count();
        $num_day = Db::name('users')->where(['tjuser'=>$myself['username'],'tjuser_state'=>1,'create_time'=>['gt',strtotime(date('Y-m-d'))]])->count();
        $num_day1 =  Db::name('seller')->where(['tjuser'=>$myself['username'],'tjuser_state'=>1,'create_time'=>['gt',strtotime(date('Y-m-d'))]])->count();


        $all_invite=$num + $num1; //总计邀请人数
        $begin_day=strtotime(date('Y-m-d',time()));
        $between_time_day['create_time']=['between',[$begin_day,$now]];
        $day_invite=$num_day + $num_day1;
        $this->assign('myself',$myself);
        $this->assign('all_invite',$all_invite);//总计邀请人数
        $this->assign('day_invite',$day_invite);

        $all_num_task=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where('state',1)
            ->count();//总完成任务个数
        $this->assign('all_num_task',$all_num_task);
        $begin_month=strtotime(date('Y-m-1'));
        $between_time['create_time']=['between',[$begin_month,$now]];
        $where_freeze['state']= array('neq',2);
        $residue_task=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where($between_time)
            ->where($where_freeze)
            ->count(); //本月任务个数
        $residue_task=220-$residue_task;
        $this->assign('residue_task',$residue_task);//本月剩余任务个数
        $all_obtain_reward=Db::name('user_reward_recharge')
            ->where('uid',$this->id)
            ->where('type',7)
            ->sum('price');
        $this->assign('all_obtain_reward',$all_obtain_reward);//累计赚取银锭
        $where_freeze['state']=  array('not in','2,1,0');//状态不是已取消 stat不等于2
        $where_freeze_complete['state']= array('neq',1);
        $wait_shop_issue_user_divided=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where($where_freeze)
            //-where($where_freeze_complete)
            ->sum('user_divided');
        $wait_shop_issue_commission=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where($where_freeze)
            ->sum('commission');
        $wait_shop_issue=$wait_shop_issue_user_divided+$wait_shop_issue_commission;
        $this->assign('wait_shop_issue',$wait_shop_issue);//待商家发放银锭
        $where_freeze_limit['state']=  array('not in','2,1');//状态不是已取消 stat不等于2
        $freeze_reward=Db::name('user_task')
            ->where('user_id',$this->id)
            ->where($where_freeze_limit)
            ->where($where_freeze_complete)
            ->count();
        $this->assign('freeze_reward',$freeze_reward);//冻结的银锭


        $buyno=Db::name('user_buyno')
            ->where(['uid'=>$this->id])
            // ->where(['state'=>1])
            ->select();
        $this->assign('buyno',$buyno);
        $topnav=1;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }

    public function taskmanagement(Request $request){
        $value=$request->param('value');
        if(request()->isAjax()) {
            $buyno=Db::name('user_buyno')->where('uid',$this->id)->select();
            $this->assign('buyno',$buyno);
            $data = $request->param();
            if(!empty($data['zhuipin'])){
                if(!empty($data['buyno'])){
                    $where['c.id']=$data['buyno'];
                }
                if($data['zhuipin'] ==1){
                    $where['e.state']=2;
                }
                if($data['zhuipin'] ==2){
                    $where['e.state']=3;
                }
                if($data['zhuipin'] ==3){
                    $where['e.state']=4;
                }
                if($data['zhuipin'] ==4){
                    $where['e.state']=6;
                }
                $where['a.user_id']=$this->id;
                $where['a.state']=1;
                $total = Db::name('user_task')
                    ->field('e.state,a.task_number,a.delivery_state,
                    a.goods_id,a.user_buyno_id,a.principal,a.commission,a.user_principal,a.seller_principal,b.publish_time,
                    b.task_type,c.wwid,d.id,d.shop_name,d.type,a.id')
                    ->alias('a')
                    ->join('seller_task b','a.seller_task_id = b.id')
                    ->join('user_buyno c','a.user_buyno_id = c.id')
                    ->join('shop d','b.seller_id =d.id')
                    ->join('review_task e','a.id =e.user_task_id')
                    ->where($where)
                    ->count('a.id');
                $list=Db::name('user_task')
                    ->field('e.state,a.task_number,a.delivery_state,a.seller_task_id,
                   a.goods_id,a.user_buyno_id,a.principal,a.commission,a.user_divided,a.seller_principal,
                   a.user_principal,b.publish_time,b.task_type,c.wwid,d.id,d.shop_name,d.type,a.id,a.create_time,e.taobao_number,
                   e.user_money,e.id as review_task_id,e.task_number')
                    ->alias('a')
                    ->join('seller_task b','a.seller_task_id = b.id')
                    ->join('user_buyno c','a.user_buyno_id = c.id')
                    ->join('shop d','b.shop_id =d.id')
                    ->join('review_task e','a.id =e.user_task_id')
                    ->where($where)
                    ->limit(($data['page']-1)*10,10)
                    ->order('a.id desc')
                    ->select();
                $list = $list ? $list->toArray() : [];
                foreach($list as $k =>&$v){
                    if($v['goods_id']){
                        $goods_id=json_decode($v['goods_id']);
                        $main_product_id=$goods_id[0];
                        $main_product=Db::name('goods')->where('id',$main_product_id)->find();
                        $v['main_product_name']=$main_product['name'];
                        $main_product_pc_img=json_decode($main_product['pc_img']);
                        $v['main_product_pc_img']=$main_product_pc_img[0];
                    }else{
                        $task_goods[$k]=Db::name('task_goods')->where('task_id',$v['seller_task_id'])->find();
                        $v['main_product_name']=$task_goods[$k]['name'];
                        $v['main_product_pc_img']=$task_goods[$k]['pc_img'];
                    }
                    $type_array=array(
                        '1'=>"文字好评",
                        '2'=>"淘口令",
                        '3'=>"二维码",
                        '4'=>"直通车",
                        '5'=>"通道任务",
                    );
                    $v['task_type']=$type_array[$v['task_type']];
                    $shop_type=array(
                        '1'=>"淘宝",
                        '2'=>"天猫",
                        '3'=>"飞猪",
                    );
                    $v['type']=$shop_type[$v['type']];
                    $shop_img=array(
                        '淘宝'=>"/static/buy/img/taobao.png",
                        '天猫'=>"/static/buy/img/tianmao.png",
                        '飞猪'=>"/static/buy/img/feizhu.png",
                    );
                    $v['shop_img']=$shop_img[$v['type']];
                    if($v['state'] ==2){
                        $list[$k]['zhuipin_state']= '1';
                    }
                    $list[$k]['index_state']= '9';
                    $foo = False;
                    $list[$k]['checked'] =$foo;
                    $state_array=array(
                        '2'=>"待处理追评任务",
                        '3'=>"待返款追评任务",
                        '4'=>"已完成追评任务",
                        '6'=>"已拒接追评任务",
                    );
                    $v['state']=$state_array[$v['state']];
                    $list[$k]['shop_name']=substr($v['shop_name'] , 0 , 9)."...";
                    $list[$k]['shop_name']=iconv("utf-8","gbk//IGNORE", $list[$k]['shop_name']);
                    $list[$k]['shop_name'] = mb_convert_encoding( $list[$k]['shop_name'],'UTF-8','GBK');
                    $v['publish_time']=date('Y-m-d H:i:s',$v['publish_time']);
                    $v['create_time']=date('Y-m-d H:i:s',$v['create_time']);
                    if(!$list[$k]['main_product_name']){
                        $list[$k]['main_product_name']="";
                    }else{
                        $list[$k]['main_product_name']=substr($v['main_product_name'] , 0 , 9)."...";
                        $list[$k]['main_product_name']=iconv("utf-8","gbk//IGNORE", $list[$k]['main_product_name']);
                        $list[$k]['main_product_name'] = mb_convert_encoding( $list[$k]['main_product_name'],'UTF-8','GBK');
                    }
                }

                $res['list'] = $list;
                $res['total'] = $total;
                return $this->success('success','',$res);
            }else{
                $where['a.user_id']=$this->id;
                // $where['a.state']= array('neq',2);
                if(isset($data['indexorder'])){
                    //$where['a.task_number']=$data['indexorder'];
                    $where['a.task_number']=['like','%'.$data['indexorder'].'%'];
                }   //搜索订单号
                if(!empty($data['choose_a']) && $data['choose_a']==1){
                    $where['a.state']='3';
                }   //已打印快递单，待发货
                if(!empty($data['choose_a']) && $data['choose_a']==2){
                    $where['a.state']='4';
                }  //已发货，待确认收货
                if(!empty($data['choose_a']) && $data['choose_a']==3){
                    $where['a.state']='5';
                }  //已确认收货，待商家返款
                if(!empty($data['choose_a']) && $data['choose_a']==4){
                    $where['a.state']='6';
                }  //商家已返款，待确认返款
                if(!empty($data['choose_a']) && $data['choose_a']==5){
                    $where['a.state']='1';
                }  //已完成
                if(!empty($data['choose_a']) && $data['choose_a']==6){
                    $where['a.deltask_type']='1';
                }  //超时取消
                if(!empty($data['choose_a']) && $data['choose_a']==7){
                    $where['a.deltask_type']='3';
                }  //客服取消
                if(!empty($data['choose_a']) && $data['choose_a']==8){
                    $where['a.deltask_type']='2';
                }  //买手手动取消
                if(!empty($data['buyno'])){
                    $where['c.id']=$data['buyno'];
                }
                if(!empty($data['task_type'])){
                    $where['b.task_type']=$data['task_type'];
                }
                if (!empty($data['datetime'])) {
                    $where['b.publish_time'] = ['between',[strtotime($data['datetime'][0]),strtotime($data['datetime'][1])]];
                }
                if(!empty($data['terminal']) && $data['terminal'] == 1){
                    $where['a.terminal'] = 1;
                }
                if(!empty($data['terminal']) && $data['terminal'] == 2){
                    $where['a.terminal'] = 2;
                }
                $total = Db::name('user_task')
                    ->field('a.state,a.task_number,a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,a.commission,a.user_principal,b.publish_time,b.task_type,c.wwid,d.id,d.shop_name,d.type,a.id')
                    ->alias('a')
                    ->join('seller_task b','a.seller_task_id = b.id')
                    ->join('user_buyno c','a.user_buyno_id = c.id')
                    ->join('shop d','b.seller_id =d.id')
                    ->where($where)
                    ->count('a.id');
                $list=Db::name('user_task')
                    ->field('a.state,a.task_number,a.delivery_state,a.seller_task_id,a.deltask_type,
                a.goods_id,a.user_buyno_id,a.principal,a.commission,a.user_divided,a.seller_principal,
                a.user_principal,b.publish_time,b.task_type,c.wwid,d.id,d.shop_name,d.type,a.id,a.create_time')
                    ->alias('a')
                    ->join('seller_task b','a.seller_task_id = b.id')
                    ->join('user_buyno c','a.user_buyno_id = c.id')
                    ->join('shop d','b.shop_id =d.id')
                    ->where($where)
                    ->limit(($data['page']-1)*10,10)
                    ->order('a.id desc')
                    ->select();
                $list = $list ? $list->toArray() : [];
                foreach($list as $k =>&$v){
                    if($v['goods_id']){
                        $goods_id=json_decode($v['goods_id']);
                        $main_product_id=$goods_id[0];
                        $main_product=Db::name('goods')->where('id',$main_product_id)->find();
                        $v['main_product_name']=$main_product['name'];
                        $main_product_pc_img=json_decode($main_product['pc_img']);
                        $v['main_product_pc_img']=$main_product_pc_img[0];
                    }else{
                        $task_goods[$k]=Db::name('task_goods')->where('task_id',$v['seller_task_id'])->find();
                        $v['main_product_name']=$task_goods[$k]['name'];
                        $v['main_product_pc_img']=$task_goods[$k]['pc_img'];
                    }
                    $type_array=array(
                        '1'=>"文字好评",
                        '2'=>"淘口令",
                        '3'=>"二维码",
                        '4'=>"直通车",
                        '5'=>"通道任务",
                    );
                    $v['task_type']=$type_array[$v['task_type']];
                    $shop_type=array(
                        '1'=>"淘宝",
                        '2'=>"天猫",
                        '3'=>"飞猪",
                    );
                    $v['type']=$shop_type[$v['type']];
                    $shop_img=array(
                        '淘宝'=>"/static/buy/img/taobao.png",
                        '天猫'=>"/static/buy/img/tianmao.png",
                        '飞猪'=>"/static/buy/img/feizhu.png",
                    );
                    $v['shop_img']=$shop_img[$v['type']];
                    $index_need_array=array(
                        '0'=>"0",
                        '1'=>"5",
                        '2'=>"6",
                        '3'=>"1",
                        '4'=>"2",
                        '5'=>"3",
                        '6'=>"4"
                    );
                    $list[$k]['index_state']= $index_need_array[$v['state']];
                    $foo = False;
                    $list[$k]['checked'] =$foo;
                    $state_array=array(
                        '0'=>"正在进行",
                        '1'=>"已完成",
                        '2'=>"已取消",
                        '3'=>"待发货",
                        '4'=>"待收货",
                        '5'=>"待返款",
                        '6'=>"待确认返款"
                    );
                    if(!empty($list[$k]['deltask_type'])){
                        if($list[$k]['deltask_type'] ==1){
                            $v['state']="已超时";
                        }
                        if($list[$k]['deltask_type'] ==2){
                            $v['state']="已放弃";
                        }
                        if($list[$k]['deltask_type'] ==3){
                            $v['state']="已取消";
                        }
                    }else{
                        $v['state']=$state_array[$v['state']];
                    }
                    $list[$k]['shop_name']=substr($v['shop_name'] , 0 , 9)."...";
                    $list[$k]['shop_name']=iconv("utf-8","gbk//IGNORE", $list[$k]['shop_name']);
                    $list[$k]['shop_name'] = mb_convert_encoding( $list[$k]['shop_name'],'UTF-8','GBK');
                    $v['publish_time']=date('Y-m-d H:i:s',$v['publish_time']);
                    $v['create_time']=date('Y-m-d H:i:s',$v['create_time']);
                    if(!$list[$k]['main_product_name']){
                        $list[$k]['main_product_name']="";
                    }else{
                        $list[$k]['main_product_name']=substr($v['main_product_name'] , 0 , 9)."...";
                        $list[$k]['main_product_name']=iconv("utf-8","gbk//IGNORE", $list[$k]['main_product_name']);
                        $list[$k]['main_product_name'] = mb_convert_encoding( $list[$k]['main_product_name'],'UTF-8','GBK');
                    }
                }
                $res['list'] = $list;
                $res['total'] = $total;
                return $this->success('success','',$res);
            }
        }
        $this->assign('value',$value);
        $buyno=Db::name('user_buyno')
            ->where(['uid'=>$this->id])
            ->where(['state'=>1])
            ->select();
        $this->assign('buyno',$buyno);
        return view();
    }
    //查看详情
    public function detail(){
        $data=input('param.id');
        $list=Db::name('user_task')
            ->field('a.id,a.state,a.terminal,a.task_number,a.create_time,a.seller_task_id,
            a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,a.deltask_type,a.is_ys,a.yf_price,a.wk_price,
            a.commission,a.user_divided,a.user_principal,a.keywordimg,a.chatimg,
            a.seller_principal,a.delivery,a.delivery_num,a.update_time,a.table_order_id,
            a.step_two_complete,a.upload_order_time,a.delivery_time,a.platform_refund_time,a.img_praise,a.text_praise,
             a.video_praise,b.publish_time,b.task_type,b.goods_price,c.wwid,d.shop_name,e.seller_name,a.ids')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->join('user_buyno c','a.user_buyno_id = c.id')
            ->join('shop d','b.shop_id =d.id')
            ->join('seller e','b.seller_id =e.id')
            ->where('a.id',$data)
            ->find();
        $list['create_time']=date('Y-m-d H:i:s',$list['create_time']);
        if($list['step_two_complete']){
            $list['step_two_complete']=date('Y-m-d H:i:s',$list['step_two_complete']);
        }else{
            $list['step_two_complete']= '';
        }
        if($list['upload_order_time']){
            $list['upload_order_time']=date('Y-m-d H:i:s',$list['upload_order_time']);
        }else{
            $list['upload_order_time']= '';
        }
        if($list['delivery_time']){
            $list['delivery_time']=date('Y-m-d H:i:s',$list['delivery_time']);
        }else{
            $list['delivery_time']= '';
        }

        if($list['platform_refund_time']){
            $list['platform_refund_time']=date('Y-m-d H:i:s',$list['platform_refund_time']);
        }else{
            $list['platform_refund_time']= '';
        }

        $terminal_type=array(
            '1'=>"电脑",
            '2'=>"手机"
        );
        if($list['goods_id']){
            $goods_id=json_decode($list['goods_id']);
            foreach ($goods_id as $k => $v){
                $text_praise=json_decode($list['text_praise']);
                $product[$k]=Db::name('goods')->where('id',$v)->find();
                $main_product_pc_img=json_decode($product[$k]['pc_img']);
                $product[$k]['pc_img']=$main_product_pc_img[0];
                $product[$k]['text_praise']=$text_praise[$k];
            }
        }else{
            $task_goods=Db::name('task_goods')->where('task_id',$list['seller_task_id'])->select();
            foreach ($task_goods as $k =>$v){
                $text_praise=json_decode($list['text_praise']);
                $product[$k]['pc_img']=$v['pc_img'];
                $product[$k]['text_praise']=$text_praise[$k];
            }
        }
        $ids = json_decode($list['ids']);
        $img_praise=Db::name('seller_task_praise')->where(['id'=>['in',$ids],'type'=>2])->value('content');
        $list['img_praise']=json_decode($img_praise);
        $video_praise=Db::name('seller_task_praise')->where(['id'=>['in',$ids],'type'=>3])->value('content');
        $list['video_praise']=$video_praise ? $video_praise : '';
        $list['terminal']=$terminal_type[$list['terminal']];
        $list['update_time']=date('Y-m-d H:i:s',$list['update_time']);
        $type_array=array(
            '1'=>"文字好评",
            '2'=>"淘口令",
            '3'=>"二维码",
            '4'=>"直通车",
            '5'=>"通道任务",
        );
        $list['task_type']=$type_array[$list['task_type']];
        if($list['deltask_type']){
            $deltask_array=array(
                '1'=>"自动取消任务",
                '2'=>"买家手动放弃任务",
                '3'=>"客服取消任务",
            );
            $list['deltask_type']=$deltask_array[$list['deltask_type']];
        }else{
            $list['deltask_type']=0;
        }
        if($list['is_ys']==1){
            $list['seller_principal']=$list['yf_price'].+''.'+'.$list['wk_price'];
        }
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
        $this->assign('list',$list);
        $this->assign('product',$product);
        return view();
    }
    //去收款
    public function shoukuan(){
        $data=input('param.id');
        $list=Db::name('user_task')
            ->field('a.id,a.seller_task_id,a.terminal,a.state,a.task_number,a.seller_task_id,
            a.create_time,a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,
            a.commission,a.user_principal,a.keywordimg,a.chatimg,a.seller_principal,
            a.delivery,a.delivery_num,a.update_time,a.text_praise,a.img_praise,
            a.video_praise,b.publish_time,b.task_type,c.wwid,d.shop_name')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->join('user_buyno c','a.user_buyno_id = c.id')
            ->join('shop d','b.shop_id =d.id')
            ->where('a.id',$data)
            ->find();
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
        if($list['goods_id']){
            $goods_id=json_decode($list['goods_id']);
            foreach ($goods_id as $k => $v){
                $text_praise=json_decode($list['text_praise']);
                $product[$k]=Db::name('goods')->where('id',$v)->find();
                $main_product_pc_img=json_decode($product[$k]['pc_img']);
                $product[$k]['pc_img']=$main_product_pc_img[0];
                $product[$k]['text_praise']=$text_praise[$k];
            }
        }else{
            $task_goods=Db::name('task_goods')->where('task_id',$list['seller_task_id'])->select();
            foreach ($task_goods as $k =>$v){
                $text_praise=json_decode($list['text_praise']);
                $product[$k]['pc_img']=$v['pc_img'];
                $product[$k]['name']=$v['name'];
                $product[$k]['text_praise']=$text_praise[$k];
            }
        }
        $img_praise=json_decode($list['img_praise']);
        $list['img_praise']=$img_praise;
        $this->assign('product',$product);
        $this->assign('list',$list);
        return view();
    }
    //去收货
    public function shouhuo(){
        $data=input('param.id');
        $list=Db::name('user_task')
            ->field('a.id,a.seller_task_id,a.terminal,a.state,a.task_number,a.seller_task_id,
            a.create_time,a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,
            a.commission,a.user_principal,a.keywordimg,a.chatimg,a.seller_principal,
            a.delivery,a.delivery_num,a.update_time,a.text_praise,a.img_praise,a.ids,a.is_shengji,
            a.video_praise,b.publish_time,b.task_type,b.memo,c.wwid,d.shop_name')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->join('user_buyno c','a.user_buyno_id = c.id')
            ->join('shop d','b.shop_id =d.id')
            ->where('a.id',$data)
            ->find();
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
                $main_product_pc_img=json_decode($good['pc_img']);
                $good['pc_img']=$main_product_pc_img[0];
                $good['text_praise']=isset($text_praise[$k]) ? $text_praise[$k] : '';
                if($k==0){
                    $array = json_decode($list['img_praise']);
                    if(!empty($array)){
                        $good['img_praise'] = implode(",", $array);}else{
                        $good['img_praise']="";
                    }
                    $good['video_praise'] = $list['video_praise'];
                }else{
                    $good['img_praise'] ="";
                    $good['video_praise'] = '';
                }
                $goods[] = $good;
            }
        }elseif($list['is_shengji']==2){
            $ids = json_decode($list['ids']);
            $goods=Db::name('task_goods')->where('task_id',$list['seller_task_id'])->select()->toArray();
            foreach ($goods as $k =>$v){
                $goods[$k]['text_praise']=Db::name('seller_task_praise')->where(['seller_task_id'=>$list['seller_task_id'],'type'=>1,'id'=>['in',$ids],'goods_id'=>$v['goods_id']])->value('content');
                $img = Db::name('seller_task_praise')->where(['seller_task_id'=>$list['seller_task_id'],'type'=>2,'id'=>['in',$ids],'goods_id'=>$v['goods_id']])->value('content');
                $array=$img ? json_decode($img) : [];
                $goods[$k]['img_praise'] = implode(",", $array);
                $goods[$k]['video_praise']=Db::name('seller_task_praise')->where(['seller_task_id'=>$list['seller_task_id'],'type'=>3,'id'=>['in',$ids],'goods_id'=>$v['goods_id']])->value('content');
            }
        }
        //dump($goods);exit;
        $this->assign('product',$goods);
        $this->assign('list',$list);
        return view();
    }
    //去收货
    public function take_delivery(){
        $data=input();
        $path = 'uploads' . DS . 'task' . DS;
        $res = aliyunOss::uploadBase64($data['high_praise_img'],$path);
       // $res = Img::base64_image_content($data['high_praise_img'],'./uploads/task/');
//        if($res['code']==0){
//            return $this->error('好评截图'.$res['data']);
//        }
        $take_delivery=[
            'high_praise_img'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res,
            'state'=>5,
            'update_time'=>time(),
            'high_praise_time'=>time(),
        ];
        $user_task=Db::name('user_task')->where('id',$data['task_id'])->find();
        if($user_task['state'] !=4){
            return $this->error('此任务出现错误，请检查。');
        }
        $resa=Db::name('user_task')
            ->where('id',$data['task_id'])
            ->update($take_delivery);
        $value=2;
        if($resa){
            return $this->success('确认收货成功',url('My/taskmanagement',['value'=>$value]));
        }
    }

    //追评查看详情
    public function zhuidetail(){
        $data=input('param.id');
        $list=Db::name('user_task')
            ->field('a.id,a.state,a.terminal,a.seller_task_id,a.task_number,a.create_time,
            a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,a.deltask_type,
            a.commission,a.user_divided,a.user_principal,a.keywordimg,a.chatimg,
            a.seller_principal,a.delivery,a.delivery_num,a.update_time,a.table_order_id,
            a.step_two_complete,a.upload_order_time,a.delivery_time,a.platform_refund_time,a.img_praise,a.text_praise,f.task_number,
            a.video_praise,b.publish_time,b.task_type,b.goods_price,c.wwid,d.shop_name,e.seller_name,f.id as zp_id,f.img,f.state,f.upload_time')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->join('user_buyno c','a.user_buyno_id = c.id')
            ->join('shop d','b.shop_id =d.id')
            ->join('seller e','b.seller_id =e.id')
            ->join('review_task f','a.id =f.user_task_id')
            ->where('f.id',$data)
            ->find();
        //dump($list);exit;
        $list['create_time']=date('Y-m-d H:i:s',$list['create_time']);
        if($list['step_two_complete']){
            $list['step_two_complete']=date('Y-m-d H:i:s',$list['step_two_complete']);
        }else{
            $list['step_two_complete']= '';
        }
        if($list['upload_order_time']){
            $list['upload_order_time']=date('Y-m-d H:i:s',$list['upload_order_time']);
        }else{
            $list['upload_order_time']= '';
        }
        if($list['delivery_time']){
            $list['delivery_time']=date('Y-m-d H:i:s',$list['delivery_time']);
        }else{
            $list['delivery_time']= '';
        }
        if($list['platform_refund_time']){
            $list['platform_refund_time']=date('Y-m-d H:i:s',$list['platform_refund_time']);
        }else{
            $list['platform_refund_time']= '';
        }
        if($list['upload_time']){
            $list['upload_time']=date('Y-m-d H:i:s',$list['upload_time']);
        }else{
            $list['upload_time']= '';
        }
        $terminal_type=array(
            '1'=>"电脑",
            '2'=>"手机"
        );
        if($list['goods_id']){
            $goods_id=json_decode($list['goods_id']);
            foreach ($goods_id as $k => $v){
                $text_praise=json_decode($list['text_praise']);
                $product[$k]=Db::name('goods')->where('id',$v)->find();
                $main_product_pc_img=json_decode($product[$k]['pc_img']);
                $product[$k]['pc_img']=$main_product_pc_img[0];
                $product[$k]['text_praise']=$text_praise[$k];
                $review_task_praise=Db::name('review_task_praise')->where(['task_id'=>$list['zp_id'],'goods_id'=>$v['goods_id']])->select();
                $product[$k]['review_type']=[];
                $product[$k]['review_content']=[];
                foreach ($review_task_praise as $k1=>$v1){
                    if($v1['type']==1){
                        $product[$k]['wenzi']=$v1['content'];
                    }
                    if($v1['type']==2){
                        $img = explode(",", $v1['content']);
                        $product[$k]['img']=$v1['content'];
                    }
                    if($v1['type']==3){
                        $product[$k]['video']=$v1['content'];
                    }
                }
            }
        }else{
            $task_goods=Db::name('task_goods')->where('task_id',$list['seller_task_id'])->select();
            //dump($task_goods);exit;
            foreach ($task_goods as $k =>$v){
                $text_praise=json_decode($list['text_praise']);
                $product[$k]['pc_img']=$v['pc_img'];
                $product[$k]['name']=$v['name'];
                $product[$k]['text_praise']=$text_praise[$k];
                $review_task_praise=Db::name('review_task_praise')->where(['task_id'=>$list['zp_id'],'goods_id'=>$v['goods_id']])->select();
                //dump($review_task_praise);exit;
                $product[$k]['review_type']=[];
                $product[$k]['review_content']=[];
                foreach ($review_task_praise as $k1=>$v1){
                    if($v1['type']==1){
                        $product[$k]['wenzi']=$v1['content'];
                    }
                    if($v1['type']==2){
                        $img = explode(",", $v1['content']);
                        $product[$k]['img']=$v1['content'];
                    }
                    if($v1['type']==3){
                        $product[$k]['video']=$v1['content'];
                    }
                }
                if(empty($product[$k]['wenzi'])){
                    $product[$k]['wenzi']="";
                }
                if(empty($product[$k]['img'])){
                    $product[$k]['img']="";
                }
                if(empty($product[$k]['video'])){
                    $product[$k]['video']="";
                }
            }
        }
        $img_praise=json_decode($list['img_praise']);
        $list['img_praise']=$img_praise;
        $list['terminal']=$terminal_type[$list['terminal']];
        $list['update_time']=date('Y-m-d H:i:s',$list['update_time']);
        $type_array=array(
            '1'=>"文字好评",
            '2'=>"淘口令",
            '3'=>"二维码",
            '4'=>"直通车",
            '5'=>"通道任务",
        );
        $list['task_type']=$type_array[$list['task_type']];
        if($list['deltask_type']){
            $deltask_array=array(
                '1'=>"自动取消任务",
                '2'=>"买家手动放弃任务",
                '3'=>"客服取消任务",
            );
            $list['deltask_type']=$deltask_array[$list['deltask_type']];
        }else{
            $list['deltask_type']=0;
        }
        if($list['state'] == 6){
            $list['show'] =1;
        }else{
            $list['show'] =0;
        }
        $state_array=array(
            '3'=>"待返款",
            '4'=>"已返款",
            '5'=>"已取消",
            '6'=>"拒接此任务",
            '7'=>"已拒接",
        );
        $list['state']=$state_array[$list['state']];
        //dump($list);exit;
        $this->assign('list',$list);
        $this->assign('product',$product);
        return view();
    }
    //追评
    public function zhuipin(){
        $data=input('param.id');
        $list=Db::name('user_task')
            ->field('a.id,a.seller_task_id,a.terminal,a.state,a.task_number,a.seller_task_id,
            a.create_time,a.delivery_state,a.goods_id,a.user_buyno_id,a.principal,
            a.commission,a.user_principal,a.keywordimg,a.chatimg,a.seller_principal,
            a.delivery,a.delivery_num,a.update_time,a.text_praise,a.img_praise,
            a.video_praise,b.publish_time,b.task_type,b.memo,c.wwid,d.shop_name,
            e.user_task_id,e.task_number,e.id as zp_id')
            ->alias('a')
            ->join('seller_task b','a.seller_task_id = b.id')
            ->join('user_buyno c','a.user_buyno_id = c.id')
            ->join('shop d','b.shop_id =d.id')
            ->join('review_task e','a.id =e.user_task_id')
            ->where('e.id',$data)
            ->find();
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
        $task_goods=Db::name('task_goods')->where('task_id',$list['seller_task_id'])->select();
        //dump($task_goods);exit;
        foreach ($task_goods as $k =>$v){
            $text_praise=json_decode($list['text_praise']);
            $product[$k]['pc_img']=$v['pc_img'];
            $product[$k]['name']=$v['name'];
            $product[$k]['text_praise']=$text_praise[$k];
            $review_task_praise=Db::name('review_task_praise')->where(['task_id'=>$list['zp_id'],'goods_id'=>$v['goods_id']])->select();
            //dump($review_task_praise);exit;
            $product[$k]['review_type']=[];
            $product[$k]['review_content']=[];
            foreach ($review_task_praise as $k1=>$v1){
                if($v1['type']==1){
                    $product[$k]['wenzi']=$v1['content'];
                }
                if($v1['type']==2){
                    $img = explode(",", $v1['content']);
                    $product[$k]['img']=$v1['content'];
                }
                if($v1['type']==3){
                    $product[$k]['video']=$v1['content'];
                }
            }
            if(empty($product[$k]['wenzi'])){
                $product[$k]['wenzi']="";
            }
            if(empty($product[$k]['img'])){
                $product[$k]['img']="";
            }
            if(empty($product[$k]['video'])){
                $product[$k]['video']="";
            }
        }
        //dump($product);exit;
        $img_praise=json_decode($list['img_praise']);
        $list['img_praise']=$img_praise;
        $this->assign('product',$product);
        $this->assign('list',$list);
        return view();
    }

    //去追评
    public function take_zhuipin(){
        $data=input();
        $img=$data['high_praise_img'];
        foreach ($img as $k=>$v){
            $path = 'uploads' . DS . 'task' . DS;
            $res[$k] = aliyunOss::uploadBase64($v,$path);
//            $res[$k]=Img::base64_image_content($v,'./uploads/task/');
//            if($res[$k]['code']==0){
//                return $this->error('追评截图'.$res[$k]['data']);
//            }
            $res_img[$k]='http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res[$k];
        }
        $res_img = implode(",", $res_img);
        $take_delivery=[
            'img'=>$res_img,
            'state'=>3,
            'upload_time'=>time(),
        ];
        $user_task=Db::name('user_task')->where('id',$data['task_id'])->find();
        $review_task=Db::name('review_task')->where('user_task_id',$data['task_id'])->max('id');
        $review_task=Db::name('review_task')->where('id',$review_task)->find();
        if($review_task['state'] !=2){
            return $this->error('此追评任务出现错误，请检查。');
        }
        $resa=Db::name('review_task')
            ->where('id',$review_task['id'])
            ->update($take_delivery);
        $value=2;
        if($resa){
            return $this->success('确认追评成功',url('My/index',['value'=>$value]));
        }
    }

    //拒绝追评
    public function refuse_zhuipin(){
        $data=input();
        $take_delivery=[
            'state'=>6,
            'confirm_time'=>time(),
        ];
        $user_task=Db::name('user_task')->where('id',$data['id'])->find();
        $review_task=Db::name('review_task')->where('id',$data['id'])->find();
        if($review_task['state']==6){
            return $this->error('此追评任务已被您拒绝。');
        }
        if($review_task['state'] !=2){
            return $this->error('此追评任务出现错误，请检查。');
        }
        $shop = Db::name('shop')->where(['id'=>$review_task['shop_id']])->value('shop_name');
        try{
            Db::startTrans();
            Db::name('review_task')->where('id',$review_task['id'])->update($take_delivery);
            $seller = Db::name('seller')->where(['id'=>$review_task['seller_id']])->find();
            $seller_edit = [];
            if($review_task['yjprice'] > 0)$seller_edit['balance'] = $seller['balance'] + $review_task['yjprice'];
            if($review_task['ydprice'] > 0)$seller_edit['reward'] = $seller['reward'] + $review_task['ydprice'];
            Db::name('seller')->where(['id'=>$seller['id']])->update($seller_edit);
            if($review_task['ydprice'] > 0){
                if(!finance($review_task['seller_id'],1,$review_task['ydprice'],2,19,"买手拒绝《{$shop}》店铺追评任务{$review_task['task_number']}退回银锭{$review_task['ydprice']}银锭"))throw new Exception('银锭退回财务写入失败');
            }
            if($review_task['yjprice'] > 0){
                if(!finance($review_task['seller_id'],1,$review_task['yjprice'],1,19,"买手拒绝《{$shop}》店铺追评任务{$review_task['task_number']}退回押金{$review_task['yjprice']}元"))throw new Exception('押金退回财务写入失败！');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        $value=2;
        return $this->success('操作成功',url('My/index',['value'=>$value]));
    }

    //会员充值
    public function vip_recharge(){
        $user_vip=db('system')->where('id',1)->value('user_vip');
        $vip=explode(',',$user_vip);
        $this->assign('vip',$vip);
        return view();
    }

    //充值生成order 支付宝支付 充值会员
    public function creat_order(){
        $data=input();
        $random = rand(100000,999999);
        $number=$random.time();
        $id = Db::name('recharge')->order('id desc')->value('id');
        $info = Db::name('recharge')->where(['uid'=>$this->id,'user_type'=>2,'state'=>0])->order('id desc')->find();
        if(time()-$info['create_time']<360){
            return $this->error('对不起,上一单未支付请等待6分钟再次充值！');
        }
        $number='2'.$this->id.$id;
        $order=[
            'uid'=>$this->id,
            'number'=>$number,
            'user_type'=>$data['user_type'],
            'type'=>$data['type'],
            'price'=>$data['price'],
            'state'=>0,
            'create_time'=>time(),
        ];
        Db::name('recharge')->insertGetId($order);
        //return $this->success('正在跳转支付',url('Money/pay',['title'=>$number,'price'=>$order['price']]));
        return $this->success('正在跳转到支付宝支付页面',url('pay/codepay',['money'=>$order['price'],'data'=>$number]));
    }

    //本金支付 充值会员
    public function principal_member(){
        $data=input();
        $user=Db::name('users')->where('id',$this->id)->find();
        if($data['price']>$user['balance']){
            return $this->error('对不起本金不足，请检查后重新输入');
        }else{
            $one_month=30*24*60*60*$data['date'];
            $change_num_balance=$user['balance']-$data['price'];
            $now=time();
            if($user['vip_time']>$now){
                $change_num_viptime=$user['vip_time']+$one_month;
            }else{
                $change_num_viptime=$now+$one_month;
            }
            $change_date_viptime=date('Y-m-d',$change_num_viptime);
        }
        $vip_record=[
            'uid'=>$this->id,
            'utype'=>2,
            'user_name'=>$user['username'],
            'price'=>$data['price'],
            'create_time'=>time(),
            'expire_time'=>$change_num_viptime,
            'remarks'=>"购买vip{$data['date']}月，使用本金{$data['price']}元，vip到期时间为{$change_date_viptime}充值成功",
        ];
        $balance=[
            'balance'=>$change_num_balance,
            'vip_time'=>$change_num_viptime,
            'vip'=>1
        ];
        Db::startTrans();
        try {
            $vip_add=Db::name('vip_record')->insert($vip_record);
            $res = Db::name('users')->where('id', $this->id)->update($balance);
            if($res) {
                finance($this->id, 2, -$data['price'], 1, 1, "购买vip{$data['date']}月，使用本金{$data['price']}元，vip到期时间为{$change_date_viptime}充值成功");
                Db::commit();
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->error('操作失败');
        }
        return $this->success('充值成功','My/vip_record');
    }

    //银锭支付 充值会员
    public function silver_member(){
        $data=input();
        $user=Db::name('users')->where('id',$this->id)->find();
        if($data['price']>$user['reward']){
            return $this->error('对不起银锭不足，请检查后重新输入');
        }else{
            $one_month=30*24*60*60*$data['date'];
            $change_num_balance=$user['reward']-$data['price'];
            $now=time();
            if($user['vip_time']>$now){
                $change_num_viptime=$user['vip_time']+$one_month;
            }else{
                $change_num_viptime=$now+$one_month;
            }
            $change_date_viptime=date('Y-m-d',$change_num_viptime);
        }
        $vip_record=[
            'uid'=>$this->id,
            'utype'=>2,
            'user_name'=>$user['username'],
            'price'=>$data['price'],
            'create_time'=>time(),
            'expire_time'=>$change_num_viptime,
            'remarks'=>"购买vip{$data['date']}月，使用银锭{$data['price']}银锭，vip到期时间为{$change_date_viptime}充值成功",
        ];
        $balance=[
            'reward'=>$change_num_balance,
            'vip_time'=>$change_num_viptime,
            'vip'=>1
        ];
        Db::startTrans();
        try {
            $vip_add=Db::name('vip_record')->insert($vip_record);
            $res = Db::name('users')->where('id', $this->id)->update($balance);
            if($res) {
                finance($this->id, 2, -$data['price'], 2, 1, "购买vip{$data['date']}月，使用银锭{$data['price']}银锭，vip到期时间为{$change_date_viptime}充值成功");
                Db::commit();
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->error('操作失败');
        }
        return $this->success('充值成功','My/vip_record');
    }

    //会员充值记录
    public function vip_record(Request $request){
        if(request()->isAjax()) {
            $data = $request->param();
            $where['uid'] = $this->id;
            $where['utype'] =2;
            if (!empty($data['datetime1']) || !empty($data['datetime2'])) {
                $datetime1=$data['datetime1']/1000;
                $datetime2=$data['datetime2']/1000;
                $datetime1=date('Y-m-d',$datetime1);
                $datetime2=date('Y-m-d',$datetime2);
                $where['create_time'] = ['between',[strtotime($datetime1),strtotime($datetime2)]];
            }
            $total = Db::name('vip_record')
                ->where($where)
                ->count('id');
            $list = Db::name('vip_record')
                ->where($where)
                ->limit(($data['page']-1)*10,10)
                ->order('id desc')
                ->select()
                ->toArray();
            foreach ($list as $k =>$v){
                $list[$k]['create_time']=date('Y-m-d H:i:s',$v['create_time']);
                $list[$k]['price']=-$list[$k]['price'];
                $list[$k]['expire_time']=date('Y-m-d',$v['expire_time']);
            }

            $res['list'] = $list;
            $res['total'] = $total;
            return $this->success('success','',$res);
        }

        return view();
    }
    //添加买号页面
    public function add_buyno(){
        $where_else_state['state']=array('neq',3);
        $buyno= Db::name('user_buyno')
            ->where('uid',$this->id)
            ->where($where_else_state)
            ->order('id DESC')
            ->field('wwid,id,creat_time,addressname,addresspro,addresscity,addressarea,addresstext,alipayimg,addressphone,state,note')
            ->select();
        $this->assign('buyno',$buyno);
        return view();
    }
    //添加买号
    public function addbuyno(){
        $data=input();
        $data['wwid']=trim($data['wwid']);
        $where_except_buyno['state']=array('neq',3);
        $buyno_count= Db::name('user_buyno')
            ->where('uid',$this->id)
            ->where($where_except_buyno)
            ->count();
        if($buyno_count>=3){
            return $this->error('您最多只能添加三个买手号!');
        }
        $path = 'uploads' . DS . 'info' . DS;
        $res = aliyunOss::uploadBase64($data['img1'],$path);
        $res2 = aliyunOss::uploadBase64($data['img2'],$path);
        $res3 = aliyunOss::uploadBase64($data['img3'],$path);
        $res4 = aliyunOss::uploadBase64($data['img4'],$path);
//        $res = Img::base64_image_content($data['img1'],'./uploads/info/');
//        if($res['code']==0){
//            return $this->error('旺旺档案截图'.$res['data']);
//        }
//        $res2 = Img::base64_image_content($data['img2'],'./uploads/info/');
//        if($res2['code']==0){
//            return $this->error('IP地址截图'.$res2['data']);
//        }
//        $res3 = Img::base64_image_content($data['img3'],'./uploads/info/');
//        if($res3['code']==0){
//            return $this->error('身份证正面截图'.$res3['data']);
//        }
//        $res4 = Img::base64_image_content($data['img4'],'./uploads/info/');
//        if($res4['code']==0){
//            return $this->error('支付宝实名认证'.$res4['data']);
//        }
        $result = $this->validate($data,'Addbuyno');
        if(true !== $result){
            return $this->error($result);
        }
        if($data['provinceValue2'] == "请选择省"){
            return $this->error('请选择旺旺常用登陆地');
        }
        if($data['provinceValue'] == "请选择省"){
            return $this->error('请选择收货地址');
        }
        $addressphone=Db::name('user_buyno')->where($where_except_buyno)->where('addressphone',$data['mobile'])->find();
        if($addressphone){
            return $this->error('此收货人手机号码已被注册。');
        }
        $code=session('code');
        $send_mobile=session('mobile');
        $code_time=session( 'code_time');
        $code_time=$code_time+20*60;    //短信验证码时间为20分钟
        $time=time();
        if($data['mobile'] != $send_mobile){
            return $this->error('请输入获取验证码的手机号');
        }

        if($data['dxyzm'] != $code){
            return $this->error('您输入的验证码不正确');
        }
        if($code_time<$time){
            return $this->error('验证码已失效');
        }

        $buyno=[
            'uid'=>$this->id,
            'wwid'=>$data['wwid'],
            'wwpro'=>$data['provinceValue2'],
            'wwcity'=>$data['cityValue2'],
            'addressname'=>$data['shouHuoNameValue'],
            'addresspro'=>$data['provinceValue'],
            'addresscity'=>$data['cityValue'],
            'addressarea'=>$data['blockValue'],
            'addresstext'=>$data['addressValue'],
            'addressphone'=>$data['mobile'],
            'alipayname'=>$data['renZhengValue'],
            'state'=>0,
            'creat_time'=>time(),
            'wwdaimg'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res,
            'ipimg'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res2,
            'idcardimg'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res3,
            'alipayimg'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res4,
            'star'=>1,
            'detail_address'=>$data['provinceValue'].'-'.$data['cityValue'].'-'.$data['blockValue'].'-'.$data['addressValue'],
        ];
        Db::name('user_buyno')->insertGetId($buyno);
        session('code',null);//清除验证码session
        session('code_time',null);//清除验证码有效时间session
        session('mobile',null);//清除验证码有效时间session
        return $this->success('绑定成功正在跳转','My/add_buyno');
        //return $data;
    }

    //修改买号
    public function edit_buyno(){

        $id=input('param.id');//id
        $buyno= Db::name('user_buyno')->where('id',$id)->find();
        $this->assign('buyno',$buyno);
        return view();
    }
    public function editbuyno()
    {
        $data = input();
        $buyno = Db::name('user_buyno')->where('id', $data['id'])->find();
        $path = 'uploads' . DS . 'info' . DS;
        if (empty($data['img1'])) {
            $res['data'] = $buyno['wwdaimg'];
        } else {
            $res = aliyunOss::uploadBase64($data['img1'],$path);
//            $res = Img::base64_image_content($data['img1'], './uploads/info/');
//            if ($res['code'] == 0) {
//                return $this->error('旺旺档案截图' . $res['data']);
//            }
        }
        if(empty($data['img2'])) {
            $res2['data'] = $buyno['ipimg'];
        } else {
            $res2 = aliyunOss::uploadBase64($data['img2'],$path);
//            $res2 = Img::base64_image_content($data['img2'],'./uploads/info/');
//            if($res2['code']==0){
//                return $this->error('IP地址截图'.$res2['data']);
//            }
        }
        if(empty($data['img3'])) {
            $res3['data'] = $buyno['idcardimg'];
        } else {
            $res3 = aliyunOss::uploadBase64($data['img3'],$path);
//            $res3 = Img::base64_image_content($data['img3'],'./uploads/info/');
//            if($res3['code']==0){
//                return $this->error('身份证正面截图'.$res3['data']);
//            }
        }
        if(empty($data['img4'])) {
            $res4['data'] = $buyno['alipayimg'];
        } else {
            $res4 = aliyunOss::uploadBase64($data['img4'],$path);
//            $res4 = Img::base64_image_content($data['img4'], './uploads/info/');
//            if ($res4['code'] == 0) {
//                return $this->error('支付宝实名认证' . $res4['data']);
//            }
        }
        $result = $this->validate($data,'Addbuyno');
        if(true !== $result){
            return $this->error($result);
        }
        $code=session('code');
        $code_time=session( 'code_time');
        $code_time=$code_time+20*60;    //短信验证码时间为20分钟
        $time=time();
        if($data['dxyzm'] != $code){
            return $this->error('您输入的验证码不正确');
        }
        $session_mobile=session('mobile');
        if($session_mobile != $data['mobile']){
            return $this->error('请输入获取验证码的手机号');
        }
        if($code_time<$time){
            return $this->error('验证码已失效');
        }
        $buyno=[
            'uid'=>$this->id,
            'wwid'=>$data['wwid'],
            'wwpro'=>$data['provinceValue2'],
            'wwcity'=>$data['cityValue2'],
            'addressname'=>$data['shouHuoNameValue'],
            'addresspro'=>$data['provinceValue'],
            'addresscity'=>$data['cityValue'],
            'addressarea'=>$data['blockValue'],
            'addresstext'=>$data['addressValue'],
            'addressphone'=>$data['mobile'],
            'alipayname'=>$data['renZhengValue'],
            'state'=>0,
            'creat_time'=>time(),
            'wwdaimg'=>$res['data'],
            'ipimg'=>$res2['data'],
            'idcardimg'=>$res3['data'],
            'alipayimg'=>$res4['data'],
            'detail_address'=>$data['provinceValue'].'-'.$data['cityValue'].'-'.$data['blockValue'].'-'.$data['addressValue'],
        ];
        Db::name('user_buyno')->where('id',$data['id'])->update($buyno);
        session('code',null);//清除验证码session
        session('code_time',null);//清除验证码有效时间session
        return $this->success('修改成功提交审核','My/add_buyno');
    }

    //基本信息information
    public function information(){
        $editpass=input('param.editpass');//顶部导航
        $this->assign('editpass',$editpass);
        return view();
    }
    //修改手机号码
    public function editphone(){
        $data=input();
        $result = $this->validate($data,'Editphone');
        if(true !== $result){
            return $this->error($result);
        }
        $code=session('code');
        $code_time=session( 'code_time');
        $code_time=$code_time+20*60;    //短信验证码时间为20分钟
        $time=time();
        if(empty($data['dxyzm'])){
            return $this->error('新手机号码验证码不能为空');
        }
        if($data['dxyzm'] != $code){
            return $this->error('您输入的验证码不正确');
        }
        $session_mobile=session('mobile');
        if($session_mobile != $data['mobile']){
            return $this->error('请输入获取验证码的手机号');
        }
        if($code_time<$time){
            return $this->error('验证码已失效');
        }
        $this->id =Session::get('id');//获取用户ID
        $users = db('users')->where('id', $this->id)->field('id,mobile,pay_pwd')->find();
        if($data['oldphone'] != $users['mobile']){
            return $this->error('您输入的原手机号码不一致,请检查后重新输入');
        }
        if($data['oldphone']  == $data['mobile']){
            return $this->error('您输入的原手机号码与新号码一致,请检查后重新输入');
        }
        if(empty($users['pay_pwd'])){
            return $this->error('请先添加支付密码');
        }
        if($users['pay_pwd'] !== md5($data['pay_pwd'])){
            return $this->error('您输入支付密码错误,请检查后重新输入');
        }
        $newphone=[
            'mobile'=>$data['mobile']
        ];
        db('users')->where('id',$users['id'])->update($newphone);
        return $this->success('修改成功正在跳转','My/information');
        //return $this->error($data['oldphone']);
    }

    //添加，修改支付密码
    public function edit_pay_pwd(){
        $data=input();
        $code=session('code');
        $session_mobile=session('mobile');
        $code_time=session( 'code_time');
        $code_time=$code_time+20*60;    //短信验证码时间为20分钟
        $time=time();
        if($data['dxyzm'] != $code){
            return $this->error('您输入的验证码不正确');
        }
        $session_mobile=session('mobile');
        if($session_mobile != $data['mobile']){
            return $this->error('请输入获取验证码的手机号');
        }
        if($code_time<$time){
            return $this->error('验证码已失效');
        }
        if($session_mobile != $data['mobile']){
            return $this->error('手机号码输入不一致,请检查后重新输入');
        }
        $this->id =Session::get('id');//获取用户ID
        $users = db('users')->where('id', $this->id)->field('id,mobile,pay_pwd')->find();
        if(!empty($users['pay_pwd'])){
            if($users['pay_pwd'] == md5($data['pay_pwd'])){
                return $this->error('新密码不能与旧密码一致,请检查后重新输入');
            }
        }
        if($data['pay_pwd'] !== $data['pay_pwd2']){
            return $this->error('新支付密码与确认密码不一致,请检查后重新输入');
        }
        if($users['mobile'] != $data['mobile']){
            return $this->error('手机号码输入错误,请检查后重新输入');
        }
        $newpaypwd=[
            'pay_pwd'=>md5($data['pay_pwd'])
        ];
        db('users')->where('id',$users['id'])->update($newpaypwd);
        return $this->success('修改成功正在跳转','My/information');
    }

    //修改登录密码
    public function edit_login_pwd(){
        $data=input();
        $code=session('code');
        $code_time=session( 'code_time');
        $code_time=$code_time+20*60;    //短信验证码时间为20分钟
        $time=time();
        if($data['dxyzm'] != $code){
            return $this->error('您输入的验证码不正确');
        }
        $session_mobile=session('mobile');
        if($session_mobile != $data['mobile']){
            return $this->error('请输入获取验证码的手机号');
        }
        if($code_time<$time){
            return $this->error('验证码已失效');
        }
        $this->id =Session::get('id');//获取用户ID
        $users = db('users')->where('id', $this->id)->field('id,mobile,login_pwd')->find();
        if(empty($data['oldloginpwd'])){
            return $this->error('请输入原登录密码');
        }else if($users['login_pwd'] !== md5($data['oldloginpwd'])){
            return $this->error('您输入的原登录密码错误,请检查后重新输入');
        }else if($users['login_pwd'] == md5($data['login_pwd'])){
            return $this->error('新密码不能与旧密码一致,请检查后重新输入');
        }
        if($data['login_pwd'] !== $data['login_pwd2']){
            return $this->error('新登录密码与确认密码不一致,请检查后重新输入');
        }
        if($users['mobile'] != $data['mobile']){
            return $this->error('手机号码输入错误,请检查后重新输入');
        }
        $newloginpwd=[
            'login_pwd'=>md5($data['login_pwd'])
        ];
        db('users')->where('id',$users['id'])->update($newloginpwd);
        return $this->success('修改成功正在跳转','My/information');
    }
    //修改用户昵称
    public function editusername(){
        $data=input();
        $this->id =Session::get('id');//获取用户ID
        $users = db('users')->where('id', $this->id)->field('id,mobile,username')->find();
        $newusername=[
            'username'=>$data['username']
        ];
        db('users')->where('id',$users['id'])->update($newusername);
        return $this->success('修改成功正在跳转','My/information');
    }

    //账户提现
    public function withdrawal(){
        $withdrawl=db('user_bank')->where('user_id',$this->id)->find();
        $bankstate=Db::name('user_bank')->where('user_id',$this->id)->find();
        if(!empty($withdrawl)){
            $this->assign('withdeawl',$withdrawl);
            $choose_bank=db('bank')->where('id',$withdrawl['bank_id'])->find();
            $this->assign('choose_bank',$choose_bank);
        }
        $bank=db('bank')->select();
        $this->assign('bank',$bank);
        $this->assign('withdrawl',$withdrawl);
        return view();
    }

    //添加银行卡
    public function add_bank_card(){
        $data=input();
        $data['bank_no']=trim($data['bank_no']);
        $path = 'uploads' . DS . 'info' . DS;
        $res = aliyunOss::uploadBase64($data['idcard_img_a'],$path);
        $res2 = aliyunOss::uploadBase64($data['idcard_img_b'],$path);
//        $res = Img::base64_image_content($data['idcard_img_a'],'./uploads/info/');
//        if($res['code']==0){
//            return $this->error('身份证正面截图'.$res['data']);
//        }
//        $res2 = Img::base64_image_content($data['idcard_img_b'],'./uploads/info/');
//        if($res2['code']==0){
//            return $this->error('身份证反面截图'.$res2['data']);
//        }
//        $bank_user=Db::name('user_bank')->where('bank_user',$data['name'])->count();
//        if($bank_user >=1){
//            return $this->error('银行卡每个用户只能添加一张。');
//        } //银行卡不能同名
        $count_user=Db::name('user_bank')->where('user_id',$this->id)->count();
        if($count_user>1){
            return $this->error('银行卡每个用户只能添加一张。');
        }
        if(mb_strlen($data['bank_no'],"utf-8") > 21 || mb_strlen($data['bank_no'],"utf-8") < 13)return $this->error('卡号长度只能在13到21之间');
        $bank=db('bank')->where('name',$data['bank_id'])->find();
        $message=[
            'user_id'=>$this->id,
            'bank_user'=>$data['name'],
            'bank_id'=>$bank['id'],
            'bank_no'=>$data['bank_no'],
            'province'=>$data['province'],
            'city'=>$data['city'],
            'branch_name'=>$data['branch_name'],
            'mobile'=>$data['mobile'],
            'idcard'=>$data['idcard'],
            'create_time'=>time(),
            'idcard_img_a'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res,
            'idcard_img_b'=>'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res2,
        ];
        Db::name('user_bank')->insertGetId($message);
        return $this->success('绑定成功，提交审核，正在跳转');
    }

    //修改银行卡
    public function edit_bank_card (){
        $data=input();
        if(mb_strlen($data['bank_no'],"utf-8") > 21 || mb_strlen($data['bank_no'],"utf-8") < 13)return $this->error('卡号长度只能在13到21之间');
        $bank_user=Db::name('user_bank')->where('bank_user',$data['name'])->find();
        $bank=db('bank')->where('name',$data['bank_id'])->find();
        $message=[
            'user_id'=>$this->id,
            'bank_id'=>$bank['id'],
            'bank_no'=>$data['bank_no'],
            'province'=>$data['province'],
            'city'=>$data['city'],
            'branch_name'=>$data['branch_name'],
            'mobile'=>$data['mobile'],
            'idcard'=>$data['idcard'],
            'create_time'=>time(),
            'state'=>0
        ];
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['idcard_img_a'], $result)){
            $path = 'uploads' . DS . 'info' . DS;
            $res = aliyunOss::uploadBase64($data['idcard_img_a'],$path);
//            $res = Img::base64_image_content($data['idcard_img_a'],'./uploads/info/');
//            if($res['code']==0){
//                return $this->error('身份证正面截图'.$res['data']);
//            }
            $message1['idcard_img_a'] = 'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res;
            $end1=Db::name('user_bank')->where('id',$bank_user['id'])->update($message1);
            if($end1){
            }else{
                return $this->error($end1);
            }
        }
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['idcard_img_b'], $result)){
//            $res2 = Img::base64_image_content($data['idcard_img_b'],'./uploads/info/');
//            if($res2['code']==0){
//                return $this->error('身份证反面截图'.$res2['data']);
//            }
            $path = 'uploads' . DS . 'info' . DS;
            $res2 = aliyunOss::uploadBase64($data['idcard_img_b'],$path);
            $message2['idcard_img_b'] =  'http://tfkzpic.oss-cn-hangzhou.aliyuncs.com/'.$res2;
            $end2=Db::name('user_bank')->where('id',$bank_user['id'])->update($message2);
            if($end2){
            }else{
                return $this->error($end2);
            }
        }
        $end=Db::name('user_bank')->where('id',$bank_user['id'])->update($message);
        if($end){
            return $this->success('修改成功，提交审核，正在跳转');
        }else{
            return $this->error($end);
        }
    }

    public function check_mobile(){
        $mobile=input('mobile');
        $where_except_buyno['state']=array('neq',3);
        $res=db('user_buyno')->where($where_except_buyno)->where('addressphone',$mobile)->find();
        if($res){
            $this->error('该手机号码已经被注册');
        }else{
            $this->success('该手机号码可以使用');
        }
    }

    /**
     * @notes 验证手机号唯一
     * @date 2019/10/25
     * @time 15:35
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bankMobile(Request $request){
        $data = $request->param();
        $where['user_id'] = ['neq',$this->id];
        $where['mobile'] = $data['mobile'];
        if(Db::name('user_bank')->where($where)->find())return $this->error('手机号已被使用，请更换');
        return $this->success('手机号没有被使用');
    }

}
