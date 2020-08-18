<?php


namespace app\common\model;


use think\Model;

class Api extends Model
{
    /**
     * @notes
     * @date 2019/9/26
     * @time 14:04
     * @param string $task_id  订单id
     * @param string $no  订单号
     * @param int $type 执行类型0发送短信1自动取消
     * @param string $mobile 手机号码
     * @param string $time 执行时间
     * @param string $memo 短信内容
     */
    static public function api($task_id='1',$no='2',$type=1,$mobile='18326553446',$time='1',$memo='2'){
        $post_data = [
            'password'=>md5('tfapi'),
            'task_id'=>$task_id,
            'no'=>$no,
            'type'=>$type,
            'mobile'=>$mobile,
            'time'=>$time,
            'memo'=>$memo,
        ];
        $pwd = md5('tfapi');
        $url = 'http://tfkz.rxzly.cc/index.php';
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt ( $ch, CURLOPT_POST, 1 ); //启用POST提交
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
        $file_contents = curl_exec ( $ch );
        curl_close ( $ch );
        return json_decode($file_contents);
    }
}