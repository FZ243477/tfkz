<?php


namespace app\seller\controller;


use think\Db;
use think\Request;

class Recommend extends Base
{
    /**
     * @notes  邀请链接页面
     * @date 2019/8/14
     * @time 14:20
     * @return \think\response\View
     * @throws \think\Exception
     */
    public function index()
    {
        if(Db::name('seller_task')->where(['seller_id'=>$this->seller['id'],'status'=>['in',[2,3,4,6]]])->count('id') > 0){
            $state = 1;
            $url = [
                'seller' => $_SERVER['HTTP_HOST']. '/index.php/seller/register/index/type/2/invite/'.$this->seller['invite_code'],
                'user' => $_SERVER['HTTP_HOST']. '/index.php/buy/login/register/type/2/invite/'.$this->seller['invite_code'],
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
        if($data['state']==1){
            $str = '未接单';
            $table = "users";
            $field = "id,create_time,username,mobile,vip_time,last_time,qq";
        }else{
            $str = '未发单';
            $table = "seller";
            $field = "id,create_time,seller_name as username,mobile,vip_time,last_time,qq";
        }
        if($data['sortNum']==1){
            $order = "vip_time asc";
        }else{
            $order = "last_time asc";
        }
        if($data['searchTime']){
            $map['create_time'] = ['between',[strtotime($data['searchTime'][0]),strtotime($data['searchTime'][1])]];
        }
        $map['tjuser'] = $this->seller['seller_name']; //推荐人用户名（当前用户名）
        $map['tjuser_state'] = 2; //推荐用户类型1买手2商家
        $total = Db::name($table)->where($map)->count('id');
        $list = Db::name($table)->where($map)->limit($firse,$data['size'])->order($order)->field($field)->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            if($data['state']==1){
                $num = Db::name('user_task')->where(['user_id'=>$item['id'],'state'=>1])->count('id');
            }else{
                $num = Db::name('seller_task')->where(['seller_id'=>$item['id'],'status'=>6])->count('id');
            }
            $where['uid'] = $this->seller['id'];
            $where['from_type'] = $data['state'];
            $where['fromuser'] = $item['id'];
            $where['type'] = 6;
            $item['num'] = $num;
            $item['price'] = Db::name('seller_reward_recharge')->where($where)->sum('price');
            $item['state'] = $item['vip_time'] < time() ? 1 : 0;
            $item['status'] = $item['last_time'] < (time()-(30*24*3600)) ? 1 : 0;
            $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            $item['vip_time'] = date('Y-m-d H:i:s',$item['vip_time']);
            $item['last_time'] = $item['last_time'] ? date('Y-m-d H:i:s',$item['last_time']) : $str;
            if(!$item['price'])$item['price'] = 0;
        }
        $res = [
            'list'=>$list,
            'total'=>$total
        ];
        return $this->success('success','',$res);
    }


}