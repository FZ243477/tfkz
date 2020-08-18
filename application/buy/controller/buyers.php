<?php
namespace app\buy\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends Controller
{
    public function information(){
        $information=db('users')->where('id',$id)->find();
        $this->assign('information',$information);
        return view();
    }
}