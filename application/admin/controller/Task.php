<?php


namespace app\admin\controller;

use app\common\controller\Img;
use app\common\model\SellerTask;
use app\common\model\UserTask;
use app\common\model\ReviewTask;
use app\seller\model\GoodsKeyWorld;
use think\Controller;
use think\Db;
use think\Cache;
use think\Exception;
use think\Request;
use think\Session;
use PHPExcel_IOFactory;
use app\seller\controller\Phpexcel;
use app\seller\model\ReviewTask as ReviewTasks;

class Task extends Base
{
    /**
     * 买家购买管理
     */
    public function PlatformCounterpayment()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            $where['state'] = array('neq', 2);
            if (isset($date['id']) && $date['id']) {
                //$where['task_number'] = $date['id'];
                $where['task_number'] =['like','%'.trim($date['id']).'%'];
            }
            if (isset($date['wwid']) && $date['wwid']) {
                $where['user_buyno_wangwang'] =['like','%'.trim($date['wwid']).'%'];
                //$where['user_buyno_wangwang'] = $date['wwid'];
            }
            if (isset($date['username']) && $date['username']) {
                $where_name['username'] = ['like', '%' . trim($date['username']) . '%'];
                $uid = db('users')->where($where_name)->column('id');
                $where['user_id'] = ['in',$uid];
            }

            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }
            if (isset($date['state']) && $date['state']!='') {
                $where['delivery_state'] =$date['state'];
            }
            if (isset($date['state']) && $date['state']==4) {
                $where['delivery_state'] =0;
                $where['state'] =3;
            }
            $count = UserTask::where($where)->count('id');
            $buyno_list = UserTask::where($where)->limit(($page - 1) * $limit, $limit)->order('create_time desc')->select();
            if ($buyno_list) $buyno_list = $buyno_list->toArray();
            foreach ($buyno_list as $K => &$v) {
                if($v['is_shengji']==1){
                    $v['goods_num'] = array_sum(json_decode($v['goods_num']));
                    $v['goods_unit_price'] = array_sum(json_decode($v['goods_unit_price']));
                    $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
                    if ($terminal == 1) {
                        $v['terminal'] = "pc";
                    } else {
                        $v['terminal'] = "手机";
                    }
                    $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
                    $shop_id = db('seller_task')->where('id', $v['seller_task_id'])->value('shop_id');
                    $v['shop'] = db('shop')->where('id', $shop_id)->find();
                    $v['seller_info'] = db('seller')->where('id', $seller_id)->find();
                    $v['user_info'] = db('users')->where('id', $v['user_id'])->find();
                }elseif ($v['is_shengji']==2){
                    $v['goods_num'] = db('task_goods')->where(['task_id'=>$v['seller_task_id']])->sum('num');
                    $v['goods_unit_price'] = db('task_goods')->where(['task_id'=>$v['seller_task_id']])->sum('price');;
                    $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
                    if ($terminal == 1) {
                        $v['terminal'] = "pc";
                    } else {
                        $v['terminal'] = "手机";
                    }
                    $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
                    $shop_id = db('seller_task')->where('id', $v['seller_task_id'])->value('shop_id');
                    $v['shop'] = db('shop')->where('id', $shop_id)->find();
                    $v['seller_info'] = db('seller')->where('id', $seller_id)->find();
                    $v['user_info'] = db('users')->where('id', $v['user_id'])->find();
                }
            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $buyno_list]);
        }
        return view('PlatformCounterpayment/PlatformCounterpayment');
    }

    /**
     * 导入
     */
    public function import()
    {
        set_time_limit(0);
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'task_data');
            if ($info) {
                // 成功上传后 获取上传信息
                // 输出 jpg
                $type=$info->getExtension();
                if($type!="xls"){
                    return $this->error('只能上传xls格式！');
                }
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $route=$info->getSaveName();
                $data=$this->excelToArray('uploads' . DS . 'task_data' . DS .$route);
                $err_num=0;
                $succ_num=0;
                $update = [];
                $arr = [];
                foreach ($data as $key =>$v){
                    if(trim($v['0'])){
                    $info=db('user_task')->where(['id'=>trim($v['0']),'state'=>3])->find();
                    if(!$info){
                        $err_num=$err_num+1;
                        continue;
                    }
                    if($v['7']=='' ||$v['8']==''){
                        $err_num=$err_num+1;
                        continue;
                    }
                    $update['id']=trim($v['0']); //订单id
                    $update['shop_name']=trim($v['2']); //发货人
                    $update['shipping_address']=trim($v['3']); //发货地址
                    $update['addressname']=trim($v['4']); //收货人
                    $update['addressphone']=trim($v['5']); //收货手机号
                    $update['address']=trim($v['6']); //收货地址
                    $update['delivery']=trim($v['7']);
                    $update['delivery_num']=trim($v['8']);
                    $update['delivery_state']=1;
                    $update['delivery_time']=time();
                    $arr[] = $update;
                    $succ_num++;
                    }
                }
                $i=0;
                $update_list = [];
                $num=0;
                $key=0;
                for($i;$i < count($arr);$i++){
                    $update_list[$key][] = $arr[$i];
                    $num++;
                    if($num == 500){
                        $num = 0;
                        $key++;
                    }
                }
                $userTaskModel = new UserTask();
                foreach ($update_list as $item){
                    try{
                        Db::startTrans();
                        $userTaskModel->saveAll($item);
                        Db::commit();
                    }catch (Exception $e){
                        Db::rollback();
                    }
                }
                $res1=admin_log("单号批量导入", "管理员{$this->admin_info['user_name']}操作:单号批量导入");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
                $this->success("成功{$succ_num},失败{$err_num}");
            } else {
                // 上传失败获取错误信息
                return $this->error($file->getError());
            }
        }
    }
    /**
     * 导入
     */
    public function import1()
    {
        set_time_limit(0);
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'task_data');
            if ($info) {
                // 成功上传后 获取上传信息
                // 输出 jpg
                $type=$info->getExtension();
                if($type!="xls"){
                    return $this->error('只能上传xls格式！');
                }
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $route=$info->getSaveName();
                $data=$this->excelToArray('uploads' . DS . 'task_data' . DS .$route);
                $err_num=0;
                $succ_num=0;
                $taskids=[];
                foreach ($data as $key =>$v){
                    try{
                        Db::startTrans();
                        $update['shop_name']=trim($v['1']); //发货人
                        $update['shipping_address']=trim($v['2']); //发货地址
                        $update['addressname']=trim($v['3']); //收货人
                        $update['addressphone']=trim($v['4']); //收货手机号
                        $update['address']=trim($v['5']); //收货地址
                        $update['delivery']=trim($v['6']);
                        $update['delivery_num']=trim($v['7']);
                        $update['delivery_state']=1;
                        $update['delivery_time']=time();
                        $info=db('user_task')->where(['task_number'=>trim($v['0']),'state'=>3])->find();
                        if(!$info){
                            $err_num=$err_num+1;
                            continue;
                        }
                        if($v['6']=='' ||$v['7']==''){
                            $err_num=$err_num+1;
                            continue;
                        }

                        $res=db('user_task')->where(['task_number'=>trim($v['0'])])->update($update);
                        if($res){
                            $taskids[$key]=trim($v['0']);
                            $succ_num=$succ_num+1;
                        }else{
                            $err_num=$err_num+1;
                        }
                        Db::commit();
                    }catch (Exception $e){
                        Db::rollback();
                    }
                }
                $datajson=json_encode($taskids);
                //$res1=admin_log("单号批量导入", "管理员{$this->admin_info['user_name']}操作:任务信息{$datajson}");
                $res1=admin_log("单号批量导入", "管理员{$this->admin_info['user_name']}操作:单号批量导入");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
                $this->success("成功{$succ_num},失败{$err_num}");
            } else {
                // 上传失败获取错误信息
                return $this->error($file->getError());
            }
        }
    }

    function excelToArray($filename){
        /** Include PHPExcel_IOFactory */
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.IOFactory");
        // $obj = new \PHPExcel();

        if (!file_exists($filename)) {
            exit("文件".$filename."不存在");
        }
        $objPHPExcel = PHPExcel_IOFactory::load($filename);
        //开始读取上传到服务器中的Excel文件，返回一个二维数组
        $dataArray = $objPHPExcel->getSheet(0)->toArray();
        $sheet_count = $objPHPExcel->getSheetCount();
        for ($s = 0; $s < $sheet_count; $s++)
        {
            $currentSheet = $objPHPExcel->getSheet($s);// 当前页
            $row_num = $currentSheet->getHighestRow();// 当前页行数
            $col_max = $currentSheet->getHighestColumn(); // 当前页最大列号

            // 循环从第二行开始，第一行往往是表头
            for($i = 3; $i <= $row_num; $i++)
            {
                $cell_values = array();
                for($j = 'A'; $j <= $col_max; $j++)
                {
                    $address = $j . $i; // 单元格坐标
                    $cell_values[] = $currentSheet->getCell($address)->getFormattedValue();
                }
                $import_data[]=$cell_values;

            }
            // 看看数据
            return $import_data;
        }
    }


    /**
     * 淘宝任务导出
     */
    public function task_export()
    {
        set_time_limit(0);
        $date = input();
        $where = [];
        $where['status'] = array('in', [2,3,6]);
        if (isset($date['time']) && $date['time']) {
            $date['time']=urldecode($date['time']);
            $time = explode("-", $date['time']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['create_time'] = ['between', [$time1, $time2]];
        }
        if (isset($date['wwid']) && $date['wwid']) {
            $where_wangwang['wangwang'] = ['like', '%' . trim($date['wwid']) . '%'];
            $shop_id = db('shop')->where($where_wangwang)->column('id');
            $where['shop_id']=['in',$shop_id];
        }
        if (isset($date['seller_name']) && $date['seller_name']) {
            $date['seller_name']=urldecode($date['seller_name']);
            $where_seller_name['seller_name'] = ['like', '%' . trim($date['seller_name']) . '%'];
            $seller_id = db('seller')->where($where_seller_name)->column('id');
            $where['seller_id']=['in',$seller_id];
        }
        $dd=[];
        $count = SellerTask::where($where)->count('id');
        $buyno_list = SellerTask::where($where)->select();
        if ($buyno_list) $buyno_list = $buyno_list->toArray();
        foreach ($buyno_list as $k => $v) {
            $dd[$k]['rand_num'] = $v['rand_num'];//任务编号id
            $dd[$k]['task_number'] = $v['task_number'];//任务编号
            $dd[$k]['seller_name'] = $v['seller_id']['seller_name'];//用户名
            $dd[$k]['shop_name'] = $v['shop_name'];//卖家店铺名(发货人)
            $dd[$k]['wangwang'] = $v['shop_id']['wangwang'];//卖家旺旺
            $dd[$k]['task_type'] = $v['task_type'];//订单类型
            $dd[$k]['terminal'] = $v['terminal'].':'.$v['num'];//刷单量
            $dd[$k]['numxq'] = '已接'.':'.$v['num']-$v['complete_num']-$v['incomplete_num'].'完成'.':'.$v['num'];
            $dd[$k]['goods_number'] = $v['goods_number'];
            $dd[$k]['goods_z_price'] = $v['goods_z_price'];
            $dd[$k]['service_price'] = $v['service_price']*$v['num'];//基础佣金
            $dd[$k]['refund_service_price'] = $v['refund_service_price']*$v['num'];//返款服务费
            $dd[$k]['img_praise_fee'] = $v['img_praise_fee']*$v['num'];//图片
            $dd[$k]['praise_fee'] = $v['praise_fee']*$v['num'];//文字
            $dd[$k]['zhitongche'] = ($v['task_type']=="直通车"?2:0)*$v['num'];//直通车
            $dd[$k]['video_praise_fee'] = $v['video_praise_fee']*$v['num'];//视频好评
            $dd[$k]['goods_more_fee'] = $v['goods_more_fee']*$v['num'];//多件商品
            $dd[$k]['add_reward'] = $v['add_reward']*$v['num'];//加赏佣金
            $dd[$k]['next_day_fee'] = $v['next_day_fee']*$v['num'];//隔天付款
            $dd[$k]['cycle'] = $v['cycle']*$v['num'];//延长买手周期
            $dd[$k]['timing_pay'] = $v['timing_pay']*$v['num'];//定时付款
            $dd[$k]['timing_publish_pay'] = $v['timing_publish_pay']*$v['num'];//定时发布
            $dd[$k]['union_interval'] = $v['union_interval']*$v['num'];//任务接单间隔
            $dd[$k]['phone_fee'] = $v['phone_fee']*$v['num'];//移动端加成
            $dd[$k]['is_free_shiping'] = $v['is_free_shiping'];
            $dd[$k]['create_time'] = $v['create_time'];//发布时间
            $status=array(
                '1'=>"未支付",
                '2'=>"已支付，待审核",
                '3'=>"已通过",
                '4'=>"已拒绝",
                '5'=>"已取消",
                '6'=>"已完成",
            );
            $dd[$k]['task_status'] =$status[$v['status']];
            $dd[$k]['tjuser'] =$v['seller_id']['tjuser'];
        }
        //dump($dd);exit;
        $title = ['任务编号id','任务编号', '用户名','店铺名','商家旺旺ID','订单类型','刷单量','已接/完成','产品数量'
        ,'商品售价','基础佣金','返款服务费','图片好评','文字好评','直通车','视频好评','多件商品','加赏佣金','隔天付款','延长买手周期','定时付款','定时发布',
            '任务接单间隔','移动端加成','邮费','发布时间','支付状态','来源用户ID'];
        Phpexcel::exportExcel($title, $dd, '淘宝任务导出表');
        // return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
    }

    /**
     * 导出
     */
    public function export()
    {
        $date = input();
        $where = [];
        $where['state'] = array('eq', 3);
        $where['delivery_state'] = array('eq', 0);
        if (isset($date['id']) && $date['id']) {
            $where['task_number'] = trim($date['id']);
        }
        if (isset($date['wwid']) && $date['wwid']) {
            $where['user_buyno_wangwang'] = trim($date['wwid']);
        }
        if (isset($date['time']) && $date['time']) {
            $date['time']=urldecode($date['time']);
            $time = explode(" - ", $date['time']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['create_time'] = ['between', [$time1, $time2]];
        }

//        if (isset($date['state']) && $date['state']!='' &&$date['state']!=88) {
//                $where['delivery_state'] =$date['state'];
//        }

        $dd=[];
        $count = UserTask::where($where)->count('id');
        $buyno_list = UserTask::where($where)->select();
        if ($buyno_list) $buyno_list = $buyno_list->toArray();
        foreach ($buyno_list as $k => $v) {
            if($v['is_shengji']==1){
                $buyno_list[$k]['goods_num'] = array_sum(json_decode($v['goods_num']));
                $buyno_list[$k]['goods_unit_price'] = array_sum(json_decode($v['goods_unit_price']));
            }elseif ($v['is_shengji']==2){
                $buyno_list[$k]['goods_num'] =db('task_goods')->where(['task_id'=>$v['seller_task_id']])->sum('num');
                $buyno_list[$k]['goods_unit_price'] =db('task_goods')->where(['task_id'=>$v['seller_task_id']])->sum('price');
            }
            $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
            if ($terminal == 1) {
                $buyno_list[$k]['terminal'] = "pc";
            } else {
                $buyno_list[$k]['terminal'] = "手机";
            }
            $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
            $shop_id = db('seller_task')->where('id', $v['seller_task_id'])->value('shop_id');
            $buyno_list[$k]['shop'] = db('shop')->where('id', $shop_id)->find();
            $buyno_list[$k]['seller_info'] = db('seller')->where('id', $seller_id)->find();
            $buyno_list[$k]['user_info'] = db('users')->where('id', $v['user_id'])->find();

            $dd[$k]['id'] = $v['id'];//id
            $dd[$k]['task_number'] = $v['task_number'];//任务编号
            // $dd[$k]['seller_name']=$buyno_list[$k]['seller_info']['seller_name'];//卖家用户名
            $dd[$k]['shop_name'] = $v['shop_name'];//卖家店铺名(发货人)
            //$dd[$k]['wangwang'] = $buyno_list[$k]['shop']['wangwang'];//卖家旺旺
            //$dd[$k]['buy_name'] = $buyno_list[$k]['user_info']['username'];//买家用户名
            // $dd[$k]['buy_wangwang'] = $v['user_buyno_wangwang'];//买家旺旺
            $dd[$k]['shipping_address'] = $v['shipping_address'];
            $dd[$k]['addressname'] = $v['addressname'];
            $dd[$k]['addressphone'] = $v['addressphone'];
            $dd[$k]['address'] = $v['address'];
            if($v['delivery_status']==0){
                $dd[$k]['delivery'] = $v['delivery'];
                $dd[$k]['delivery_num'] = $v['delivery_num'];
            }else{
                $dd[$k]['delivery'] = "无需物流";
                $dd[$k]['delivery_num'] = "无需物流";
            }
            $dd[$k]['code'] = $v['code'];
        }
        $title = ['任务id','任务编号', '卖家店铺名(发货人)','发货地址','收货人姓名','收货人号码','收货地址','快递公司','快递单号','发货仓'];
        Phpexcel::exportExcel($title, $dd, '买家任务导出表');
        // return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
    }


    /**
     * 导出
     */
    public function export666()
    {
        $date = input();
        $where = [];
        $where['state'] = array('eq', 3);
        $where['delivery_state'] = array('eq', 0);
        if (isset($date['id']) && $date['id']) {
            $where['task_number'] = trim($date['id']);
        }
        if (isset($date['wwid']) && $date['wwid']) {
            $where['user_buyno_wangwang'] = trim($date['wwid']);
        }
        if (isset($date['time']) && $date['time']) {
            $date['time']=urldecode($date['time']);
            $time = explode(" - ", $date['time']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['create_time'] = ['between', [$time1, $time2]];
        }

//        if (isset($date['state']) && $date['state']!='' &&$date['state']!=88) {
//                $where['delivery_state'] =$date['state'];
//        }

        $dd=[];
        $count = UserTask::where($where)->count('id');
        $buyno_list = UserTask::where($where)->select();
        if ($buyno_list) $buyno_list = $buyno_list->toArray();
        foreach ($buyno_list as $k => $v) {
            $buyno_list[$k]['goods_num'] = array_sum(json_decode($v['goods_num']));
            $buyno_list[$k]['goods_unit_price'] = array_sum(json_decode($v['goods_unit_price']));
            $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
            if ($terminal == 1) {
                $buyno_list[$k]['terminal'] = "pc";
            } else {
                $buyno_list[$k]['terminal'] = "手机";
            }
            $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
            $shop_id = db('seller_task')->where('id', $v['seller_task_id'])->value('shop_id');
            $buyno_list[$k]['shop'] = db('shop')->where('id', $shop_id)->find();
            $buyno_list[$k]['seller_info'] = db('seller')->where('id', $seller_id)->find();
            $buyno_list[$k]['user_info'] = db('users')->where('id', $v['user_id'])->find();

            $dd[$k]['id'] = $v['id'];//id
            $dd[$k]['task_number'] = $v['task_number'];//任务编号
            // $dd[$k]['seller_name']=$buyno_list[$k]['seller_info']['seller_name'];//卖家用户名
            $dd[$k]['shop_name'] = $v['shop_name'];//卖家店铺名(发货人)
            //$dd[$k]['wangwang'] = $buyno_list[$k]['shop']['wangwang'];//卖家旺旺
            //$dd[$k]['buy_name'] = $buyno_list[$k]['user_info']['username'];//买家用户名
            // $dd[$k]['buy_wangwang'] = $v['user_buyno_wangwang'];//买家旺旺
            $dd[$k]['shipping_address'] = $v['shipping_address'];
            $dd[$k]['addressname'] = $v['addressname'];
            $dd[$k]['addressphone'] = $v['addressphone'];
            $dd[$k]['address'] = $v['address'];
            if($v['delivery_status']==0){
                $dd[$k]['delivery'] = $v['delivery'];
                $dd[$k]['delivery_num'] = $v['delivery_num'];
            }else{
                $dd[$k]['delivery'] = "无需物流";
                $dd[$k]['delivery_num'] = "无需物流";
            }
        }
        $title = ['任务id','任务编号', '卖家店铺名(发货人)','发货地址','收货人姓名','收货人号码','收货地址','快递公司','快递单号'];
        Phpexcel::exportExcel($title, $dd, '买家任务导出表');
        // return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$buyno_list]);
    }

    /**
     * 平台返款管理
     */
    public function platformReturn()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['task_number']) && $date['task_number']) {
                //$where['task_number'] = $date['task_number'];
                $where['task_number'] = ['like', '%' . trim($date['task_number']) . '%'];
            }
            if (isset($date['wangwang']) && $date['wangwang']) {
                $where['user_buyno_wangwang'] = ['like', '%' . trim($date['wangwang']) . '%'];
                //$where['user_buyno_wangwang'] = $date['wangwang'];
            }
//            if (isset($date['shopname']) && $date['shopname']) {
//                $shop_id= db('shop')->where('shop_name',$date['shopname'])->value('id');
//                $taskid=db('seller_task')->where('shop_id',$shop_id)->column('id');
//                $where['seller_task_id']=['in',$taskid];
//            }
            if (isset($date['shopname']) && $date['shopname']) {
                $where_shopname['shop_name'] = ['like', '%' . trim($date['shopname']) . '%'];
                $taskid=db('seller_task')->where($where_shopname)->column('id');
                $where['seller_task_id']=['in',$taskid];
            }

            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['high_praise_time'] = ['between', [$time1, $time2]];
            }
            if (isset($date['state']) && $date['state']!='') {
                $where['state'] =$date['state'];
            }else{
                $where['state'] =['neq',2];
            }
//            if (isset($date['delivery_state']) && $date['delivery_state']!='') {
//                $where['delivery_state'] =$date['delivery_state'];
//            }

            if (isset($date['state']) && $date['state']==3) {
                $where['delivery_state'] =1;
            }

            $count = UserTask::where($where)->count('id');
            $buyno_list = UserTask::where($where)->limit(($page - 1) * $limit, $limit)->order('high_praise_time desc')->select();
            if ($buyno_list) $buyno_list = $buyno_list->toArray();
            foreach ($buyno_list as $K => &$v) {
                if($v['is_shengji']==1){
                    $v['goods_num'] = array_sum(json_decode($v['goods_num']));
                    $v['goods_unit_price'] = array_sum(json_decode($v['goods_unit_price']));
                    $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
                    if ($terminal == 1) {
                        $v['terminal'] = "pc";
                    } else {
                        $v['terminal'] = "手机";
                    }
                    $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
                    $shop_id = db('seller_task')->where('id', $v['seller_task_id'])->value('shop_id');
                    $v['shop'] = db('shop')->where('id', $shop_id)->find();
                    $v['seller_info'] = db('seller')->where('id', $seller_id)->find();
                    $v['user_info'] = db('users')->where('id', $v['user_id'])->find();

                }elseif ($v['is_shengji']==2){
                    $sj_task_goods = db('task_goods')->where(['task_id' => $v['seller_task_id']])->select();
                    if ($sj_task_goods) $sj_task_goods = $sj_task_goods->toArray();
                    foreach ($sj_task_goods as $k => $goodvo) {
                        $good = db('goods')->where('id', $goodvo['goods_id'])->find();
                        $good['pc_img'] = $good['pc_img'];
                        $good['goods_num'] = $goodvo['num'];
                        $good['goods_unit_price'] = $goodvo['price'];
                        $good['goods_spec'] = $goodvo['goods_spec'];
                        $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
                        if ($terminal == 1) {
                            $v['terminal'] = "pc";
                        } else {
                            $v['terminal'] = "手机";
                        }
                        $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
                        $shop_id = db('seller_task')->where('id', $v['seller_task_id'])->value('shop_id');
                        $v['shop'] = db('shop')->where('id', $shop_id)->find();
                        $v['seller_info'] = db('seller')->where('id', $seller_id)->find();
                        $v['user_info'] = db('users')->where('id', $v['user_id'])->find();
                    }
                }
            }

            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $buyno_list]);
        }
        return view('platformReturn/platformReturn');
    }


    /**
     * 平台返款管理
     */
    public function selectPlatformCounterpayment()
    {
        $data = input();
        if (!$data['id']) {
            return false;
        }

        $task_id = db('user_task')->where('id', $data['id'])->value('seller_task_id');
        if (!$task_id) {
            return false;
        }
        $task = SellerTask::where('id', $task_id)->find();
        if ($task) $task = $task->toArray();
        //$task['shop']=db('shop')->where('id',$task['shop_id']['id'])->find();
        $task['task'] = db('user_task')->where('id', $data['id'])->find();
        if(!$task['task']['keywordimg']){  //默认关键词截图
            $task['task']['keywordimg']="/uploads/timg.jpg";
        }
        $task['task']['step_two_complete'] = $task['task']['step_two_complete'] ? date('m-d H:i:s',$task['task']['step_two_complete']) : '';

        $task['task']['upload_order_time'] = $task['task']['upload_order_time'] ? date('m-d H:i:s',$task['task']['upload_order_time']) : '';
        $task['task']['high_praise_time'] = $task['task']['high_praise_time'] ? date('m-d H:i:s',$task['task']['high_praise_time']) : '';
        if(!$task['task']['chatimg']){  //默认聊天截图
            $task['task']['chatimg']="/uploads/timg.jpg";
        }

        if(!$task['task']['order_detail_img']){  //默认付款截图
            $task['task']['order_detail_img']="/uploads/timg.jpg";
        }

        if(!$task['task']['high_praise_img']){  //默认好评截图
            $task['task']['high_praise_img']="/uploads/timg.jpg";
        }
        $task['user_info'] = db('users')->where('id', $task['task']['user_id'])->find();
        if($task['is_shengji']==1){
            $goods_id = json_decode($task['goods_id']);
            $goods_unit_price = json_decode($task['goods_unit_price']);
            $goods_num = json_decode($task['goods_num']);
            foreach ($goods_id as $k => $value) {
                $good = db('goods')->where('id', $value)->find();
                $goods[] = $good;
                $goods[$k]['task_num'] = $goods_num[$k];
                $goods[$k]['task_price'] = $goods_unit_price[$k];
                $key_world = db('goods_key_world')->where('goods_key_id', $good['goods_key_id'])->column('key_world');
                $goods[$k]['key_world'] = implode(',', $key_world);
                if ($k == 0) {
                    $goods[$k]['type'] = "主商品信息";
                } else {
                    $goods[$k]['type'] = "副商品{$k}信息";
                }
            }
        }elseif ($task['is_shengji']==2) {
            $sj_task_goods = db('task_goods')->where(['task_id' => $task['id']])->select();
            if ($sj_task_goods) $sj_task_goods = $sj_task_goods->toArray();
            foreach ($sj_task_goods as $k => $goodvo) {
                $good = db('goods')->where('id', $goodvo['goods_id'])->find();
                $good['pc_img'] = $good['pc_img'];
                $good['task_num'] = $goodvo['num'];
                $good['task_price'] = $goodvo['price'];
                $good['goods_spec'] = $goodvo['goods_spec'];
                if ($k == 0) {
                    $good['type'] = "主商品信息";
                } else {
                    $good['type'] = "副商品{$k}信息";
                }
                $key_world = db('task_word')->where(['task_id' => $task['id'], 'goods_id' => $goodvo['goods_id']])->column('key_word');
                $good['key_world'] = $key_world;
                $goods[] = $good;
            }
        }
        $task['goods'] = $goods;
        $this->assign('task', $task);
        return view('PlatformCounterpayment/selectPlatformCounterpayment');
    }

    //淘宝任务管理
    public function Task()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            $where['status'] = array('neq', 1);
            $where['pay_state'] =1;
            if (isset($date['id']) && $date['id']) {
                //$where['task_number'] = $date['id'];
                $where['task_number'] = ['like', '%' . trim($date['id']) . '%'];
            }
            if (isset($date['rand_num']) && $date['rand_num']) {
                $where['rand_num'] =trim($date['rand_num']);
                //$where['rand_num'] = ['like', '%' . trim($date['rand_num']) . '%'];
            }

            if (isset($date['wwid']) && $date['wwid']) {
                $where_wangwang['wangwang'] = ['like', '%' . trim($date['wwid']) . '%'];
                $shop_id = db('shop')->where($where_wangwang)->column('id');
                $where['shop_id']=['in',$shop_id];
            }

//            if (isset($date['wwid']) && $date['wwid']) {
//                $where['shop_id'] = db('shop')->where('wangwang',$date['wwid'])->value('id');
//            }

            if (isset($date['seller_name']) && $date['seller_name']) {
                $where_seller_name['seller_name'] = ['like', '%' . trim($date['seller_name']) . '%'];
                $seller_id = db('seller')->where($where_seller_name)->column('id');
                $where['seller_id']=['in',$seller_id];
            }

//            if (isset($date['seller_name']) && $date['seller_name']) {
//                $where['seller_id'] = db('seller')->where('seller_name',$date['seller_name'])->value('id');
//            }

            if (isset($date['dpid']) && $date['dpid']) {
                $where_shop_name['shop_name'] = ['like', '%' . trim($date['dpid']) . '%'];
                $shop_id = db('shop')->where($where_shop_name)->column('id');
                $where['seller_id']=['in',$shop_id];
            }

//            if (isset($date['dpid']) && $date['dpid']) {
//                $where['shop_id'] = db('shop')->where('shop_name',$date['dpid'])->value('id');
//            }
            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['publish_time'] = ['between', [$time1, $time2]];
            }
            if (isset($date['state']) && $date['state']!='') {
                $where['status'] = $date['state'];

            }
            if (isset($date['task_type']) && $date['task_type']!='') {
                $where['task_type'] = $date['task_type'];

            }
            $count = SellerTask::where($where)->count('id');
            $buyno_list = SellerTask::where($where)->limit(($page - 1) * $limit, $limit)->order('pay_time desc,publish_time desc')->select();
            if ($buyno_list) $buyno_list = $buyno_list->toArray();
            foreach ($buyno_list as $K => &$v) {
                $status=array(
                    '1'=>"未支付",
                    '2'=>"已支付，待审核",
                    '3'=>"已通过",
                    '4'=>"已拒绝",
                    '5'=>"已撤销",
                    '6'=>"已完成",
                );
                $v['task_status'] =$status[$v['status']];
                if($v['is_shengji']==1){
                    $v['goods_num'] = array_sum(json_decode($v['goods_num']));
                    $v['goodss_num'] = count(json_decode($v['goods_id']));
                    $v['goods_unit_price'] = array_sum(json_decode($v['goods_unit_price']));
                }elseif ($v['is_shengji']==2){
                    $v['goods_num'] =db('task_goods')->where(['task_id'=>$v['id']])->sum('num');
                    $v['goodss_num'] =db('task_goods')->where(['task_id'=>$v['id']])->count('id');
                    $v['goods_unit_price'] =db('task_goods')->where(['task_id'=>$v['id']])->sum('price');
                }
            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $buyno_list]);
        }
        return view();
    }

    //淘宝任务审核
    public function task_to_modify()
    {
        $data=input();
        $data=$data['data'];
        if (!$data['id']) {
            return $this->error('参数错误！');
        }
        $update_data['memo']=$data['memo'];
        $update_data['update_time']=time();
        $data_info = db('seller_task')->where('id',$data['id'])->find();
        if(!$data_info){
            return $this->error('订单不存在！');
        }
        $res = db('seller_task')->where('id', $data['id'])->update($update_data);
        if($res){
            return $this->success('修改成功！');
        }else{
            return $this->error('修改失败！');
        }
    }
    public function taskExamine(){
        $id=input('id');
        if(!$id){
            $this->error('参数错误！');
        }
        $task_info=db('seller_task')->where('id',$id)->field('id,remarks,status')->find();
        if(!$task_info){
            $this->error('订单不存在！');
        }
        $this->assign('task_info',$task_info);
        return view();
    }
    //淘宝任务审核
    public function taskToExamine()
    {
        $data=input();
        if (!$data['id'] || $data['state']=='') {
            return $this->error('参数错误！');
        }
        $update_data['status']=$data['state'];
        $update_data['remarks']=$data['remarks'];
        $update_data['examine_time']=time();
        $data_info = db('seller_task')->where('id',$data['id'])->find();

        if($data_info['status']==4 && !($data['state']==3 || $data['state']==4)){
            return $this->error('已拒绝的订单只能改为已通过！');
        }

        if($data_info['status']==3 &&$data['state']==2){
            return $this->error('已通过的订单不能改为待审核！');
        }

        if($data_info['status']==4 &&$data['state']==2){
            return $this->error('已拒绝的订单不能改为待审核！');
        }

        if($data_info['status']==5&&$data['state']!=5){
            return $this->error('已取消的订单不能操作！');
        }
        if($data_info['status']!=5&&$data['state']==5){
            return $this->error('后台不能取消订单！');
        }
        try{
            Db::startTrans();
            $res = db('seller_task')->where('id', $data['id'])->update($update_data);
            if(!$res){
                throw new Exception('修改失败!');
            }
//            if($data['state']==4){
//            $deposit=$data_info['deposit'];//押金
//            $silver_ingot=$data_info['silver_ingot'];//银锭
//                $seller_info=db('seller')->where('id', $data_info['seller_id'])->find();
//                if(!$seller_info){
//                    throw new Exception('商家不存在!');
//                }
//                $seller_balance=$seller_info['balance']+$deposit;
//                $seller_reward=$seller_info['reward']+$silver_ingot;
//                $res1=db('seller')->where('id',$data_info['seller_id'])->update(['balance'=>$seller_balance,'reward'=>$seller_reward]);
//                if(!$res1){
//                    throw new Exception('退款出错!');
//                }
//                $d1=finance($seller_info['id'],1,$seller_balance,1,8,"押金:{$seller_balance},任务取消返还");
//                $d2=finance($seller_info['id'],1,$seller_reward,2,8,"银锭:{$seller_reward},任务取消返还");
//                if(!$d1 || !$d2){
//                    throw new Exception('写入财务出错!');
//                }
//            }
            $res1=admin_log("淘宝任务审核", "管理员{$this->admin_info['user_name']}操作:任务编号{$data_info['task_number']}");
            if(!$res1){
                throw new Exception('操作日志写入失败！');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }

        return $this->success('修改成功！');
    }

    /**
     * 任务详情
     */
    public function xiangqing()
    {
        $data = input();
        if (!$data['id']) {
            return false;
        }
        $task = SellerTask::where('id', $data['id'])->find();
        if ($task) $task = $task->toArray();
        if($task['is_shengji']==1){     //第一版本
            //$task['shop']=db('shop')->where('id',$task['shop_id']['id'])->find();
            $goods_id = json_decode($task['goods_id']);
            $goods_unit_price = json_decode($task['goods_unit_price']);
            $goods_num = json_decode($task['goods_num']);
            $goods_spec = explode('{}',$task['goods_spec']);

            if($task['terminal']=='pc'){
                $type=1;
            }else{
                $type=2;
            }

            foreach ($goods_id as $k => $value) {
                $good = db('goods')->where('id', $value)->find();
                $good['pc_img'] = json_decode($good['pc_img'])[0];
                $goods[] = $good;
                $goods[$k]['task_num'] = $goods_num[$k];
                $goods[$k]['task_price'] = $goods_unit_price[$k];
                $goods[$k]['goods_spec'] = $goods_spec[$k];
                $key_world = db('goods_key_world')->where(['goods_key_id'=>$good['goods_key_id']])->column('key_world');
                $goods[$k]['key_world'] =$key_world;
                if ($k == 0) {
                    $goods[$k]['type'] = "主商品信息";
                } else {
                    $goods[$k]['type'] = "副商品{$k}信息";
                }
            }
        }elseif ($task['is_shengji']==2){  //第二次升级版本
            $sj_task_goods =db('task_goods')->where(['task_id'=>$task['id']])->select();
            if ($sj_task_goods) $sj_task_goods = $sj_task_goods->toArray();
            foreach ($sj_task_goods as $k=>$goodvo){
                $good = db('goods')->where('id', $goodvo['goods_id'])->find();
                $good['pc_img'] = $goodvo['pc_img'];
                $good['task_num'] = $goodvo['num'];
                $good['task_price'] = $goodvo['price'];
                $good['goods_spec'] = $goodvo['goods_spec'];
                if ($k == 0) {
                    $good['type'] = "主商品信息";
                } else {
                    $good['type'] = "副商品{$k}信息";
                }
                $key_world = db('task_word')->where(['task_id'=>$task['id'],'goods_id'=>$goodvo['goods_id']])->column('key_word');
                $good['key_world'] = $key_world;
                $goods[] = $good;

            }
        }
        $task['goods'] = $goods;
        $this->assign('task', $task);
        return view();
    }

    /**
     * 接单详情
     */
    public function jdxq()
    {
        $id=input('id');
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            $where['seller_task_id'] =$date['taskid'] ;
            //$where['state'] = array('neq', 2);
            $count = UserTask::where($where)->count('id');
            $buyno_list = UserTask::where($where)->limit(($page - 1) * $limit, $limit)->order('id desc')->select();
            if ($buyno_list) $buyno_list = $buyno_list->toArray();
            foreach ($buyno_list as $K => &$v) {
                $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
                $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
                if ($terminal == 1) {
                    $v['terminal'] = "pc";
                } else {
                    $v['terminal'] = "手机";
                }

                $v['seller_info'] = db('seller')->where('id', $seller_id)->find();
                $v['user_info'] = db('users')->where('id', $v['user_id'])->find();
            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $buyno_list]);
        }
        $this->assign('id',$id);
        return view();
    }

    /**
     * 放弃详情
     */
    public function giveUp()
    {
        $id=input('id');
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            $where['seller_task_id'] =$date['taskid'] ;
            $where['state'] = array('eq', 2);
            $count = UserTask::where($where)->count('id');
            $buyno_list = UserTask::where($where)->limit(($page - 1) * $limit, $limit)->select();
            if ($buyno_list) $buyno_list = $buyno_list->toArray();
            foreach ($buyno_list as $K => &$v) {
                $seller_id = db('seller_task')->where('id', $v['seller_task_id'])->value('seller_id');
                $terminal = db('seller_task')->where('id', $v['seller_task_id'])->value('terminal');
                if ($terminal == 1) {
                    $v['terminal'] = "pc";
                } else {
                    $v['terminal'] = "手机";
                }

                $v['seller_info'] = db('seller')->where('id', $seller_id)->find();
                $v['user_info'] = db('users')->where('id', $v['user_id'])->find();
            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $buyno_list]);
        }
        $this->assign('id',$id);
        return view();
    }

    /**
     * 接单详情
     */
    public function jdxq2()
    {
        $date = input();
        $user_task_info = db('user_task')->where('id', $date['id'])->find();
        if (!$user_task_info) {
            return $this->error('参数错误！');
        }
        $task = SellerTask::where('id', $user_task_info['seller_task_id'])->find();
        if ($task) $task = $task->toArray();
        if ($task['is_shengji']==1){
            $goods_id = json_decode($task['goods_id']);
            $goods_unit_price = json_decode($task['goods_unit_price']);
            $goods_num = json_decode($task['goods_num']);
            foreach ($goods_id as $k => $value) {
                $good = db('goods')->where('id', $value)->find();
                $goods[] = $good;
                $goods[$k]['task_num'] = $goods_num[$k];
                $goods[$k]['task_price'] = $goods_unit_price[$k];
                $key_world = db('goods_key_world')->where('goods_key_id', $good['goods_key_id'])->column('key_world');
                $goods[$k]['key_world'] = implode(',', $key_world);
                if ($k == 0) {
                    $goods[$k]['type'] = "主商品信息";
                } else {
                    $goods[$k]['type'] = "副商品{$k}信息";
                }
            }

        }elseif ($task['is_shengji']==2){  //第二次升级版本
            $sj_task_goods =db('task_goods')->where(['task_id'=>$task['id']])->select();
            if ($sj_task_goods) $sj_task_goods = $sj_task_goods->toArray();
            foreach ($sj_task_goods as $k=>$goodvo){
                $good = db('goods')->where('id', $goodvo['goods_id'])->find();
                $good['pc_img'] = $good['pc_img'];
                $good['task_num'] = $goodvo['num'];
                $good['task_price'] = $goodvo['price'];
                $good['goods_spec'] = $goodvo['goods_spec'];
                if ($k == 0) {
                    $good['type'] = "主商品信息";
                } else {
                    $good['type'] = "副商品{$k}信息";
                }
                $key_world = db('task_word')->where(['task_id'=>$task['id'],'goods_id'=>$goodvo['goods_id']])->column('key_word');
                $good['key_world'] = $key_world;
                $goods[] = $good;

            }
        }
        $task['goods'] = $goods;
        $task['user_info'] = db('users')->where('id', $user_task_info['user_id'])->find();
        $task['user_task_info'] = $user_task_info;
        $this->assign('task', $task);
        return view();
    }


    /**
     * 取消任务(不用)
     */
    public function cancel_task_test()
    {
        $date = input();
        $user_task_info = db('user_task')->where('id', $date['id'])->find();
        if (!$user_task_info) {
            return $this->error('参数错误！');
        }
        if ($user_task_info['state']==2) {
            return $this->error('任务已经取消！');
        }
//        if ($user_task_info['state']==3) {
//            return $this->error('待发货的订单无法取消！');
//        }
        if ($user_task_info['state']==1) {
            return $this->error('完成的订单无法取消！');
        }
        if ($user_task_info['state']==6) {
            return $this->error('待返款的订单不能取消！');
        }

        try{
            Db::startTrans();
            $res=db('user_task')->where('id', $date['id'])->update(['state'=>2,'deltask_type'=>3]);//修改任务状态
            if($res) {
                $user_info = db('users')->where('id', $user_task_info['user_id'])->find();
                if (!$user_info) {
                    throw new Exception('接单人不存在!');
                }
                db('users')->where('id', $user_task_info['user_id'])->setInc('reward',1);//银锭+1
                finance($user_info['id'],2,1,2,10,"管理员{$this->admin_info['user_name']}操作:取消任务{$user_task_info['task_number']}返还");
                db('seller_task')->where('id', $user_task_info['seller_task_id'])->setInc('incomplete_num',1);//商家任务数量+1
                $res1=admin_log("取消淘宝任务", "管理员{$this->admin_info['user_name']}操作:任务编号{$user_task_info['task_number']}");
                if(!$res1){
                    throw new Exception('操作日志写入失败！');
                }
                $praise_ids = json_decode($user_task_info['ids']);
                if($user_task_info['ids'] && $praise_ids){
                    Db::name('seller_task_praise')->where(['id'=>['in',$praise_ids]])->update(['state'=>0]);
                }
                Db::commit();
            }
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }

        return $this->success('取消成功！');

    }

    /**
     * @notes  取消买手已接的任务
     * @date 2019/12/4
     * @time 13:21
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel_task(Request $request){
        $data = $request->param();
        $user_task = Db::name('user_task')->where(['id'=>$data['id']])->lock(true)->find();
        if(!$user_task)return $this->error('订单不存在');
        if($user_task['state']==2)return $this->error('订单已取消，无法操作！');
        if($user_task['state']==1)return $this->error('已完成订单，无法操作！');
        if($user_task['state']==6)return $this->error('待返款订单，无法操作！');
        $user = Db::name('users')->where(['id'=>$user_task['user_id']])->find();
        if(!$user)return $this->error('买手信息未找到！');
        try{
            Db::startTrans();
            $update['state'] = 2;
            $update['deltask_type'] = 3;
            //$update['cancel_remarks'] = $data['key'];
            Db::name('user_task')->where(['id'=>$user_task['id']])->update($update);
            $last = Db::name('user_task')->where(['seller_task_id'=>$user_task['seller_task_id'],'create_time'=>['gt',$user_task['create_time']]])->find();
            if(!$last){
                Db::name('seller_task')->where(['id'=>$user_task['seller_task_id']])->update(['receipt_time'=>0]);
            }
            Db::name('users')->where(['id'=>$user['id']])->setInc('reward',1);//银锭+1
            finance($user['id'],2,1,2,10,"管理员{$this->admin_info['user_name']}操作:取消任务{$user_task['task_number']}返还");
            $num = Db::name('seller_task')->where(['id'=>$user_task['seller_task_id']])->value('incomplete_num');
            $num = $num + 1;
            Db::name('seller_task')->where(['id'=>$user_task['seller_task_id']])->update(['incomplete_num'=>$num]);//商家任务数量+1
            admin_log("取消淘宝任务", "管理员{$this->admin_info['user_name']}操作:任务编号{$user_task['task_number']}");
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
        return $this->success('取消成功');

    }




    /**
     * 修改返款金额
     */
    public function return_price()
    {
        $data=input();
        if(!$data['price'] || $data['price']<0){
            return $this->error('请核实返款金额！');
        }
        if(!$data['taskid']){
            return $this->error('参数错误');
        }
        $user_task=db('user_task')->where(['id'=>$data['taskid']])->find();
        if(!$user_task){
            return   $this->error('任务不存在！');
        }
        if($user_task['principal']-100>$data['price'] || $user_task['principal']+100<$data['price']){
            return $this->error('返款金额上下浮动不能超过100元！');
        }
        if(!($user_task['state']==5||$user_task['state']==4||$user_task['state']==3))return $this->error('任务状态不正确，只有待发货,待收货,待返款才能修改！');
        $res=db('user_task')->where('id',$data['taskid'])->update(['seller_principal'=>$data['price']]);
        if($res){
            $res1=admin_log("修改返款金额", "管理员{$this->admin_info['user_name']}操作:任务编号{$user_task['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return  $this->success('修改成功！');
        }else{
            return $this->error('修改失败！');
        }
    }


    /**
     * 修改返款金额
     */
    public function incomplete_num()
    {
        $data=input();
        if(!$data['price'] || $data['price']<0){
            return $this->error('请核实剩余单数！');
        }
        if(!$data['taskid']){
            return $this->error('参数错误');
        }

        $seller_task=db('seller_task')->where(['id'=>$data['taskid']])->find();
        if(!$seller_task){
            return   $this->error('任务不存在！');
        }
        $count=db('user_task')->where(['seller_task_id'=>$seller_task['id'],'state'=>['neq',2]])->count();
        if($seller_task['num']-$count!=$data['price']){
            return $this->error('请核实缺少数量！');
        }
        if(($seller_task['status']==1||$seller_task['status']==2||$seller_task['status']==6))return $this->error('任务状态不正确');
        $res=db('seller_task')->where('id',$data['taskid'])->update(['incomplete_num'=>$data['price']]);
        if($res){
            $res1=admin_log("修改剩余单数", "管理员{$this->admin_info['user_name']}操作:任务编号{$seller_task['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return  $this->success('修改成功！');
        }else{
            return $this->error('修改失败！');
        }
    }


    /**
     * 修改返款金额
     */
    public function return_price1()
    {
        $data=input();
        if(!$data['price'] || $data['price']<0){
            return $this->error('请核实返款金额！');
        }
        if(!$data['taskid']){
            return $this->error('参数错误');
        }
        $user_task=db('user_task')->where(['id'=>$data['taskid']])->find();
        if(!$user_task){
            return   $this->error('任务不存在！');
        }
        if($user_task['yf_price']-100>$data['price'] || $user_task['yf_price']+100<$data['price']){
            return $this->error('返款金额上下浮动不能超过100元！');
        }
        if($user_task['state']!=5)return $this->error('任务状态不正确，只有待返款才能修改！');
        $res=db('user_task')->where('id',$data['taskid'])->update(['yf_price'=>$data['price']]);
        if($res){
            $res1=admin_log("修改预付金额", "管理员{$this->admin_info['user_name']}操作:任务编号{$user_task['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return  $this->success('修改成功！');
        }else{
            return $this->error('修改失败！');
        }
    }
    /**
     * 修改返款金额
     */
    public function return_price2()
    {
        $data=input();
        if(!$data['price'] || $data['price']<0){
            return $this->error('请核实返款金额！');
        }
        if(!$data['taskid']){
            return $this->error('参数错误');
        }
        $user_task=db('user_task')->where(['id'=>$data['taskid']])->find();
        if(!$user_task){
            return   $this->error('任务不存在！');
        }
        if($user_task['wk_price']-100>$data['price'] || $user_task['wk_price']+100<$data['price']){
            return $this->error('尾款金额上下浮动不能超过100元！');
        }
        if($user_task['state']!=5)return $this->error('任务状态不正确，只有待返款才能修改！');
        $res=db('user_task')->where('id',$data['taskid'])->update(['wk_price'=>$data['price']]);
        if($res){
            $res1=admin_log("修改尾款金额", "管理员{$this->admin_info['user_name']}操作:任务编号{$user_task['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return  $this->success('修改成功！');
        }else{
            return $this->error('修改失败！');
        }
    }



    /**
     * 修改关键词
     */
    public function edit_key()
    {
        $data=input();
        if(!$data['key']){
            return $this->error('请填写关键字！');
        }
        if(!$data['taskid']){
            return  $this->error('参数错误');
        }
        $user_task=db('user_task')->where('id',$data['taskid'])->find();
        if(!$user_task){
            return   $this->error('任务不存在！');
        }
        $res=db('user_task')->where('id',$data['taskid'])->update(['key'=>trim($data['key'])]);
        if($res){
            $res1=admin_log("修改订单关键字", "管理员{$this->admin_info['user_name']}操作:任务编号{$user_task['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return  $this->success('修改成功！');
        }else{
            return   $this->error('修改失败！');
        }
    }

    /**
     * 任务修改
     */
    public function update()
    {
        return view();
    }

    /**
     * @notes 批量返款
     * @date 2019/9/25
     * @time 12:38
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnPayss(Request $request){
        $data = $request->param();
        $data['ids']=explode(",",$data['ids']);
//        foreach ($data['data'] as $v){
//            $data['ids'][]=$v['id'];
//        }
        if(!$data['ids'] || !is_array($data['ids']))return $this->error('请选择要发货的任务单！');
        $num = 0;
        $numbers = [];
        foreach ($data['ids'] as $key=>$id){
            $list = Db::name('user_task')->where(['id'=>$id,'state'=>5,'is_ys'=>0])->field("id,seller_task_id,seller_id,principal,seller_principal,task_number,state")->find();
            if(!$list)continue;
            $margin = Db::name('seller_task')->where(['id'=>$list['seller_task_id']])->value('margin');
            $margin = $margin ? $margin : 0;
            $data['price']=$list['seller_principal'];
            if(!$data['price'])continue;
            if($data['price']<=0)continue;
            $price = $data['price'] - $list['principal']-$margin;
            $seller = Db::name('seller')->where(['id'=>$list['seller_id']])->find();
            if($seller['balance'] < $price)continue;
            try{
                Db::startTrans();
                if($price != 0){
                    $update['balance'] = $seller['balance'] - $price;
                    Db::name('seller')->where(['id'=>$seller['id']])->update($update);
                    if($price<0){
                        $prices=-$price;
                    }else{
                        $prices=$price;
                    }
                    if(!finance($seller['id'],1,-$price,1,9,"本金:{$prices},任务{$list['task_number']}返款补差额"))throw new Exception('财务写入失败！');
                }
                $task['seller_principal'] = $data['price'];
                $task['platform_refund_time'] = time();
                $task['state'] = 6;
                $ree=Db::name('user_task')->where(['id'=>$list['id']])->update($task);
                $numbers[] = $list['task_number'];
                $num++;
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                continue;
            }
        }
        $nums = count($data['ids'])-$num;
        if($numbers){
            $numbers = implode(',',$numbers);
            admin_log("任务单条返款", "管理员{$this->admin_info['user_name']}操作:任务编号:{$numbers}");
        }
        return $this->success("返款成功{$num}条，失败{$nums}条!");

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
        $list = Db::name('user_task')->where(['id'=>$data['id'],'state'=>5])->field("id,seller_id,seller_task_id,principal,seller_principal,task_number,state")->find();
        if(!$list)return $this->error('未找到数据或数据状态不正确！请刷新重试');
        if($list['is_ys']!=0)return $this->error('预售任务不能适应本返款方式！');
        $margin = Db::name('seller_task')->where(['id'=>$list['seller_task_id']])->value('margin');
        $margin = $margin ? $margin : 0;
        $data['price']=$list['seller_principal'];
        if(!$data['price'])return $this->error('请填写返款金额！');
        if($data['price']<=0)return $this->error('返款金额不正确！');
        $price = $data['price'] - $list['principal']-$margin;
        $seller = Db::name('seller')->where(['id'=>$list['seller_id']])->find();
        if($seller['balance'] < $price)return $this->error('商家返款有差额，账户余额不足以补差价');
        try{
            Db::startTrans();
            if($price != 0){
                $update['balance'] = $seller['balance'] - $price;
                Db::name('seller')->where(['id'=>$seller['id']])->update($update);
                if($price<0){
                    $prices=-$price;
                }else{
                    $prices=$price;
                }
                if(!finance($seller['id'],1,-$price,1,9,"本金:{$prices},任务{$list['task_number']}返款补差额"))throw new Exception('财务写入失败！');
            }
            $task['seller_principal'] = $data['price'];
            $task['platform_refund_time'] = time();
            $task['state'] = 6;
            Db::name('user_task')->where(['id'=>$list['id']])->update($task);
            $res1=admin_log("任务单条返款", "管理员{$this->admin_info['user_name']}操作:任务编号:{$list['task_number']}");
            if(!$res1)throw new Exception('操作日志写入失败!');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('返款成功！');
    }


    /**
     * @notes 预付返款
     * @date 2019/9/25
     * @time 12:38
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnys(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('请选择要返款的任务单！');
        $list = Db::name('user_task')->where(['id'=>$data['id'],'state'=>5])->field("id,seller_id,seller_task_id,principal,seller_principal,task_number,state,is_ys,user_id,ok_yf,ok_wk")->find();
        if(!$list)return $this->error('未找到数据或数据状态不正确！请刷新重试');
        if($list['is_ys']==0)return $this->error('该任务不是预售任务！');
        //$margin = Db::name('seller_task')->where(['id'=>$list['seller_task_id']])->value('margin');
        //$margin = $margin ? $margin : 0;
        //$data['price']=$list['yf_price'];//预付款
        // if(!$data['price'])return $this->error('请填写返款金额！');
        // if($data['price']<=0)return $this->error('返款金额不正确！');
        //$price = $data['price'] ;
        //$seller = Db::name('user')->where(['id'=>$list['seller_id']])->find();
        //if($seller['balance'] < $price)return $this->error('商家返款有差额，账户余额不足以补差价');
        try{
            Db::startTrans();

            if($data['type']==1){
                if($list['ok_yf']!=0)throw new Exception('任务状态不对!');
                $task['update_time'] = time();
                $task['ok_yf'] =1;
                $task['platform_refund_time'] = time();
                $task['state'] = 6;
                Db::name('user_task')->where(['id'=>$list['id']])->update($task);
                $res1=admin_log("任务预付款返款", "管理员{$this->admin_info['user_name']}操作:任务编号:{$list['task_number']}");
                if(!$res1)throw new Exception('操作日志写入失败!');
            }elseif ($data['type']==2){
                if($list['ok_wk']!=0)throw new Exception('任务状态不对!');
                $task['update_time'] = time();
                $task['ok_wk'] = 1;
                $task['platform_refund_time'] = time();
                $task['state'] = 6;
                Db::name('user_task')->where(['id'=>$list['id']])->update($task);
                $res1=admin_log("任务尾款返款", "管理员{$this->admin_info['user_name']}操作:任务编号:{$list['task_number']}");
                if(!$res1)throw new Exception('操作日志写入失败!');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('返款成功！');
    }


    /**
     * @notes 平台返款批量发货
     * @date 2019/9/25
     * @time 12:39
     * @param Request $request
     */
    public function allDelivery(Request $request){
        $data = $request->param();
//        foreach ($data['data'] as $v){
//            $data['ids'][]=$v['id'];
//        }
        $data['ids']=explode(",",$data['ids']);
        if(!$data['ids'] || !is_array($data['ids']))return $this->error('请选择要发货的任务单！');
        $num = 0;
        $nums = 0;
        $taskids=[];
        try{
            Db::startTrans();
            foreach ($data['ids'] as $key=>$id){
                $list = Db::name('user_task')->where(['id'=>$id])->field("id,user_principal,task_number,commission,delivery_state,state")->find();
                if($list){
                    if($list['state']==3 && $list['delivery_state']==1){
                        $task['delivery_time'] = time();
                        $task['delivery_state'] = 2;
                        $task['state'] = 4;
                        Db::name('user_task')->where(['id'=>$list['id']])->update($task);
                        $taskids[]=trim($list['task_number']);
                        $num++;
                    }else{
                        $nums++;
                    }
                }
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        $taskids=json_encode($taskids);
        $text = "成功发货{$num}条，失败{$nums}条";
        $res1=admin_log("任务多条发货", "管理员{$this->admin_info['user_name']}操作:任务信息:{$taskids}");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        return $this->success($text);
    }

    /**
     * @notes 推荐奖励发放
     * @date 2019/9/25
     * @time 12:39
     * @param $user_id 买手id
     * @param $seller_id 商家id
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function tjjAng($user_id,$seller_id){
        $user = Db::name('users')->where(['id'=>$user_id])->field('tjuser,tjuser_state')->find();
        if($user){
            if($user['tjuser_state']==1){
                $tjuser = Db::name('users')->where(['mobile'=>$user['tjuser']])->find();
            }else{
                $tjuser = Db::name('seller')->where(['mobile'=>$user['tjuser']])->find();
            }
            if($tjuser){
                $price = $tjuser['tj_award'] > 0 ? 1 : 10;
                $user_update['reward'] = $tjuser['reward'] + $price;
                $user_update['tj_award'] = $tjuser['tj_award'] + $price;
                $user_update['tj_award_day'] = $tjuser['tj_award_day'] + $price;
                if($user['tjuser_state']==1){
                    Db::name('users')->where(['id'=>$tjuser['id']])->update($user_update);
                    if(!finance($tjuser['id'],2,$price,2,6,"银锭:{$price},{$user_id}做任务发放推荐佣金",1,$user_id))throw new Exception('财务写入失败！');
                }else{
                    Db::name('seller')->where(['id'=>$tjuser['id']])->update($user_update);
                    if(!finance($tjuser['id'],1,$price,2,6,"银锭:{$price},{$user_id}做任务发放推荐佣金",1,$user_id))throw new Exception('财务写入失败！');
                }
            }
        }
        $seller = Db::name('seller')->where(['id'=>$seller_id])->field('tjuser,tjuser_state')->find();
        if($seller){
            if($seller['tjuser_state']==1){
                $tjseller = Db::name('users')->where(['mobile'=>$user['tjuser']])->find();
            }else{
                $tjseller = Db::name('seller')->where(['mobile'=>$user['tjuser']])->find();
            }
            if($tjseller){
                $price = $tjseller['tj_award'] > 0 ? 1 : 10;
                $seller_update['reward'] = $tjseller['reward'] + $price;
                $seller_update['tj_award'] = $tjseller['tj_award'] + $price;
                $seller_update['tj_award_day'] = $tjseller['tj_award_day'] + $price;
                if($seller['tjuser_state']==1){
                    Db::name('users')->where(['id'=>$tjseller['id']])->update($seller_update);
                    if(!finance($tjseller['id'],2,$price,2,6,"银锭:{$price},{$seller_id}发任务发放推荐佣金",2,$seller_id))throw new Exception('财务写入失败！');
                }else{
                    if($seller['tjuser_state']==2)Db::name('seller')->where(['id'=>$tjseller['id']])->update($seller_update);
                    if(!finance($tjseller['id'],1,$price,2,6,"银锭:{$price},{$seller_id}发任务发放推荐佣金",2,$seller_id))throw new Exception('财务写入失败！');
                }

            }
        }
    }


    /**
     * @notes 淘宝任务多条审核通过
     * @date 2019/9/25
     * @time 12:39
     * @param Request $request
     */
    public function allexamine(Request $request){
        $data = $request->param();
//        foreach ($data['data'] as $v){
//            $data['ids'][]=$v['id'];
//        }
        $data['ids']=explode(",",$data['ids']);
        if(!$data['ids'] || !is_array($data['ids']))return $this->error('请选择要审核的任务单！');
        $num = 0;
        $nums = 0;
        try{
            Db::startTrans();
            foreach ($data['ids'] as $id){
                $list = Db::name('seller_task')->where(['id'=>$id])->field("id,status")->find();
                if($list){
                    if($list['status']==2){
                        $task['examine_time'] = time();
                        $task['status'] = 3;
                        Db::name('seller_task')->where(['id'=>$list['id']])->update($task);
                        $num++;
                    }else{
                        $nums++;
                    }
                }
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        $text = "成功审核{$num}条，失败{$nums}条";
        $res1=admin_log("任务审核", "管理员{$this->admin_info['user_name']}操作:任务审核");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        return $this->success($text);
    }

    public function operation(){
        $id=input('id');
        if(!$id){
            return $this->error('参数错误！');
        }
        $task_info=db('user_task')->where('id',$id)->field('id,delivery,delivery_num')->find();
        if(!$task_info){
            return  $this->error('订单不存在！');
        }
        $this->assign('task_info',$task_info);
        return view('PlatformCounterpayment/operation');
    }


    public function deliver_examine(){
        $data=input();
        if(!$data['id'] ||!$data['type']){
            return $this->error('参数错误！');
        }
        if(!$data['delivery']){
            return $this->error('请填写快递公司！');
        }
        if(!$data['delivery_num']){
            return   $this->error('请填写快递单号！');
        }
        $task_info=db('user_task')->where('id',$data['id'])->find();
        if(!$task_info){
            return   $this->error('订单不存在！');
        }
        if($task_info['state']!=3){
            return  $this->error('待发货订单才可以操作！');
        }
        $update_data['delivery']=$data['delivery'];
        $update_data['delivery_num']=$data['delivery_num'];
        if($data['type']==1){
            $update_data['update_time']=time();
            $msg="修改";
        }else{
            if($task_info['state']<=3 && $task_info['state']!=2){
                $update_data['delivery_state']=1;
                $update_data['state']=3;
                $update_data['delivery_time']=time();
            }
            $update_data['update_time']=time();
            $msg="发货";
        }
        $res=db('user_task')->where('id',$data['id'])->update($update_data);
        if($res){
            $res1=admin_log("买家购买管理订单{$msg}", "管理员{$this->admin_info['user_name']}操作:任务编号{$task_info['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return $this->success("{$msg}成功！");
        }else{
            return $this->error("{$msg}失败！");
        }
    }

//任务回退到待收货
    public function regression(){
        $id=input('id');
        if(!$id){
            $this->error('参数错误！');
        }
        $task_info=db('user_task')->where('id',$id)->field('id,delivery,delivery_num')->find();
        if(!$task_info){
            $this->error('订单不存在！');
        }
        $this->assign('task_info',$task_info);
        return view('platformReturn/regression');
    }

    public function regression_examine()
    {
        $data = input();
        if (!$data['id']) {
            $this->error('参数错误！');
        }
        if (!$data['delivery']) {
            $this->error('请填写快递公司！');
        }
        if (!$data['delivery_num']) {
            $this->error('请填写快递单号！');
        }
        $task_info = db('user_task')->where(['id'=>$data['id'],'state'=>5])->find();
        if (!$task_info) {
            $this->error('订单不存在！');
        }
        $update_data['delivery'] = $data['delivery'];
        $update_data['delivery_num'] = $data['delivery_num'];
        $update_data['state'] = 4;
        $update_data['delivery_time'] = time();
        $update_data['update_time'] = time();
        $res=db('user_task')->where('id', $data['id'])->update($update_data);
        if($res){
            $res1=admin_log("重新发货", "管理员{$this->admin_info['user_name']}操作:订单编号:{$task_info['task_number']}");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            return $this->success('发货成功！');
        }else{
            return $this->error('发货失败！');
        }
    }

    /**
     * @notes
     * @date 2019/9/4
     * @time 11:31
     * @param Request $request
     * @return bool|\think\response\View|void
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        $data = $request->param();
        if(!$data['id'])return false;
        if(!$data['task_id'])return false;
        if(request()->isAjax()){
            $where['id'] = $data['id'];
            $list = Db::name('goods')->where(['id'=>$data['id']])->find();
            $shop = Db::name('shop')->where(['seller_id'=>$list['seller_id'],'state'=>0])->field("id,shop_name")->select();
            $shop = $shop ? $shop->toArray() : [];
            $list['pc_img'] = json_decode($list['pc_img']);
            if(!$list)return $this->error('该数据不存在');
            $list_task = Db::name('seller_task')->where(['id'=>$data['task_id']])->find();
            if($list_task && $list_task['is_shengji'] > 1){
                $task_word = Db::name('task_word')->where(['goods_id'=>$data['id'],'task_id'=>$data['task_id']])->select();
                $list_word = $task_word ? $task_word->toArray() : [];
            }else{
                $list_word = [];
            }
            $res = [
                'list' => $list,
                'shop' =>$shop,
                'list_word' =>$list_word
            ];
            return $this->success('success','',$res);
        }
        $this->assign('id',$data['id']);
        $this->assign('task_id',$data['task_id']);
        $state = Db::name('system')->value('switch');
        $this->assign('state',$state);
        return view();
    }

    /**
     * @Function 关键词修改
     * @Author 扬风
     * @Date: 2020/2/10
     * @Time: 13:27
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function keyWordEdit(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('参数错误!');
        if(!$data['key_word'])return $this->error('参数错误!');
        $list = Db::name('task_word')->where(['id'=>$data['id']])->find();
        if(!$list)return $this->error('未找到数据!');
        if(!Db::name('task_word')->where(['id'=>$data['id']])->update(['key_word'=>$data['key_word']]))return $this->error('修改失败');
        return $this->success('修改成功!');
    }

    /**
     * @notes
     * @date 2019/9/4
     * @time 11:31
     * @param Request $request
     */
    public function goodsKeyAdd(Request $request){
        $data = $request->param();
        if(!$data['seller_id'])return $this->error('参数错误！');
        $seller_id = $data['seller_id'];
        $data = $data['key_wold'];
        if(!$data['name'])return $this->error('请填写方案名称！');
        if(!$data['shop_id'])return $this->error('请选择店铺！');
        $type = Db::name('shop')->where(['seller_id'=>$seller_id,'id'=>$data['shop_id']])->value('type');
        Db::startTrans();
        try{
            $add['seller_id'] = $seller_id;
            $add['type'] = $type;
            $add['name'] = $data['name'];
            $add['create_time'] = time();
            $data['id'] = Db::name('goods_key')->insertGetId($add);
            $num = 0;
            foreach ($data['key_wold'] as &$row){

                if($row['key_world']){
                    $num++;
                    $row['goods_key_id'] = $data['id'];
                    $row['create_time'] = time();
                    if(isset($row['discount']) && $row['discount']){
                        $row['discount'] = implode(',',$row['discount']);
                    }else{
                        $row['discount'] = '';
                    }
                    if(isset($row['filter'])){
                        $row['filter'] = implode(',',$row['filter']);
                    }else{
                        $row['filter'] = ',,,';
                    }
                    Db::name('goods_key_world')->insert($row);
                }
            }
            if($num==0)throw new Exception('请填写关键词');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('添加方案成功','',$data);
    }



    /**
     * @notes
     * @date 2019/9/4
     * @time 11:31
     * @param Request $request
     */
    public function goodsKeyEdit(Request $request){
        $data = $request->param();
        if(!$data['name'])return $this->error('请填写方案名称！');
        if(!$data['id'])return $this->error('参数错误！');
        Db::startTrans();
        try{
            $edit['name'] = $data['name'];
            $edit['update_time'] = time();
            Db::name('goods_key')->where(['id'=>$data['id']])->update($edit);
            Db::name('goods_key_world')->where(['goods_key_id'=>$data['id']])->delete();
            $num = 0;
            foreach ($data['key_wold'] as &$row){
                if($row['key_world']){
                    $num++;
                    $row['goods_key_id'] = $data['id'];
                    $row['create_time'] = time();
                    if(isset($row['discount']) && $row['discount']){
                        $row['discount'] = implode(',',$row['discount']);
                    }else{
                        $row['discount'] = '';
                    }
                    if(isset($row['filter'])){
                        $row['filter'] = implode(',',$row['filter']);
                    }else{
                        $row['filter'] = ',,,';
                    }
                    Db::name('goods_key_world')->insert($row);
                }
            }
            if($num==0)throw new Exception('请填写关键词');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('修改方案成功','',$data);
    }

    /** 修改商品
     * @notes
     * @date 2019/8/27
     * @time 10:24
     * @param Request $request
     */
    public function goodsEditDo(Request $request){
        $data = $request->param();
        $result = $this->validate($data,'Goods.edit');
        if(true !== $result){
            return $this->error($result);
        }
        $url_info = parse_url($data['link']);
        if(!isset($url_info['query']))return $this->error('商品链接不正确');
        parse_str($url_info['query'],$query);
        if(!isset($query['id']))return $this->error('商品链接不正确');
        $taobao_id = $query['id'];
        $state = Db::name('system')->value('switch');
        if($state==1){
            if (!preg_match('/^[0-9]{6}$/i',$data['number']))return $this->error('核对码只能是6位数字！');
        }
        $edit['number'] = $data['number'];
        Db::startTrans();
        try{
            $pc_img = [];
            foreach ($data['pc_img'] as $item){
                if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $item, $result)){
                    $img = Img::base64_image_goods($item);
                    if($img['code']==0)throw new Exception('pc端商品图'.$img['data']);
                    $pc_img[] = $img['data'];
                }else{
                    $pc_img[] = $item;
                }
            }
            $edit['pc_img'] = json_encode($pc_img);
            $edit['name'] = $data['name'];
            $edit['shop_id'] = $data['shop_id'];
            $edit['goods_key_id'] = $data['goods_key_id'];
            $edit['link'] = $data['link'];
            $edit['taobao_id'] = $taobao_id;
            $edit['price'] = $data['price'];
            $edit['show_price'] = $data['show_price'];
            $edit['num'] = $data['num'];
            $edit['spec_name'] =$data['spec_name'];
            $edit['spec_value'] = $data['spec_value'];
            $edit['create_time'] = time();
            Db::name('goods')->where(['id'=>$data['id']])->update($edit);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        $res1=admin_log("修改商品", "管理员{$this->admin_info['user_name']}操作:修改商品id为:{$data['id']},名称为：{$data['name']}");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        return $this->success('修改成功');
    }

    /**
     * @notes  淘宝任务添加审核备注
     * @date 2019/12/4
     * @time 13:21
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function remarks_task(Request $request)
    {
        $data = $request->param();
        $data_info = db('seller_task')->where('id',$data['id'])->find();
        if (!$data_info) return $this->error('订单不存在');
        $update_data['remarks']=$data['key'];
        $update_data['update_time']=time();
        try{
            Db::startTrans();
            $res = db('seller_task')->where('id', $data['id'])->update($update_data);
            if(!$res){
                throw new Exception('修改失败!');
            }
            $res1=admin_log("淘宝任务添加备注", "管理员{$this->admin_info['user_name']}操作:任务编号{$data_info['task_number']}{$data['key']}");
            if(!$res1){
                throw new Exception('操作日志写入失败！');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }

        return $this->success('添加备注成功！');
    }
    /**
     * 追评管理
     */
    public function review_task()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['task_number']) && $date['task_number']) {
                $where['task_number'] = ['like', '%' . trim($date['task_number']) . '%'];
//                $where_shopname['task_number'] = ['like', '%' . trim($date['task_number']) . '%'];
//                $shopid=db('user_task')->where($where_shopname)->column('id');
//                $where['user_task_id']=['in',$shopid];
            }

            if (isset($date['wangwang']) && $date['wangwang']) {
                $where_user_buyno['wwid'] = ['like', '%' . trim($date['wangwang']) . '%'];
                $buynoid=db('user_buyno')->where($where_user_buyno)->column('id');
                $where['buy_id']=['in',$buynoid];
            }
            if (isset($date['shopname']) && $date['shopname']) {
                $where_shopname['shop_name'] = ['like', '%' . trim($date['shopname']) . '%'];
                $shopid=db('shop')->where($where_shopname)->column('id');
                $where['shop_id']=['in',$shopid];
            }

            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }
            if (isset($date['state']) && $date['state']!='') {
                $where['state'] =$date['state'];
            }else{
                $where['state'] =['neq',0];
            }

            $count = ReviewTask::where($where)->count('id');
            $buyno_list = ReviewTask::where($where)->limit(($page - 1) * $limit, $limit)->order('create_time desc')->select();
            if ($buyno_list) $buyno_list = $buyno_list->toArray();
            foreach ($buyno_list as $K => &$v) {
                $v['user_task'] = db('user_task')->where('id', $v['user_task_id'])->find();
                $v['shop'] = db('shop')->where('id', $v['shop_id'])->find();
                $v['seller_info'] = db('seller')->where('id', $v['seller_id'])->find();
                $v['user_info'] = db('users')->where('id', $v['user_id'])->find();
                $v['buyno_info'] = db('user_buyno')->where('id', $v['buy_id'])->find();
            }
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $buyno_list]);
        }
        return view('task/review_task');
    }

    public function reviewTaskExamine(){
        $id=input('id');
        if(!$id){
            $this->error('参数错误！');
        }
        $task_info=db('review_task')->where('id',$id)->field('id,remarks,state')->find();
        if(!$task_info){
            $this->error('订单不存在！');
        }
        $this->assign('task_info',$task_info);
        return view();
    }

    //追评任务审核
    public function reviewtaskToExamine()
    {
        $data=input();
        if (!$data['id'] || $data['state']=='') {
            return $this->error('参数错误！');
        }
        $update_data['state']=$data['state'];
        $update_data['remarks']=$data['remarks'];
        $update_data['examine_time']=time();
        $data_info = db('review_task')->where('id',$data['id'])->find();
        if($data_info['state']==6)return $this->error('买手已拒接,不能操作!');
//        if($data_info['status']==4 && ($data['state']!=3||$data['state']!=4)){
//            return $this->error('已拒绝的订单只能改为已通过！');
//        }
//        if($data_info['status']==4 && !($data['state']==3 || $data['state']==4)){
//            return $this->error('已拒绝的订单只能改为已通过！');
//        }
//
//        if($data_info['status']==3 &&$data['state']==2){
//            return $this->error('已通过的订单不能改为待审核！');
//        }

        if($data_info['state']==4 &&$data['state']==2){
            return $this->error('已完成订单不能改为已审核！');
        }

        if($data_info['state']==5 &&$data['state']!=5){
            return $this->error('已取消的订单不能操作！');
        }
        if($data['state']==2){
            $addmessage=[
                'type'=>1,
                'title'=>'追评任务',
                'content'=>"您有新的追评任务！请查看。",
                'create_time'=>time(),
                'state'=>1,
                'user_id'=>$data_info['user_id'],
                'author'=>session('admin_name'),
                'admin_id'=>session('admin_id')
            ];
            Db::name('message')->insertGetId($addmessage);
        }

        try{
            Db::startTrans();
            $res = db('review_task')->where('id', $data['id'])->update($update_data);
            if(!$res){
                throw new Exception('修改失败!');
            }
            $res1=admin_log("追评任务审核", "管理员{$this->admin_info['user_name']}操作:任务编号{$data_info['task_number']}");
            if(!$res1){
                throw new Exception('操作日志写入失败！');
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }

        return $this->success('修改成功！');
    }


    /**
     * @notes 追评任务多条审核通过
     * @date 2019/9/25
     * @time 12:39
     * @param Request $request
     */
    public function allexaminereview(Request $request){
        $data = $request->param();
//        foreach ($data['data'] as $v){
//            $data['ids'][]=$v['id'];
//        }
        $data['ids']=explode(",",$data['ids']);
        if(!$data['ids'] || !is_array($data['ids']))return $this->error('请选择要审核的任务单！');
        $num = 0;
        $nums = 0;
        try{
            Db::startTrans();
            foreach ($data['ids'] as $id){
                $list = Db::name('review_task')->where(['id'=>$id])->field("id,state,user_id")->find();
                if($list){
                    if($list['state']==1){
                        $task['examine_time'] = time();
                        $task['state'] = 2;
                        Db::name('review_task')->where(['id'=>$list['id']])->update($task);
                        $addmessage=[
                            'type'=>1,
                            'title'=>'追评任务',
                            'content'=>"您有新的追评任务！请查看。",
                            'create_time'=>time(),
                            'state'=>1,
                            'user_id'=>$list['user_id'],
                            'author'=>session('admin_name'),
                            'admin_id'=>session('admin_id')
                        ];
                        Db::name('message')->insertGetId($addmessage);
                        $num++;
                    }else{
                        $nums++;
                    }
                }
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        $text = "成功审核{$num}条，失败{$nums}条";
        $res1=admin_log("追评任务审核", "管理员{$this->admin_info['user_name']}操作:追评任务审核");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        return $this->success($text);
    }

    //追评详情
    public function reviewTaskXiangqing(){
        $id=input('id');
        if(!$id){
            $this->error('参数错误！');
        }
        $list = ReviewTasks::where(['id'=>$id])->order('id desc')->find();
        if ($list) $list = $list->toArray();
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
        $list['user_task'] = db('user_task')->where('id', $list['user_task_id'])->find();
        // $list['shop'] = db('shop')->where('id', $list['shop_id'])->find();
        $list['seller_info'] = db('seller')->where('id', $list['seller_id'])->find();
        $list['user_info'] = db('users')->where('id', $list['user_id'])->find();
        //$list['buyno_info'] = db('user_buyno')->where('id', $list['buy_id'])->find();
        $this->assign('list',$list);
        return view();
    }


    /**
     * @notes 追评任务单条返款
     * @date 2019/9/25
     * @time 12:38
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reviewTaskReturnPay(Request $request){
        $data = $request->param();
        if(!$data['id'])return $this->error('请选择要返款的任务单！');
        $list = Db::name('review_task')->where(['id'=>$data['id'],'state'=>3])->field("id,seller_id,task_id,user_id,user_money,task_number,state")->find();
        if(!$list)return $this->error('未找到数据或数据状态不正确！请刷新重试');
        $data['price']=$list['user_money'];
        if(!$data['price'])return $this->error('请填写返款金额！');
        if($data['price']<=0)return $this->error('返款金额不正确！');
        $price = $data['price'];
        try{
            Db::startTrans();
            Db::name('users')->where(['id'=>$list['user_id']])->setInc('reward',$price);
            $user=Db::name('users')->where(['id'=>$list['user_id']])->find();
            if(!finance($user['id'],2,$price,2,7,"银锭:{$price},任务{$list['task_number']}返款"))throw new Exception('财务写入失败！');
            $task['confirm_time'] = time();
            $task['state'] = 4;
            Db::name('review_task')->where(['id'=>$list['id']])->update($task);
            $res1=admin_log("追评任务单条返款", "管理员{$this->admin_info['user_name']}操作:追评任务编号:{$list['task_number']}");
            if(!$res1)throw new Exception('操作日志写入失败!');
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('返款成功！');
    }



    /**
     * @notes 追评任务批量返款
     * @date 2019/9/25
     * @time 12:38
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reviewTaskReturnPays(Request $request){
        $data = $request->param();
        $data['ids']=explode(",",$data['ids']);
//        foreach ($data['data'] as $v){
//            $data['ids'][]=$v['id'];
//        }
        if(!$data['ids'] || !is_array($data['ids']))return $this->error('请选择要返款的任务单！');
        $num = 0;
        $numbers = [];
        foreach ($data['ids'] as $key=>$id){
            $list = Db::name('review_task')->where(['id'=>$id,'state'=>3])->field("id,seller_id,task_id,user_id,user_money,task_number,state")->find();
            if(!$list)continue;
            $data['price']=$list['user_money'];
            if(!$data['price'])continue;
            if($data['price']<=0)continue;
            $price = $data['price'];
            try{
                Db::startTrans();
                Db::name('users')->where(['id'=>$list['user_id']])->setInc('reward',$price);
                $user=Db::name('users')->where(['id'=>$list['user_id']])->find();
                if(!finance($user['id'],2,$price,2,7,"银锭:{$price},任务{$list['task_number']}返款"))throw new Exception('财务写入失败！');
                $task['confirm_time'] = time();
                $task['state'] = 4;
                Db::name('review_task')->where(['id'=>$list['id']])->update($task);
                $numbers[] = $list['task_number'];
                $num++;
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                continue;
            }
        }
        $nums = count($data['ids'])-$num;
        if($numbers){
            $numbers = implode(',',$numbers);
            admin_log("追评任务多条条返款", "管理员{$this->admin_info['user_name']}操作:任务编号:{$numbers}");
        }
        return $this->success("返款成功{$num}条，失败{$nums}条!");

    }
}
