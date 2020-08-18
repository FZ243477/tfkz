<?php
namespace app\seller\controller;
use app\common\controller\Img;
use app\seller\model\Message;
use think\Db;
use think\Exception;
use think\Image;
use think\Request;

class Index extends Base
{
    /**
     * @notes 商家首页
     * @date 2019/7/26
     * @time 11:15
     * @return \think\response\View
     */
    public function index()
    {
        $num = Db::name('users')->where(['tjuser'=>$this->seller['seller_name'],'tjuser_state'=>2])->count();
        $num1 = Db::name('seller')->where(['tjuser'=>$this->seller['seller_name'],'tjuser_state'=>2])->count();
        $num_day = Db::name('users')->where(['tjuser'=>$this->seller['seller_name'],'tjuser_state'=>2,'create_time'=>['gt',strtotime(date('Y-m-d'))]])->count();
        $num_day1 =  Db::name('seller')->where(['tjuser'=>$this->seller['seller_name'],'tjuser_state'=>2,'create_time'=>['gt',strtotime(date('Y-m-d'))]])->count();
        $this->assign('num',($num+$num1));
        $this->assign('num_day',($num_day+$num_day1));
        $this->assign('seller',$this->seller);
        return view();
    }

    /**
     * @notes 商家信息页面
     * @date 2019/7/26
     * @time 11:20
     * @return \think\response\View
     */
    public function info(){
        $this->seller['vip_time'] = date('Y-m-d',$this->seller['vip_time']);
        $this->assign('seller',json_encode($this->seller));
        $this->assign('menu','1-1');
        return view();
    }

    /**
     * @notes 商家修改手机号
     * @date 2019/8/6
     * @time 15:17
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function editMobileDo(Request $request){
        $data = $request->param();
        $check = '/^1[3-9]{1}[0-9]{9}$/';
        if (!preg_match($check, $data['mobile']))return $this->error('手机号码不正确');
        if($data['mobile'] != session('mobile'))return $this->error('手机号码必须为接受验证码的手机号');
        if($data['code'] != session('code'))return $this->error('验证码不正确');
        if(!isset($data['pay_pwd']) || !$data['pay_pwd'])return $this->error('支付密码不能为空');
        if(!Db::name('seller')->where(['id'=>$this->seller['id'],'pay_pwd'=>md5($data['pay_pwd'])])->field('id')->find())return $this->error('支付密码不正确');
        if(!Db::name('seller')->where(['id'=>$this->seller['id']])->update(['mobile'=>$data['mobile']]))return $this->error('修改失败');
        session('code',null);
        session('mobile',null);
        return $this->success('修改成功');
    }
    public function editQqDo(Request $request){
        $data = $request->param();
        if(!$data['qq'])return $this->error('请输入qq号');
        if(!Db::name('seller')->where(['id'=>$this->seller['id']])->update(['qq'=>$data['qq']]))return $this->error('修改失败');
        return $this->success('修改成功');
    }
    public function editNameDo(Request $request){
        $data = $request->param();
        if(!$data['seller_name'])return $this->error('请输入昵称');
        if(!Db::name('seller')->where(['id'=>$this->seller['id']])->update(['seller_name'=>$data['seller_name']]))return $this->error('修改失败');
        return $this->success('修改成功');
    }

    /**
     * @notes 修改登录密码
     * @date 2019/8/6
     * @time 16:39
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function editLoginPwdDo(Request $request){
        $data = $request->param();
        if($data['mobile'] != session('mobile'))return $this->error('手机号不正确');
        if(!Db::name('seller')->where(['id'=>$this->seller['id'],'mobile'=>$data['mobile']])->field('id')->find())return $this->error('手机号码不正确');
        if($data['code'] != session('code'))return $this->error('验证码不正确');
        if(!isset($data['login_pwd']) || !$data['login_pwd'])return $this->error('登录密码不能为空');
        if(!Db::name('seller')->where(['id'=>$this->seller['id']])->update(['login_pwd'=>md5($data['login_pwd'])]))return $this->error('修改失败');
        session('code',null);
        session('mobile',null);
        return $this->success('修改成功');
    }

    /**
     * @notes 修改支付密码
     * @date 2019/8/6
     * @time 16:39
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function editPayPwdDo(Request $request){
        $data = $request->param();
        if($data['mobile'] != session('mobile'))return $this->error('手机号不正确');
        if(!Db::name('seller')->where(['id'=>$this->seller['id'],'mobile'=>$data['mobile']])->field('id')->find())return $this->error('手机号码不正确');
        if($data['code'] != session('code'))return $this->error('验证码不正确');
        if(!isset($data['pay_pwd']) || !$data['pay_pwd'])return $this->error('支付密码不能为空');
        if(!Db::name('seller')->where(['id'=>$this->seller['id']])->update(['pay_pwd'=>md5($data['pay_pwd'])]))return $this->error('修改失败');
        session('code',null);
        session('mobile',null);
        return $this->success('修改成功');
    }



    /**
     * @notes 银行卡信息页面
     * @date 2019/7/26
     * @time 15:39
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bank(){
        $this->assign('menu','1-2');
        if(request()->isAjax()){
            $seller_bank = Db::table('tfkz_seller_bank')->alias('a')->join('tfkz_bank b','b.id = a.bank_id')->where(['a.uid'=>$this->seller['id']])->field('a.*,b.img,b.name')->select();
            $bank = Db::name('bank')->select();
            $list = [
                'seller_bank'=>$seller_bank,
                'bank'=>$bank,
            ];
            return $this->success('success','',$list);
        }
        return view();
    }

    /**
     * @notes 商家提现银行卡添加
     * @date 2019/7/27
     * @time 16:30
     * @param Request $request
     */
    public function bankAdd(Request $request){
        $data = $request->param();
        $data['bank_number'] = trim($data['bank_number']);
        $result = $this->validate($data,'Bank.add');
        if(true !== $result){
            return $this->error($result);
        }
        Db::startTrans();
        try{
            if(!$data['idcard_img']) throw new Exception('请上传身份证正面照！');
            $img = Img::base64_image_content($data['idcard_img'],"./uploads/info/");
            if($img['code']==0)throw new Exception('身份证'.$img['data']);
            $add['idcard_img'] = $img['data'];
            $add['bank_seller'] = $data['bank_seller'];
            $add['branch_name'] = $data['branch_name'];
            $add['mobile'] = $data['mobile'];
            $add['idcard'] = $data['idcard'];
            $add['bank_number'] = $data['bank_number'];
            $add['uid'] = $this->seller['id'];
            $add['bank_id'] = $data['bank_id'];
            $add['province'] = $data['province'];
            $add['city'] = $data['city'];
            $add['create_time'] = time();
            Db::name('seller_bank')->insert($add);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('添加成功');

    }

    /**
     * @notes 商家提现银行卡信息修改
     * @date 2019/7/27
     * @time 16:35
     * @param Request $request
     */
    public function bankEdit(Request $request){
        $data = $request->param();
        $result = $this->validate($data,'Bank.edit');
        if(true !== $result){
            return $this->error($result);
        }
        Db::startTrans();
        try{
            if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['idcard_img'], $result)){
                if(!$data['idcard_img']) throw new Exception('请上传身份证正面照！');
                $img = Img::base64_image_content($data['idcard_img'],"./uploads/info/");
                if($img['code']==0)throw new Exception('身份证'.$img['data']);
                $edit['idcard_img'] = $img['data'];
            }
            $edit['bank_seller'] = $data['bank_seller'];
            $edit['branch_name'] = $data['branch_name'];
            $edit['mobile'] = $data['mobile'];
            $edit['idcard'] = $data['idcard'];
            $edit['bank_number'] = $data['bank_number'];
            $edit['uid'] = $this->seller['id'];
            $edit['bank_id'] = $data['bank_id'];
            $edit['province'] = $data['province'];
            $edit['city'] = $data['city'];
            $edit['state'] = 0;
            $edit['update_time'] = time();
            Db::name('seller_bank')->where(['id'=>$data['id']])->update($edit);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('修改成功');

    }



    public function pay(){
        $optEmail = config('alipay.alidirect_account');//isset($_REQUEST["optEmail"]) ? $_REQUEST["optEmail"] : "";
        $payAmount = 0.01;//isset($_REQUEST["payAmount"]) ? $_REQUEST["payAmount"] : "";
        $title = time();//isset($_REQUEST["title"]) ? $_REQUEST["title"] : "";
        $memo = '';//isset($_REQUEST["memo"]) ? $_REQUEST["memo"] : "";
        $act = '';//isset($_REQUEST["act"]) ? $_REQUEST["act"] : "";
        $this->assign('optEmail',$optEmail);
        $this->assign('payAmount',$payAmount);
        $this->assign('title',$title);
        $this->assign('memo',$memo);
        $this->assign('act',$act);
        return view();
    }

    /**
     * @notes 头部消息
     * @date 2019/10/25
     * @time 15:35
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function msg(Request $request){
        $data = $request->param();
        $first = ($data['page'] - 1) * 5;
        $num = Db::name('message')->where(['type'=>2,'user_id'=>$this->seller['id'],'state'=>1,'look'=>0])->count('id');
        $total = Db::name('message')->where(['type'=>2,'user_id'=>$this->seller['id'],'state'=>1])->count('id');
        $list = Message::where(['type'=>2,'user_id'=>$this->seller['id'],'state'=>1])->order("id desc")->limit($first,5)->select();
        $list = $list ? $list->toArray() : [];
        $res = [
            'list'=>$list,
            'total'=>$total,
            'num'=>$num,
        ];
        return $this->success('success','',$res);
    }

    /**
     * @notes 已读消息设置
     * @date 2019/10/25
     * @time 15:36
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function setMsg(Request $request){
        $data = $request->param();
        Db::name('message')->where(['id'=>$data['id']])->update(['look'=>1]);
        $num = Db::name('message')->where(['type'=>2,'user_id'=>$this->seller['id'],'state'=>1,'look'=>0])->count('id');
        return $this->success('success','',$num);
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
        if($data['is_edit']==1){
            $where['id'] = ['neq',$data['id']];
        }
        $where['mobile'] = $data['mobile'];
        if(Db::name('seller_bank')->where($where)->find())return $this->error('手机号已被使用，请更换');
        return $this->success('手机号没有被使用');
    }
}
