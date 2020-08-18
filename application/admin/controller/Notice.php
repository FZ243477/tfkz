<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Session;
use app\common\model\Notice as NoticeTime;

class Notice extends Base
{


    //买家公告
    public function BuyerBulletin(){
        if(request()->isPost()) {
            $date=input();
            return $this->notice_date(1,$date);
        }
        return view();
    }

    //买家常见问题
    public function BuyerProblem(){
        if(request()->isPost()) {
            $date=input();
            return $this->notice_date(2,$date);
        }
        return view();
    }
    //商家公告
    public function BusinessBulletin(){
        if(request()->isPost()) {
            $date=input();
            return $this->notice_date(3,$date);
        }
        return view();
    }
    //商家常见问题
    public function BusinessProblems(){
        if(request()->isPost()) {
            $date=input();
           return $this->notice_date(4,$date);
        }
        return view();
    }
//公告列表数据
    private function notice_date($type='1',$date){
            $page=$date['page'];//页数
            $limit=$date['limit'];//每页条数
            $count=db('notice')->where('type',$type)->count('id');
            $notice_list=NoticeTime::where('type',$type)->limit(($page-1)*$limit,$limit)->field('id,title,state,create_time,newstop,content')->order('id desc')->select()->toArray();
            // $this->assign('notice_list',$notice_list);
            return json(['code'=>0,'count'=>$count,'msg'=>'获取数据成功','data'=>$notice_list]);
    }

    /**
     * 添加公告
     */
public function newsAdd(){
    $type=input('type');
    if(request()->isPost()){
    $data=input();
    if(!$data['title']){
        $this->error('请填写标题！');
    }
    if(!$data['content']){
        $this->error('请填写内容！');
    }
    if($data['id']){
        $notice_data['title']=$data['title'];
        $notice_data['content']=$data['content'];
        $notice_data['newstop']=$data['newsTop'];
        $notice_data['update_time']=time();
        $mag="修改";
        $res=db('notice')->where('id',$data['id'])->update($notice_data);
    }else{
        $notice_data['type']=$data['type'];
        $notice_data['title']=$data['title'];
        $notice_data['content']=$data['content'];
        //$notice_data['state']=$data['state'];
        $notice_data['admin_id']=session('admin_id');
        $notice_data['newstop']=$data['newsTop'];
        $notice_data['create_time']=time();
        $mag="添加";
        $res=db('notice')->insert($notice_data);
    }
    if($res){
        $this->add_news($data['type']);
        $res1=admin_log("公告{$mag}", "公告{$mag}");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        $this->success("{$mag}成功!");
    }else{
        $this->error("{$mag}失败！");
    }
    }
    $this->assign('type',$type);
    return view();
}

    /**
     * 发公告时给会员和商家发消息
     */
    public function add_news($type){
        if($type==1 || $type==2){
            $u_type=1;
        }elseif($type==3 || $type==4){
            $u_type=2;
        }
        if ($type == 1) {
            $title = "买家公告";
        } elseif ($type == 2) {
            $title = "买家常见问题";
        } elseif ($type == 3) {
            $title = "商家公告";
        } elseif ($type == 4) {
            $title = "商家常见问题";
        }
        if($u_type==1){
            $u_list=Db::name('users')->select();
        }elseif($u_type==2){
            $u_list=Db::name('seller')->select();
        }
        foreach ($u_list as $v){
            $addmessage=[
                'type'=>$u_type,
                'title'=>"您有新的未读公告",
                'content'=>"您有新{$title}！请查看。",
                'create_time'=>time(),
                'state'=>1,
                'user_id'=>$v['id'],
                'author'=>session('admin_name'),
                'admin_id'=>session('admin_id')
            ];
            Db::name('message')->insertGetId($addmessage);
        }


    }

    /**
     * 修改公告
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
public function edit_notice(){
    $data=input();
    if(isset($data['id']) || empty($data['id'])){
        $this->error('参数错误！');
    }
    if(isset($data['title']) || empty($data['title'])){
        $this->error('请填写标题！');
    }
    if(isset($data['content']) || empty($data['content'])){
        $this->error('请填写内容！');
    }
    $notice_data['title']=$data['title'];
    $notice_data['type']=$data['type'];
    $notice_data['content']=$data['content'];
    $notice_data['state']=$data['state'];
    //$notice_data['admin_id']=session('damin_id');
    $notice_data['sort']=$data['sort'];
    $notice_data['update_time']=time();
    $res=db('notice')->where('id',$data['id'])->update($notice_data);
    if($res){
        $this->success('修改成功!');
    }else{
        $this->error('修改失败！');
    }
}

    /**
     * 删除公告
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
public function delete_notice(){
    $data=input();
    if(!$data['id']){
        $this->error('参数错误！');
    }
    $res=db('notice')->where('id',$data['id'])->delete();
    if($res){
        $res1=admin_log("公告删除", "公告删除");
        if(!$res1){
            return $this->error('操作日志写入失败！');
        }
        $this->success('删除成功!');
    }else{
        $this->error('删除失败！');
    }
}


    /**
     * 公告置顶
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function top_notice(){
        $data=input();
        if(!$data['id']){
            $this->error('参数错误！');
        }
        $res=db('notice')->where('id',$data['id'])->find();
        if(!$res){
            $this->error('公告不存在！');
        }
        if($res['newstop']=='false'){
            $res1=db('notice')->where('id',$data['id'])->update(['newstop'=>'true']);
        }elseif ($res['newstop']=='true'){
            $res1=db('notice')->where('id',$data['id'])->update(['newstop'=>'false']);
        }
        if($res1){
            $this->success('修改成功!');
        }else{
            $this->error('修改失败！');
        }
    }

    public function addImg(Request $request){
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $path = 'uploads' . DS . 'notice';
        $info = $file->validate(['size'=>2*1024*1024,'ext'=>'jpg,png,jpeg'])->move(ROOT_PATH . 'public' . DS . $path);
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getSaveName();
            $path = DS .$path. DS .$info->getSaveName();
            return json(['code'=>0,'msg'=>'上传成功','data'=>['src'=>$path]]);
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
        }else{
            // 上传失败获取错误信息
            return json(['code'=>ERROR,'data'=>$file->getError()]);
        }
    }
}
