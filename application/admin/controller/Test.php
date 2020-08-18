<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Cache;
use think\Exception;
use think\Request;
use think\Session;
use PHPExcel_IOFactory;
use PHPExcel;
use think\session\driver\Redis;
use app\common\controller\aliyunOss;

/**
 * 会员登录
 * Class Login
 * @package app\admin\controller
 */
class Test extends Controller
{
    
      public function img(){
           return $this->fetch('Test/upimg');
        return view();
    }
    
    public function testup(){
       $file = request()->file('image');  //获取到上传的文件
       $arr =aliyunOss::uploadFile($file);
         dump($arr);exit;
        
    }
    
    /**
     * Returns the url query as associative array
     *
     * @param    string    query
     * @return    array    params
     */
    function convertUrlQuery()
    {
        $query="https://item.taobao.com/item.htm?spm=a21ag.7623864.0.0.45df3ba5F4Doqn&id=603687399746";
        $data=parse_url($query);
        $query=$data['query'];
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param)
        {

            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        dump($params);exit;
        return $params;
    }

    function getUrlQuery($array_query)
    {
        $tmp = array();
        foreach($array_query as $k=>$param)
        {
            $tmp[] = $k.'='.$param;
        }
        $params = implode('&',$tmp);
        return $params;
    }


    public function login()
    {
        $data = input();
        $ip = request()->ip();
        if (!isset($data['user_name']) || empty($data['user_name'])) {
            return $this->error('请输入用户名!');
        }

        if (!isset($data['password']) || empty($data['password'])) {
            return $this->error('请输入密码!');
        }
        $info = db('admin_user')->where('user_name', $data['user_name'])->field('id,user_name,password,state,logins_num')->find();//查询会员信息
        if (empty($info)) {
            return $this->error('会员账号未注册!');
        }
        if ($info['state'] == 0) {
            return $this->error('已被限制登录!');
        }
        if (md5($data['password']) == $info['password']) {
            Session::set('admin_id', $info['id']);
            Session::set('admin_name', $info['user_name']);
            $updata['logins_ip'] = $ip;
            $updata['logins_num'] = $info['logins_num'] + 1;
            db('admin_user')->where('user_name', $data['user_name'])->update($updata);
            $this->redirect(url('index/index'));//登录成功！
        } else {
            return $this->error('密码错误!');
        }
    }



    public function test()
    {

        //$post_data['tkl'] = "fu至这行话$5vjO1T1c7Rt$转移至τáǒЬáǒ【后现代轻奢真皮休闲椅子头层牛皮现代简约靠背单人沙发椅家用港式】；或https://m.tb.cn/h.VeBX5pg?sm=294e5d 掂击鏈→接，再选择瀏→覽→嘂..咑№亓";
        $post_data['tkl'] ='复制这段话$9bSD1mIztOj$转移至淘宝或掂击鏈→接https://m.tb.cn/h.ViSAxQh?sm=6b9247 至瀏..覽..噐【TTP原创 Nigel&Mihara联名款 余文乐同款MMY风溶解胶底低帮帆布鞋】';
        $post_data['apikey'] = "mN1phT7B23rkpX2BGowVVDiJWNaKAhah";
        $dd = $this->http_curl('http://api.tbk.dingdanxia.com/tkl/query', $post_data);
       dump(json_decode($dd)->data);
        exit;
    }


    public function http_curl($url, $post_data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $data;
    }

    /**
     * 退出登录
     */
    public function out()
    {
        session('admin_id', 'null');
        session('admin_name', 'null');
        $this->success("退出成功！", 'login/index');
    }

    /**
     * 数据导出
     * @param array $title 标题行名称
     * @param array $data 导出数据
     * @param string $fileName 文件名
     * @param string $savePath 保存路径
     * @param $type   是否下载  false--保存   true--下载
     * @return string   返回文件全路径
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */

    function ceshi(){
        exportExcel(array('姓名','年龄'), array(array('a',21),array('b',23)), '档案', './', true);
    }
    function exportExcel($title = array(), $data = array(), $fileName = '', $savePath = './Excel/', $isDown = true)
    {
        /** Include PHPExcel_IOFactory */
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.IOFactory");
        $obj = new \PHPExcel();
        var_dump($obj);
        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称
        $_row = 1;   //设置纵向单元格标识
        if ($title) {
            $_cnt = count($title);
            $obj->getActiveSheet(0)->mergeCells('A' . $_row . ':' . $cellName[$_cnt - 1] . $_row);   //合并单元格
            $obj->setActiveSheetIndex(0)->setCellValue('A' . $_row, '数据导出：' . date('Y-m-d H:i:s'));  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach ($title AS $v) {   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i] . $_row, $v);
                $i++;
            }
            $_row++;
        }
        //填写数据
        if ($data) {
            $i = 0;
            foreach ($data AS $_v) {
                $j = 0;
                foreach ($_v AS $_cell) {
                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $_row), $_cell);
                    $j++;
                }
                $i++;
            }
        }
        //文件名处理
        if (!$fileName) {
            $fileName = uniqid(time(), true);
        }
        $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        if ($isDown) {   //网页下载
            header('pragma:public');
            header("Content-Disposition:attachment;filename=$fileName.xls");
            $objWrite->save('php://output');
            exit;
        }
        $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
        $_savePath = $savePath . $_fileName . '.xlsx';
        $objWrite->save($_savePath);
        return $savePath . $fileName . '.xlsx';
    }



    /**
     * @notes 支付宝支付回调
     * @date 2019/8/2
     * @time 11:32
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function alipayNotify(Request $request){
        $data = $request->param();
        $tradeNo = isset($data['tradeNo'])?$data['tradeNo']:'';
        $Money = isset($data['Money'])?$data['Money']:0;
        $title = isset($data['title'])?$data['title']:'';
        $memo = isset($data['memo'])?$data['memo']:'';
        $alipay_account = isset($data['alipay_account'])?$data['alipay_account']:'';
        $Gateway = isset($data['Gateway'])?$data['Gateway']:'';
        $Sign = isset($data['Sign'])?$data['Sign']:'';
        if(strtoupper(md5(config('alipay.alidirect_pid') . config('alipay.alidirect_key') . $tradeNo . $Money . $title . $memo)) == strtoupper($Sign)){
            //MD5签名验证通过后，判断订单是否存在，判断订单是否已处理，判断订单金额是否与通知中的金额一致
            //全部验证通过后即可做到账处理，并修改订单状态为已处理
            //以下为示例，您可以删除示例代码编写您自己的代码
            //==================================================================================================
            $result = Db::name('recharge')->where(['number'=>$title,'price'=>$Money,'state'=>0])->find();//读取支付前保存的订单信息
            if(!$result) exit('IncorrectOrder');//订单不存在
            if($result['price']!=$Money) exit('Fail');//实际支付金额不匹配
            Db::startTrans();
            try{
                $recharge_update['trade_no'] = $tradeNo;
                $recharge_update['state'] = 1;
                Db::name('recharge')->where(['id'=>$result['id']])->update($recharge_update);
                if(!in_array($result['type'],[1,2]))throw new Exception('error');
                if(!in_array($result['user_type'],[1,2]))throw new Exception('error');
                if($result['type']==2){
                    if($result['user_type']==1){
                        $sys = Db::name('system')->value('seller_vip');
                        $sys = explode(',',$sys);
                        $vip = 0;
                        foreach ($sys as $key=>$val){
                            if($Money==$val) $vip = $key + 1;
                        }
                        if(!$vip)throw new Exception('购买金额不存在');
                        $seller = Db::name('seller')->where(['id'=>$result['uid']])->find();
                        $update['vip'] = 1;
                        $update['vip_time'] = $seller['vip_time'] + ($vip * 90 * 24 * 3600);
                        if($seller['vip_time'] < time())$update['vip_time'] = time() + ($vip * 90 * 24 * 3600);
                        Db::name('seller')->where(['id'=>$result['uid']])->update($update);
                    }
                    if($result['user_type']==2){
                        $sys = Db::name('system')->value('user_vip');
                        $sys = explode(',',$sys);
                        $vip = 0;
                        foreach ($sys as $key=>$val){
                            if($Money==$val)$vip = $key + 1;
                        }
                        if(!$vip)throw new Exception('error');
                        $users = Db::name('users')->where(['id'=>$result['uid']])->find();
                        $update['vip'] = 1;
                        $update['vip_time'] = $users['vip_time'] + ($vip * 90 * 24 * 3600);
                        if($users['vip_time'] < time())$update['vip_time'] = time() + ($vip * 90 * 24 * 3600);
                        Db::name('users')->where(['id'=>$result['uid']])->update($update);
                    }
                    $m = $vip * 3;
                    $time = date('Y-m-d H:i:s',$update['vip_time']);
                    $memo = "购买{$m}个月会员使用支付宝支付{$Money}元,到期时间{$time}";
                    if(!$this->writeVip($Money,$memo,$update['vip_time'],$result['uid'],$result['user_type']))throw new Exception('error');
                }else{
                    $str = "银锭";
                    if($result['user_type']==1){//商家
                        $seller = Db::name('seller')->where(['id'=>$result['uid']])->find();
                        if(!in_array($result['recharge_type'],[1,2]))throw new Exception('error');
                        if($result['recharge_type']==1){//押金
                            $seller_update['balance'] = $seller['balance'] + $Money;
                            $str = "押金";
                        }
                        if($result['recharge_type']==2){//银锭
                            $seller_update['reward'] = $seller['reward'] + $Money;
                        }
                        Db::name('seller')->where(['id'=>$result['uid']])->update($seller_update);
                    }
                    if($result['user_type']==2){//买手
                        $users = Db::name('users')->where(['id'=>$result['uid']])->find();
                        if(!in_array($result['recharge_type'],[1,2]))throw new Exception('error');
                        if($result['recharge_type']==1){//本金
                            $str = "本金";
                            $users_update['balance'] = $users['balance'] + $Money;
                        }
                        if($result['recharge_type']==2){//银锭
                            $users_update['reward'] = $users['reward'] + $Money;
                        }
                        Db::name('users')->where(['id'=>$result['uid']])->update($users_update);
                    }
                    if(!finance($result['uid'],$result['user_type'],$Money,$result['recharge_type'],2,"支付宝：充值{$str}支付{$Money}元"))throw new Exception('error');
                }
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                Db::name('test')->insert(['test'=>$e]);
                Db::name('test')->insert(['test'=>$this->seller]);

                exit('error');
            }
            exit('Success');//支付成功
            //==================================================================================================
        }else{
            exit('Fail');//Sign签名验证失败
        }
    }

    /**
     * @notes vip充值记录写入
     * @date 2019/7/29
     * @time 16:21
     * @param $price 金额
     * @param $memo 描述
     * @param $vip_time 到期时间
     * @return int|string
     */
    private function writeVip($price,$memo,$vip_time,$uid,$utype){
        if($utype==1){//商家
            $user_name = Db::name('seller')->where(['id'=>$uid])->value('seller_name');
        }else{//买手
            $user_name = Db::name('users')->where(['id'=>$uid])->value('username');
        }
        $add['uid'] = $uid;
        $add['utype'] = $utype;
        $add['user_name'] = $user_name;
        $add['price'] = $price;
        $add['remarks'] = $memo;
        $add['expire_time'] = $vip_time;
        $add['create_time'] = time();
        $res = Db::name('vip_record')->insert($add);
        return $res;
    }


    public  function fahuo(){
        $list=db('user_task')->select();
        foreach ($list as $v){
            $sellertask=db('seller_task')->where('id',$v['seller_task_id'])->value('shop_id');
            db('user_task')->where('id',$v['id'])->update(['shop_id'=>$sellertask]);

        }
    }


    public function goods_test(){
        exit;
        $list = Db::name('seller_task')->where(['id'=>['gt',4288]])->select()->toArray();
        $num=0;
        foreach ($list as $item){
            $num++;
            $goods_id = json_decode($item['goods_id']);
            $goods_spec = Db::name('goods')->where(['id'=>['in',$goods_id]])->field('spec_name,spec_value')->select()->toArray();
            $add['goods_spec'] = '';
            foreach ($goods_spec as $spec){
                $add['goods_spec'] .= $add['goods_spec'] ? '{}'.$spec['spec_name'].'/'.$spec['spec_value'] : $spec['spec_name'].'/'.$spec['spec_value'];
            }
            Db::name('seller_task')->where(['id'=>$item['id']])->update($add);
            if($num==500){
                dump($item['id']);
                exit;
            }
        }
        dump($num);
    }


    public function text_task(){
        exit;
        $list = Db::name('seller_task')->where(['margin'=>['gt',0],'status'=>['neq',5]])->field('id,task_number')->select();
        $list = $list ? $list->toArray() : [];
        dump($list);
    }

    public function test_redis(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->lpush("test","111");
        $redis->lpush("test","222");
        dump($redis->get('test'));
    }
    //发货地址
    public  function fajianaddress(){
        $list=db('shop')->select();
        foreach ($list as $v){
            $add['sheng'] = $v['province'];
            $add['shi'] = $v['city'];
            $add['qu'] = $v['area'];
            db('shop')->where('id',$v['id'])->update($add);

        }
        dump(111);exit;
    }

}
