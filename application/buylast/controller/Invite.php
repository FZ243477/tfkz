<?php
namespace app\buy\controller;
use think\Controller;
use think\Db;
use app\common\model\Sms; //引入手机验证码
use app\common\controller\Img;
use think\Request;
use think\Session;

class Invite extends Base
{
    public function index()
    {
        if(Db::name('seller_task')->where(['seller_id'=>$this->seller['id']])->count('id') > 0){
            $state = 1;
            $url = [
                'seller' => $_SERVER['HTTP_HOST']. '/index.php/seller/register/index/type/1/tjid/'.$this->seller['invite_code'],
                'user' => $_SERVER['HTTP_HOST']. '/index.php/buy/login/register/type/1/tjid/'.$this->seller['invite_code'],
            ];
        }else{
            $state = 0;
            $url = [
                'seller' => '',
                'user' => '',
            ];
        }
        $this->assign('menu','6-1');
        $this->assign('state',$state);
        $this->assign('url',$url);
        return view();
    }
    public function record()
    {
        $this->assign('menu','6-2');
        return view();
    }

    public function recordData(Request $request){
        $data = $request->param();
        $firse = ($data['page']-1)*$data['size'];
        $field = "id,create_time,mobile";
        if($data['state']==1){
            $table = "users";
            $tables = "user_reward_recharge";
        }else{
            $table = "seller";
            $tables = "seller_reward_recharge";
        }
        if($data['searchTime']){
            $map['create_time'] = ['between',[strtotime($data['searchTime'][0]),strtotime($data['searchTime'][1])]];
        }
        $map['tjuser'] = $this->seller['mobile'];
        $total = Db::name($table)->where($map)->count('id');
        $list = Db::name($table)->where($map)->limit($firse,$data['size'])->field($field)->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            $where['uid'] = $item['id'];
            $where['from_type'] = $data['state'];
            $where['from_type'] = $this->seller['id'];
            $item['num'] = Db::name($tables)->where($where)->count('id');
            $item['price'] = Db::name($tables)->where($where)->sum('price');
            if(!$item['price'])$item['price'] = 0;
        }
        $res = [
            'list'=>$list,
            'total'=>$total
        ];
        return $this->success('success','',$res);
    }
}