<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;
use app\common\model\Sms; //引入手机验证码
use app\common\controller\Img;
use think\Request;
use think\Session;

class Way extends Controller
{

    /*验证手机发送二维码*/
    public function send_code(){
        $data=input();
        $mobile=$data['mobile'];
        $res = Sms::send($mobile);
        $code=session('code');
        // return $this->error($code);
    }

}