<?php


namespace app\seller\controller;


use think\Controller;
use think\Db;
use think\Cache;
use think\Exception;
use think\Request;

class Test extends Controller
{

    public function index(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        dump($redis->keys('*'));
        $redis->rpush("test_test","333");
        $redis->expire ("test_test",5);
        dump($redis->ttl ("test_test"));
        //dump($redis->lrange("1575943490889014",0,-1));
        /*foreach($redis->keys('*') as $item){
            $redis->del($item);
        }*/
        //dump($redis->keys('*'));
        exit;

        $redis->rpush("test_test","333");
        $redis->rpush("test_test","333");
        $redis->rpush("test_test","444");
        $redis->rpush("test_test","555");
        dump($redis->lrange("test_test",0,-1));
        $redis->lrem('test_test','333',0);
        $redis->lrem('test_test','555',0);
        dump($redis->lrange("test_test",0,-1));
    }

    public function test(){
        exit;
        $where['id'] = ['between',[1201,2500]];
        $list = Db::name('users')->where($where)->select()->toArray();
        $s = 365 * 24 * 3600;
        foreach($list as $item){
            if($item['vip_time'] > time()){
                $update['vip_time'] = $item['vip_time'] + $s;
            }else{
                $update['vip_time'] = time() + $s;
            }
            $update['vip'] = 1;
            Db::name('users')->where(['id'=>$item['id']])->update($update);
            $add['type'] = 1;
            $add['user_id'] = $item['id'];
            $add['title'] = '会员通知';
            $add['content'] = '亲爱的各位买手：因面对突如其来的新冠肺炎疫情，牵动着全国人民的心。各行各业都在全力奋战，共抗疫情。考虑各位的不易，现赠送一波大福利——赠送所有买手一年会员！一年会员！这是史无前例的，针对已续费会员的买手，我们将顺延会员时长。大家积极邀请徒弟，奖励机制不变，疫情当前，平台和所有买手一起共克时艰！师傅可在账户邀请记录里查询自己所邀请的徒弟，并查看做单记录，对于徒弟未做单成功的，师傅也要积极跟踪询问。';
            $add['create_time'] = time();
            $add['state'] = 1;
            $add['author'] = 'admin';
            $add['admin_id'] = 1;
            Db::name('message')->insert($add);
        }
    }

    public function zpedit(){
        exit;
        $list = Db::name('review_task')->select()->toArray();
        foreach($list as $item){
            $update['pay_price'] = Db::name('user_task')->where(['id'=>$item['user_task_id']])->value('seller_principal');
            Db::name('review_task')->where(['id'=>$item['id']])->update($update);
        }
    }

    public function task_edit(){
     exit;
        $list = Db::name('seller_task')->where(['goods_number'=>0,'id'=>['between',[0,3000]]])->select()->toArray();
        foreach($list as $key=>$v){
            if($v['is_shengji']==1){
                $edit['goods_number'] = array_sum(json_decode($v['goods_num']));
                $edit['goods_z_price'] = array_sum(json_decode($v['goods_unit_price']));
            } else{
                $edit['goods_number'] = db('task_goods')->where(['task_id'=>$v['id']])->sum('num');
                $edit['goods_z_price'] = db('task_goods')->where(['task_id'=>$v['id']])->sum('price');;
            }
            Db::name('seller_task')->where(['id'=>$v['id']])->update($edit);
        }
    }

    public function tongji(){
        $list = Db::name('users')->where(['mc_task_num'=>['gt',0]])->select()->toArray();
        dump(count($list));exit;
        $times = strtotime(date('Y-m'));
        foreach ($list as $item){
            $num = Db::name('user_task')->where(['state'=>1,'complete_time'=>['gt',$times],'user_id'=>$item['id']])->count('id');
            Db::name('users')->where(['id'=>$item['id']])->update(['mc_task_num'=>$num]);
        }
    }


    /**
     * Function 获取1688链接参数
     * User 扬风
     * Date 2020/6/21
     * Time 6:13 PM
     */
    public function parse_str($str=''){
        $arr = parse_url($str);
        if(!isset($arr['path']))return false;
        $str = str_replace('.html','',$arr['path']);
        $str = str_replace('offer','',$str);
        $str = str_replace('/','',$str);
        return $str;
    }

    /**
     * @notes 识别链接id
     * @date 2019/10/15
     * @time 13:56
     * @param string $query 链接
     * @return array|bool
     */
    public function convertUrlQuery($query="https://item.taobao.com/item.htm?id=603687399746")
    {
        $url_info = parse_url($query);
        if(!isset($url_info['query']))return false;
        $queryParts = explode('&', $url_info['query']);
        $params = array();
        foreach ($queryParts as $param)
        {

            $item = explode('=', $param);
            if(isset($item[0])){
                if(!isset($item[1]))$item[1]='';
                $params[$item[0]] = $item[1];
            }
        }
        return $params;
    }

}