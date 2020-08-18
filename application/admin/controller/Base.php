<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Session;

class Base extends Controller
{
    /* @var array $admin 管理员登录信息 */
    protected $admin;
    /* @var string $route 当前控制器名称 */
    protected $controller = '';

    /* @var string $route 当前方法名称 */
    protected $action = '';

    /* @var string $route 当前路由uri */
    protected $routeUri = '';

    /* @var array $allowAllAction 登录验证白名单 */
    protected $allowAllAction = [
        // 登录页面
        'login/login',
        'login/logout',
    ];
    /**@var string $role_id 当前角色id*/
    protected $role_id;

    protected $admin_info;

    public function _initialize()
    {
        $this->admin_info=db('admin_user')->where('id',session('admin_id'))->field('id,user_name,name')->find();
        $this->checkLogin();
        //session('admin_id',1);
        //session('admin_name',1);
        $this->getRouteinfo();
        $this->checkPower();
    }

    /**
     * 解析当前路由参数 （分组名称、控制器名称、方法名）
     */
    protected function getRouteinfo()
    {
        // 控制器名称
        $this->controller = toUnderScore($this->request->controller());
        // 方法名称
        $this->action = $this->request->action();
        // 当前uri
        $this->routeUri = $this->controller . '/' . $this->action;
    }

    /**
     * function 获取没有访问权限的菜单
     * @return mixed
     */
    protected function notRoleMenu(){
        if(!Cache::get('notRole_'.$this->role_id)){
            $admin_role = Db::name('admin_menu_role')->where(['admin_role'=>$this->role_id])->column('admin_menu_id');
            if($admin_role){
                $arr = Db::name('admin_menu')->where(['id'=>['not in',$admin_role],'fid'=>['gt',0]])->column('href');
            }else{
                $arr = [];
            }
            Cache::set('notRole_'.$this->role_id,$arr,3600);//缓存1小时
        }
        return Cache::get('notRole_'.$this->role_id);
    }

    /**
     * function 获取菜单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menu(){
        if(!Cache::get('menu'.$this->role_id)){
            if(Db::name('admin_role')->where(['id'=>$this->role_id])->value('is_susper')==1){
                $res = Db::name('admin_menu')->where(['state'=>1,'fid'=>0])->order('sort desc')->field('id,title,href,fid,spread,icon')->select();
                if($res){
                    $res=$res->toArray();
                }
                foreach ($res as &$row){
                    $row['href'] = url($row['href']);
                    if($row['spread']=="false"){
                        $row['spread']=false;
                    }else{
                        $row['spread']=true;
                    }
                    $row['children'] = Db::name('admin_menu')->where(['state'=>1,'fid'=>$row['id']])->order('sort desc')->field('id,title,href,fid,spread,icon')->select();
                    if($row['children']){
                        $row['children']=$row['children']->toArray();
                    }
                    foreach ($row['children'] as &$child){
                        $child['href'] = url($child['href']);
                        if($child['spread']=="false"){
                            $child['spread']=false;
                        }else{
                            $child['spread']=true;
                        }
                    }
                }
            }else{
                $admin_role = Db::name('admin_menu_role')->where(['admin_role'=>$this->role_id])->column('admin_menu_id');
                if($admin_role){
                    $res = Db::name('admin_menu')->where(['id'=>['in',$admin_role],'state'=>1,'fid'=>0])->order('sort desc')->field('id,title,href,fid,spread,icon')->select();
                    if($res){
                        $res=$res->toArray();
                    }
                    foreach ($res as &$row){
                    if($row['spread']=="false"){
                     $row['spread']=false;
                      }else{
                        $row['spread']=true;
                    }
                        $row['href'] = url($row['href']);
                        $row['children'] = Db::name('admin_menu')->where(['id'=>['in',$admin_role],'state'=>1,'fid'=>$row['id']])->order('sort desc')->field('id,title,href,fid,spread,icon')->select();
                       if($row['children']){
                           $row['children']=$row['children']->toArray();
                       }
                        foreach ($row['children'] as &$child){
                            $child['href'] = url($child['href']);
                            if($child['spread']=="false"){
                                $child['spread']=false;
                            }else{
                                $child['spread']=true;
                            }
                        }
                    }
                }else{
                    $res = [];
                }
            }
            $res=['contentManagement'=>$res];
            $menu = $res;

            Cache::set('menu'.$this->role_id,$menu,3600);//缓存1小时
        }
        return Cache::get('menu'.$this->role_id);
    }

    protected function checkPower(){
        $notMenu = $this->notRoleMenu();
        $root=db('admin_role')->where('id',$this->admin['role_id'])->value('is_susper');
        if($root==0){
            if(in_array($this->routeUri,$notMenu)){
                echo "<script>alert('访问权限受限')</script>";
                exit;
            }
        }

    }

    /**
     * @notes 验证登录
     * @date 2019/7/22
     * @time 16:44
     * @return bool|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkLogin(){
        // 验证登录状态
        $ip = $this->request->ip();
        $admin = Db::name('admin_user')->where(['id'=>session::get('admin_id')])->find();
        $this->role_id = $admin['role_id'];
        $this->admin = $admin;
        if (!$this->admin ||$this->admin['logins_ip'] != $ip) {
            session('admin_id',null);
            session('admin_name',null);
            return $this->redirect('Login/index');
        }
        return true;
    }

}
