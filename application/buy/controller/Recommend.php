<?php


namespace app\buy\controller;


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
        $system=db('system')->where('id',1)->find();
        if(Db::name('user_task')->where(['user_id'=>$this->id])->where('state',1)->count('id') >= $system['invitation_num'] ){
            $state = 1;
            $url = [
                'seller' => $_SERVER['HTTP_HOST']. '/index.php/seller/register/index/type/1/invite/'.$this->invite_code,
                'user' => $_SERVER['HTTP_HOST']. '/index.php/buy/login/register/type/1/invite/'.$this->invite_code,
            ];
        }else{
            $state = 0;
            $url = [
                'seller' => '',
                'user' => '',
            ];
        }
        $users=Db::name('users')->where('username','同福测试买手')->find();
        if($this->id == $users['id']){
            $state = 1;
            $url = [
                'seller' => $_SERVER['HTTP_HOST']. '/index.php/seller/register/index/type/1/invite/'.$this->invite_code,
                'user' => $_SERVER['HTTP_HOST']. '/index.php/buy/login/register/type/1/invite/'.$this->invite_code,
            ];
        }
        $this->assign('state',$state);
        $this->assign('url',$url);
        return view();
    }
    public function record()
    {
        $topnav=4;
        $this->assign('topnav',$topnav);//头部导航
        return view();
    }

    public function recordData(Request $request){
        $data = $request->param();
        $firse = ($data['page']-1)*$data['size'];


        if($data['state']==1){
            $str = '未接单';
            $table = "users";
            $field = "id,create_time,mobile,username,qq,vip_time,last_time";
        }else{
            $str = '未发单';
            $table = "seller";
            $field = "id,create_time,mobile,seller_name as username,qq,vip_time,last_time";
        }
        if($data['sortNum']==1){
            $order = "vip_time asc";
        }else{
            $order = "last_time asc";
        }
        if($data['searchTime']){
            $map['create_time'] = ['between',[strtotime($data['searchTime'][0]),strtotime($data['searchTime'][1])]];
        }
        $user=Db::name('users')->where('id',$this->id)->find();
        $map['tjuser'] = $user['username'];
        $total = Db::name($table)->where($map)->count('id');
        $list = Db::name($table)->where($map)->limit($firse,$data['size'])->order($order)->field($field)->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$item){
            if($data['state']==1){
                $num = Db::name('user_task')->where(['user_id'=>$item['id'],'state'=>1])->count('id');
            }else{
                $num = Db::name('seller_task')->where(['seller_id'=>$item['id'],'status'=>6])->count('id');
            }
            $where['uid'] = $user['id'];
            $where['from_type'] = $data['state'];
            $where['fromuser'] = $item['id'];
            $item['num'] = $num;
            $item['price'] = Db::name('user_reward_recharge')->where($where)->sum('price');
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

    /**
     * @Function 获取买手完成单量数据
     * @Author 扬风
     * @Date: 2020/3/2
     * @Time: 12:13
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCTask(Request $request){
        $data = $request->param();
        $firse = ($data['page']-1)*$data['size'];
        $where['user_id'] = $data['id'];
        $total = Db::name('user_invited')->where($where)->count('id');
        $list = Db::name('user_invited')->where($where)->limit($firse,$data['size'])->order('create_time desc')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as $key=>$item){
            $list[$key]['username'] = Db::name('users')->where(['id'=>$item['user_id']])->value('username');
            $list[$key]['month'] = date('Y-m',$item['create_time']);
        }
        $res = [
            'list'=>$list,
            'total'=>$total
        ];
        return $this->success('success','',$res);
    }

}