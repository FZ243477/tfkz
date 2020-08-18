<?php


namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\AdminRole;
use app\common\model\AdminUser;
use app\common\model\Commission;
use app\common\model\Delivery;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Session;
use think\Exception;

class System extends Base
{
    private $tables = array();//要备份的数据表
    private $path;//文件保存路径
    private $dbname;//database name;
    private $model;//数据库模型对象

    public function _initialize()
    {
        parent::_initialize();
        $this->path = './sql/';//常量ROOT 定义的项目根目录 请自行解决
        $this->dbname = 'cs';// database 名称 请自行解决
        $this->check_path();
    }


    /**
     * 角色管理
     */
    public function basicParameter()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $count = db('admin_role')->count('id');
            $notice_list = AdminRole::limit(($page - 1) * $limit, $limit)->field('id,name,create_time')->order('id desc')->select()->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
        }
        return view();
    }


    /**
     * 角色添加
     */
    public function addbasicparameter()
    {

        if (request()->isPost()) {
            $date = input();
            // 启动事务
            Db::startTrans();
            try {
                if (!$date['name']) {
                    throw new \Exception('请输入角色名');
                }
                $role_name = db('admin_role')->where('name', $date['name'])->find();
                if ($role_name) {
                    throw new \Exception('角色名已存在');
                }
                $insert_data['name'] = $date['name'];
                $insert_data['state'] = 1;
                $insert_data['create_time'] = time();
                $id = db('admin_role')->insertGetId($insert_data);
                if (!$id) {
                    throw new \Exception('角色添加失败');
                }
                foreach ($date['data'] as $v){
                    $insert_admin_menu_role['admin_menu_id']=$v['id'];
                    $insert_admin_menu_role['admin_role']=$id;
                    $insert_admin_menu_role['create_time'] = time();
                    $res=db('admin_menu_role')->insert($insert_admin_menu_role);
                    if(!$res){
                        throw new \Exception('权限添加失败');
                    }
                    if(isset($v['children'])){
                    foreach ($v['children'] as $v){
                        $insert_admin_menu_role['admin_menu_id']=$v['id'];
                        $insert_admin_menu_role['admin_role']=$id;
                        $insert_admin_menu_role['create_time'] = time();
                        $res=db('admin_menu_role')->insert($insert_admin_menu_role);
                        if(!$res){
                            throw new Exception('权限添加失败');
                        }
                    }
                    }

                }
                // 提交事务
                Db::commit();
            }catch (Exception $e) {
                dump($e->getMessage());
                Db::rollback();
            }
            $this->success('添加成功！');
        }
        $menu=parent::menu();
        $this->assign('menu',json_encode($menu['contentManagement']));
        return view();
    }

    /**
     * 角色修改
     */
    public function editBasicParameter()
    {
        $id = input('id');
        if($id){
            $admin_role=db('admin_role')->where('id',$id)->field('id,name')->find();
            if(!$admin_role){
                $admin_role = [
                    'id' => '',
                    'name' => '',
                ];
            }

            $this->assign('admin_role',$admin_role);
           $role_ids=db('admin_menu_role')->where('admin_role',$id)->column('admin_menu_id');
        }
        if (request()->isPost()) {
            $date = input();
            // 启动事务
            Db::startTrans();
            try {
                if (!$date['name']) {
                    throw new \Exception('请输入角色名');
                }
                $role_info=db('admin_role')->where('id', $date['roleid'])->find();
                if (!$role_info) {
                    throw new Exception('参数错误！');
                }
                if($date['name'] != $role_info['name']){
                    $role_name = db('admin_role')->where('id', $date['name'])->find();
                    if ($role_name) {
                        throw new Exception('角色名已存在');
                    }
                    $insert_data['name'] = $date['name'];
                    db('admin_role')->where('id', $date['roleid'])->update($insert_data);
                }
                $role_ids=db('admin_menu_role')->where('admin_role',$date['roleid'])->column('admin_menu_id');
                foreach ($date['data'] as $v){
                    $admin_menu_ids[] = $v['id'];
                    if(isset($v['children'])&&$v['children']){
                        foreach ($v['children'] as $val){
                            $admin_menu_ids[] = $val['id'];
                        }
                    }
                }
                $cha1 = array_diff($role_ids,$admin_menu_ids);//取数据库有且未提交的（需要删除）
                $cha2 = array_diff($admin_menu_ids,$role_ids);//取数据库没有且提交了的（需要添加）
                Db::name('admin_menu_role')->where(['admin_menu_id'=>['in',$cha1],'admin_role'=>$date['roleid']])->delete();
                foreach ($cha2 as $val){
                    $insert_admin_menu_role['admin_menu_id']=$val;
                    $insert_admin_menu_role['admin_role']=$date['roleid'];
                    $insert_admin_menu_role['create_time'] = time();
                    db('admin_menu_role')->insert($insert_admin_menu_role);
                }
                // 提交事务
                Db::commit();
            }catch (Exception $e) {
                return $this->error($e->getMessage());
                Db::rollback();
            }
            return $this->success('添加成功！');
        }
        $menu=parent::menu();
        foreach ($menu['contentManagement'] as &$v){
            if($v['children']){
                foreach ($v['children'] as &$v1){
                    if(in_array($v1['id'],$role_ids)){
                        $v1['checked']=true;
                    }
                }
            }else{
                if(in_array($v['id'],$role_ids)){
                    $v['checked']=true;
                }
            }
        }
        $this->assign('menu',json_encode($menu['contentManagement']));
        return view();
    }


    /**
     * 删除角色
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete_admin_role()
    {
        $data = input();
        if (!$data['id']) {
            $this->error('参数错误！');
        }
        $res = db('admin_role')->where('id', $data['id'])->delete();
        if ($res) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 系统配置
     */
    public function system()
    {
        $data = db('system')->where('id', 1)->find();
        $this->assign('data', $data);
        return view();
    }

    /**
     * 系统配置
     */
    public function setting_system()
    {

        $data = input();
        if(!isset($data['data']['switch'])){
            $data['data']['switch']='off';
        }
        if($data['data']['switch']=='on'){
            $data['data']['switch']=1;
        }else{
            $data['data']['switch']=0;
        }
        $res = db('system')->where('id', 1)->update($data['data']);
        if ($res) {
            $res1=admin_log("系统参数修改", "管理员{$this->admin_info['user_name']}操作:系统参数修改");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            $this->success('修改成功!');
        } else {
            $this->error('修改失败！');
        }
    }

    /**
     * 管理用户
     */
    public function adUser()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $count = db('admin_user')->count('id');
            $notice_list = AdminUser::limit(($page - 1) * $limit, $limit)->field('id,user_name,logins_num,logins_time,role_id,logins_ip,state,name,create_time')->order('id desc')->select()->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
        }
        return view();
    }

    /**
     * 添加用户
     */
    public function addUser()
    {
        $data = input();
        $user_data = [
            'id' => '',
            'user_name' => '',
            'name' => '',
            'role_id' => '',
            'state' => '',
        ];
        if ($data['id'] && $data['id'] != 0) {
            $user_data = db('admin_user')->where('id', $data['id'])->find();

        }
        $this->assign('user_data', $user_data);
        $role_data = db('admin_role')->where('state', 1)->select();
        $this->assign('role_data', $role_data);
        return view();
    }


    /**
     * 修改添加用户
     */
    public function set_user()
    {
        $data = input();
        $data=$data['field'];
        if (!$data['name']) {
            $this->error('请填写用户名！');
        }

        if (!$data['name2']) {
            $this->error('请填写姓名！');
        }
        if (!$data['id']) {
            if (!$data['state']) {
                $this->error('请选择状态！');
            }

            if (!$data['vip']) {
                $this->error('请选择角色！');
            }

            if (!$data['pwd']) {
                $this->error('请选填写密码！');
            }

        }
        $admin_data['user_name'] = $data['name'];
        $admin_data['name'] = $data['name2'];
        if ($data['state']) {
            $admin_data['state'] = $data['state'];
        }
        if ($data['vip']) {
            $admin_data['role_id'] = $data['vip'];
        }
        if ($data['pwd']) {
            $admin_data['password'] = md5($data['pwd']);
        }

        if ($data['id']) {
            $res = db('admin_user')->where('id', $data['id'])->update($admin_data);
            $msg = "修改";
        } else {
            $res = db('admin_user')->insert($admin_data);
            $msg = "添加";
        }
        if ($res) {
            $this->success("{$msg}成功！");
        } else {
            $this->success("{$msg}失败！");
        }
    }


    /**
     * 删除用户
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete_user()
    {
        $data = input();
        if (!$data['id']) {
            $this->error('参数错误！');
        }
        $res = db('admin_user')->where('id', $data['id'])->delete();
        if ($res) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 用户记录
     */
    public function userRecord()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $where = [];
            if (isset($date['modular']) && $date['modular']) {
                $where['model'] =['like','%'.trim($date['modular']).'%'];
            }

            if (isset($date['time']) && $date['time']) {
                $time = explode(" - ", $date['time']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_time'] = ['between', [$time1, $time2]];
            }
            if (isset($date['username']) && $date['username']) {
                $where_name['user_name'] = ['like', '%' . trim($date['username']) . '%'];
                $uid = db('admin_user')->where($where_name)->column('id');
                $where['admin_id'] = ['in',$uid];
            }
            $count = db('admin_log')->where($where)->limit(($page - 1) * $limit, $limit)->count('id');
            $notice_list = AdminLog::where($where)->limit(($page - 1) * $limit, $limit)->field('id,content,admin_id,model,create_time')->order('id desc')->select()->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
        }
        return view();
    }

    /**
     * 佣金比例
     */
    public function commissionRate()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $count = db('commission')->limit(($page - 1) * $limit, $limit)->count('id');
            $notice_list = Commission::limit(($page - 1) * $limit, $limit)->field('id,max_goods_price,user_reward,seller_reward,create_time,update_time')->select()->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
        }
        return view();
    }


    /**
     * 修改添加佣金
     */
    public function addCommissionRate()
    {
        $data = input();
        $commission_data = [
            'id' => '',
            'max_goods_price' => '',
            'user_reward' => '',
            'seller_reward' => '',
        ];
        if ($data['id'] && $data['id'] != 0) {
            $commission_data = db('commission')->where('id', $data['id'])->find();

        }
        $this->assign('commission_data',$commission_data);
        return view();
    }


    /**
     * 修改添加佣金
     */
    public function set_commission_rate()
    {
        $data = input();
        $data=$data['field'];
        if (!$data['principal1']) {
            $this->error('请填写商品限额！');
        }

        if (!$data['principal3']) {
            $this->error('请填写收取商家银锭！');
        }
        if (!$data['principal4']) {
            $this->error('请填写发放给刷手银锭！');
        }
        $commission_rate_data['max_goods_price'] = $data['principal1'];
        $commission_rate_data['seller_reward'] = $data['principal3'];
        $commission_rate_data['user_reward'] = $data['principal4'];
        if ($data['id']) {
            $res = db('commission')->where('id', $data['id'])->update($commission_rate_data);
            $msg = "修改";
        } else {
            $res = db('commission')->insert($commission_rate_data);
            $msg = "添加";
        }
        if ($res) {
            $res1=admin_log("{$msg}佣金比例", "管理员{$this->admin_info['user_name']}操作:{$msg}佣金比例");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            $this->success("{$msg}成功！");
        } else {
            $this->success("{$msg}失败！");
        }
    }

    /**
     * 删除佣金
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete_commission_rate()
    {
        $data = input();
        if (!$data['id']) {
            $this->error('参数错误！');
        }
        $res = db('commission')->where('id', $data['id'])->delete();
        if ($res) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 菜单管理
     */
    public function menu()
    {
        return view();
    }

    /**
     * @notes  页面提示设置页面
     * @date 2019/9/28
     * @time 11:25
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settips()
    {
        $sellerList = Db::name('set_tips')->where(['type'=>1])->select(); //商家
        $sellerList = $sellerList ? $sellerList->toArray() : [];
        $userList = Db::name('set_tips')->where(['type'=>2])->select(); //买手
        $userList = $userList ? $userList->toArray() : [];
        $this->assign('sellerList',$sellerList);
        $this->assign('userList',$userList);
        return view();
    }

    /**
     * @notes 修改页面提示
     * @date 2019/9/28
     * @time 11:38
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function editTips(Request $request){
        $data = $request->param();
        if(!isset($data['id']) || !$data['id'])return $this->error('未找到数据');
        if(!isset($data['content']) || !$data['content'])return $this->error('内容不能为空');
        $list = Db::name('set_tips')->where(['id'=>$data['id']])->find();
        if(!$list)return $this->error('未找到数据');
        if(!Db::name('set_tips')->where(['id'=>$data['id']])->update(['content'=>$data['content'],'update_time'=>time()]))return $this->error('修改失败！');
        return $this->success('修改成功！');
    }


    /**
     * @notes 数据备份页面
     * @date 2019/10/9
     * @time 17:00
     * @return \think\response\View
     */
    public function backups()
    {
        $list = $this->getFileInfo();
        $this->assign('list',$list);
        return view();
    }

    /**
     * @notes 获取所有表名
     * @date 2019/10/9
     * @time 14:56
     */
    private function get_tables ()
    {
        $sql = 'show tables';
        if( $data = Db::query($sql) ) {
            foreach ( $data as $val ) {
                $this->tables[] = $val['Tables_in_'.$this->dbname];
            }
        }
    }

    /**
     * @notes 数据库备份
     * @date 2019/10/9
     * @time 17:11
     */
    public function backupAll(){
        set_time_limit(0);
        $this->get_tables();
        $data = $this->genTitle();
        foreach ( $this ->tables as $table )
        {
            //拿相关 create table 数据
            $ctable = $this->get_create_table($table);
            //生成表结构
            $data .= $this->get_table_structure($ctable);
            //表记录
            $data .= $this->get_table_records($table);
        }
        $filename = $this->path . date("Y-m-d-H-i-s") .'.sql';
        $res = file_put_contents($filename, $data);
        if(!$res)return $this->error('备份失败');
        return $this->success('备份成功！');
    }

    //sql 文件开头部分  可以省略 但 SET FOREIGN_KEY_CHECKS=0 最好有
    private function genTitle()
    {
        $time = date("Y-m-d H:i:s" ,time());
        $str = "/*************************\r\n";
        $str.= " * {$time} \r\n";
        $str.= " ************************/\r\n";
        $str.= "SET FOREIGN_KEY_CHECKS=0;\r\n";
        return $str;
    }



    //返回一个数组, 0=>表名称 ,1=>表结构(Create Table)
    private function get_create_table ($table)
    {
        $sql = "show create table $table";
        $arr = Db::query($sql);
        return array_values($arr);
    }

    //生成表结构
    private function get_table_structure ($ctable)
    {
        $str  = "-- ----------------------------\r\n";
        $str .= "-- Table structure for `{$ctable[0]['Table']}`\r\n";
        $str .= "-- ----------------------------\r\n";
        $str .= "DROP TABLE IF EXISTS `{$ctable[0]['Table']}`;\r\n".$ctable[0]['Create Table'].";\r\n\r\n";
        return $str;
    }

    //表记录的sql语句拼接  当还原的时候  就是逐条插入记录 到对应的表
    private function get_table_records ($table)
    {
        $sql = "select * from {$table}";
        if( $data = Db::query($sql) ) {
            $str = "-- ----------------------------\r\n";
            $str.= "-- Records of $table \r\n";
            $str.= "-- ----------------------------\r\n";

            foreach ( $data as $val ) {
                if( $val ) {
                    //$keyArr = array();
                    $valArr = array();
                    //这里看情况了,
                    foreach ( $val as $k => $v ) {
                        //$keyArr[] = "`".$k."`";
                        //对单引号和换行符进行一下转义
                        $valArr[] = "'".str_replace( array("'","\r\n"), array("\'","\\r\\n"), $v )."'";
                    }
                    //$keys = implode(', ', $keyArr);
                    $values = implode(', ', $valArr);
                    $str .= "INSERT INTO `{$table}` VALUES ($values);\r\n";//省略了字段名称
                }
            }
            $str .= "\r\n";
            return $str;
        }
        return '';
    }

    /**
     * @notes 判断文件是否存在
     * @date 2019/10/9
     * @time 15:24
     */
    private function check_path ()
    {
        if( !is_dir($this->path) ) {
            mkdir($this->path ,0777 ,true);
        }
    }

    /**
     * @notes 删除文件
     * @date 2019/10/9
     * @time 15:28
     * @param $file
     * @return bool
     */
    public function dbDel ($file)
    {
        $file = '.'.$file;
        if( file_exists($file) )
        {
            if(!unlink($file))return $this->error('删除失败！');
            return $this->success('删除成功！');
        }
        return $this->error('没有该文件，请刷新！');
    }

    /**
     * @notes 数据库文件列表
     * @date 2019/10/9
     * @time 15:39
     */
    public function file(){
        //扫描文件夹
        $files = scandir($this->path);
        $file_arr = [];
        foreach ($files as $file){
            if($file != '.' && $file != '..'){
                $file_arr[] = $file;
            }
        }
        //显示
        dump($file_arr);
    }

    /**
     * @notes 数据库文件列表
     * @date 2019/10/9
     * @time 15:39
     */
    public function getFileInfo()
    {
        $temp = array();
        if( is_dir($this->path) )
        {
            $handler = opendir($this->path);
            $num = 0;
            while ( $file = readdir($handler) ){
                if( $file !== '.' && $file !== '..' )
                {
                    $filename = $this->path.$file;
                    $temp[$num]['name'] = $file;
                    $temp[$num]['size'] = ceil(filesize($filename)/1024);
                    $temp[$num]['time'] = date("Y-m-d H:i:s" ,filemtime($filename));
                    $temp[$num]['path'] = str_replace("./","/",$filename);
                    $num ++;
                }
            }
        }
        return $temp;
    }

    /**
     * @notes 还原方法  拆分sql语句,  因为之前保存到文件中的语句都是以 ;\r\n 结尾的, 所以...
     * @date 2019/10/9
     * @time 15:47
     * @param $file
     * @return bool
     */
    public function restore ($file)
    {
        $filename = $file;
        if( !file_exists($filename) )
        {
            return false;
        }
        $str = fread( $hd = fopen($filename, "rb") , filesize($filename) );
        $sqls = explode(";\r\n", $str);//所以... 这里拆分sql
        if($sqls)
        {
            foreach($sqls as $sql)
            {
                Db::query($sql);//逐条执行
            }
        }
        fclose($hd);
        return true;
    }
    /**
     * 发货仓列表
     */
    public function delivery()
    {
        if (request()->isPost()) {
            $date = input();
            $page = $date['page'];//页数
            $limit = $date['limit'];//每页条数
            $count = db('delivery')->limit(($page - 1) * $limit, $limit)->count('id');
            $notice_list = Delivery::limit(($page - 1) * $limit, $limit)->field('id,name,remarks,create_time')->select()->toArray();
            return json(['code' => 0, 'count' => $count, 'msg' => '获取数据成功', 'data' => $notice_list]);
        }
        return view();
    }


    /**
     * 修改添加佣金
     */
    public function addDelivery()
    {
        $data = input();
        $commission_data = [
            'id' => '',
            'name' => '',
            'remarks' => '',
        ];
        if ($data['id'] && $data['id'] != 0) {
            $commission_data = db('delivery')->where('id', $data['id'])->find();

        }
        $this->assign('commission_data',$commission_data);
        return view();
    }


    /**
     * 修改添加佣金
     */
    public function set_delivery()
    {
        $data = input();
        $data=$data['field'];
        if (!$data['name']) {
            $this->error('请填写发货仓名称！');
        }

//        if (!$data['remarks']) {
//            $this->error('请填写说明！');
//        }
        $commission_rate_data['name'] = $data['name'];
        $commission_rate_data['remarks'] = $data['remarks'];
        $commission_rate_data['create_time'] = time();
        if ($data['id']) {
            $res = db('delivery')->where('id', $data['id'])->update($commission_rate_data);
            $msg = "修改";
        } else {
            $res = db('delivery')->insert($commission_rate_data);
            $msg = "添加";
        }
        if ($res) {
            $res1=admin_log("{$msg}发货仓", "管理员{$this->admin_info['user_name']}操作:{$msg}佣金比例");
            if(!$res1){
                return $this->error('操作日志写入失败！');
            }
            $this->success("{$msg}成功！");
        } else {
            $this->success("{$msg}失败！");
        }
    }

    /**
     * 删除发货仓
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete_delivery()
    {
        $data = input();
        if (!$data['id']) {
            $this->error('参数错误！');
        }
        $res = db('delivery')->where('id', $data['id'])->delete();
        if ($res) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败！');
        }
    }

}
