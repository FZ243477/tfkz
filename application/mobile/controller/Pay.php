<?php


namespace app\mobile\controller;


use think\Db;
use think\Exception;
use think\Request;

class Pay extends Base
{
    public $config;
    public function _initialize(){
        parent::_initialize();
        $this->config = [
            //收款APPID号
            "appid"=>"3146220668",
            //对应的APPKEY密匙
            "appkey"=>"6e48af9cfe058e33e346941a4f83beef",
            //网关连接地址 一般不做修改
            "server"=>"http://yunpay.waa.cn/",   //注意：最后要加斜杠 /
            //支付成功后的跳转地址
            "reurl"=>"http://".$_SERVER['HTTP_HOST']."/mobile/my/index.html",
            //默认当前域名,可根据自己的需求自己开发
            //如果跳转需要带参数 请在AJAX页面自行组合并添加，这个只是一个返回效果并无数据返回
            //请用户不要误认为是异步数据通知的链接
            //获取客户IP(必须)
            "uip"=>$this->getIp(),
            //模板提示支付帮助 1提示 0不提示
            "helpts"=>1 ,
            "alipayh5"=>0 //是否开启自动生成二维码，开启后云端上传的二维码将失效
        ];
    }
    //获取客户端IP地址
    public function getIp()
    { //取IP函数
        static $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $realip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } else {
                $realip = getenv('HTTP_CLIENT_IP') ? getenv('HTTP_CLIENT_IP') : getenv('REMOTE_ADDR');
            }
        }
        $realip=explode(",",$realip);

        return $realip[0];
    }

    //数组拼接为url参数形式
    public function urlparams($params){
        $sign = '';
        foreach ($params AS $key => $val) {
            if ($val == '') continue;
            if ($key != 'sign') {
                if ($sign != '') {
                    $sign .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
            }
        }
        return $sign;
    }

    public function curl_post_https($url,$data){ // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据，json格式
    }

    public function parseurl($url="")
    {
        $url = rawurlencode(mb_convert_encoding($url, 'gb2312', 'utf-8'));
        $a = array("%3A", "%2F", "%40");
        $b = array(":", "/", "@");
        $url = str_replace($a, $b, $url);
        return $url;
    }

    public function codepay(Request $request){
        $datas = $request->param();
        $datas['type'] = 1;
        error_reporting(0);//PHP报错不显示
        header("content-Type: text/html; charset=Utf-8");
        //$a= floor($money);
        //将数据列入数组
        $yundata = array(
            "appid"  => $this->config['appid'],
            "data"   => $datas['data'],//网站订单号/或者账号
            "money"  => number_format($datas['money'],2,".",""),//注意金额一定要格式化否则token会出现错误
            "type"   => (int)$datas['type'],
            "uip"    => $this->config['uip'],
        );
        /*
        token签名规则 注意顺序不能乱
        //金额格式 例如正确格式(10.00  100.01  0.01)  金额必须格式化否则token签名会失败
        错误的格式 (10  20  500  2)
        */
        $token = array(
            "appid"  =>  $this->config['appid'],//APPID号码
            "data"   =>  $yundata["data"],//数据单号
            "money"  =>  $yundata["money"],//金额
            "type"   =>  $yundata["type"],//类别
            "uip"    =>  $this->config['uip'],//客户IP
            "appkey" =>  $this->config['appkey']//appkey密匙
        );
        /*
        token签名MD5加密
        将字符串进行MD5加密
        md5(appid=88888888&data=222222&money=100.00&type=1&uip=127.0.0.1&appkey=xxxxxxx)
        签名一律小写 例如 ：528a657d628395de403d4d152d658073
        */
        $token = md5($this->urlparams($token));
        $postdata = $this->urlparams($yundata).'&token='.$token;
        if($this->config['alipayh5']== 1  && $yundata["type"]== 1 ){//仅限支付宝
            //启用本地备注模式
            $order_data = base64_encode($yundata["data"].','.$yundata["money"]);//将数据进行base64编码
            $qrcode = 'http://'.$_SERVER['HTTP_HOST'].'/pay/alipayh5.php?data='.$order_data.'';//本地自动生成二维码地址
            $sdata = array('state'=>1,'qrcode'=>$qrcode,'youorder'=>$yundata["data"],'data'=>$yundata["data"],'money'=>$yundata["money"],'times'=>time() + 300,'orderstatus'=>0,'text'=>10089); //本地生成二维码可手动伪造JSON数据
        }else{
            //否则走云端
            $fdata = $this->curl_post_https($this->config['server'],$postdata);//发送数据到网关
            $sdata = json_decode($fdata, true);//将json代码转换为数组
        }
        /*返回的json参数
        {"state":"1","qrcode":"二维码","youorder":"token","data":"data","money":"10.00","times":"1531384783","orderstatus":"0","text":"10089"}
        state = 1 为成功获取二维码数据  0表示异常 请看错误代码
        */
        $state = $sdata["state"];//状态 1 ok   0有错误
        if(!$state){
            exit('异常'.$sdata["text"]);
        }
        $qrcode = $sdata["qrcode"];//二维码
        $times =  $sdata["times"] - time(); //有效时间减去当前时间 保留一分钟减去60秒
        $moneys = $sdata["money"];//实际付款金额
        if($sdata["money"]!=$datas['money']){
            Db::name('recharge')->where(['number'=>$datas['data']])->update(['price'=>$sdata["money"]]);
        }
        $orderstatus =$sdata["orderstatus"];//付款状态 1ok  0等待付款
        $data =$sdata["data"];//传递的订单号
        $order =$sdata["order"];//云端分配的唯一订单号 通过这个订单号查询状态
        if($yundata["type"]==1){
            $logo = '/pay/template/Image/zfb.png';
            $title = '支付宝';
            $text =  '支付宝扫一扫付款（手机上可以直接启动APP，或者截图相册识别）';
            $tishi = "<div style='position:relative;width:300px;height:341px;margin:0 auto;border:1px solid #e4e3e3'><img src='/pay/template/Image/zfbbg.png' alt='' /><div style='position:absolute;width:100px;height:100px;z-indent:2;left:35;top:210;font-size:48px;color:#F00'>{$moneys}</div></div>";
            //如果你只使用支付宝 固定金额 可以做成自动启动支付宝APP  具体查阅开发文档或询问技术
        }elseif($yundata["type"]==2){
            $logo = '/pay/template/Image/qq.png';
            $title = 'QQ钱包';
            $text = 'QQ钱包扫一扫付款（QQ中可长按识别，或者截图相册识别）';
            $tishi = '<div style="position:relative;width:300px;height:360px;margin:0 auto;border:1px solid #e4e3e3"><img src="/pay/template/Image/qqbg.png" alt="" /><div style="position:absolute;width:100px;height:100px;z-indent:2;left:35;top:220;font-size:48px;color:#F00">'.$moneys.'</div></div>';
        }elseif($yundata["type"]==3){
            $logo = '/pay/template/Image/wx.png';
            $title = '微信支付';
            $text = '微信扫一扫付款（微信中可长按识别，或者截图相册识别）';
            $tishi = '<div style="position:relative;width:300px;height:331px;margin:0 auto;border:1px solid #e4e3e3"><img src="/pay/template/Image/wxbg.png" alt="" /><div style="position:absolute;width:100px;height:100px;z-indent:2;left:65;top:200;font-size:48px;color:#F00">'.$moneys.'</div></div>';
        }elseif($yundata["type"]==4){
            $logo = '/pay/template/Image/ysf.png';
            $title = '云闪付';
            $text = '银联云闪付扫一扫付款';
            $tishi = '';
        }
        $this->assign('logo',$logo);
        $this->assign('title',$title);
        $this->assign('text',$text);
        $this->assign('tishi',$tishi);
        $this->assign('yundata',$yundata);
        $this->assign('config',$this->config);
        $this->assign('order',$order);
        $this->assign('data',$data);
        $this->assign('times',$times);
        $this->assign('moneys',$moneys);
        $this->assign('qrcode',$qrcode);
        return view();
    }
}
