<?php
namespace app\seller\controller;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Session;

class Base extends Controller
{
    /* @var array $seller 商家登录信息 */
    protected $seller;

    /* @var array $system 系统设置参数 */
    protected $system;
    /* @var string $route 当前控制器名称 */
    protected $controller = '';

    /* @var string $route 当前方法名称 */
    protected $action = '';

    /* @var string $route 当前路由uri */
    protected $routeUri = '';

    public function _initialize()
    {
        $this->getRouteinfo();
        $this->checkLogin();
        $this->assign('sellerinfo',$this->seller);
        $this->assign('menu','0');
        $this->system = Db::name('system')->find();
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
        $seller = Db::name('seller')->where(['id'=>session('seller_id')])->field('login_pwd,pay_pwd',true)->find();
        $this->seller = $seller;
        if (!$this->seller) {
            session('seller_id',null);
            session('seller_name',null);
            return $this->redirect(url('Login/index'));
        }
        /*if (!$this->seller) {
            session('seller_id',null);
            session('seller_name',null);
            return $this->redirect(url('Login/index'));
        }*/
        return true;
    }

}