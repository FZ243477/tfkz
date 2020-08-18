<?php
namespace app\mobile\model;

use think\Db;
use think\Model;

class Login extends Model
{
    public function login($name,$password){
        $users= \think\Db::name('users')->where('username','=',$name)->find();
        $password= md5($password);
        if($users){
            if($users['login_pwd']== $password){
                \think\Session::set('id',$users['id']);
                \think\Session::set('name',$users['username']);
                return 1;
            }else{
                \think\Session::set('id',$users['id']);
                \think\Session::set('name',$users['username']);
                return 2;
            }
        }else{
            return 3;
        }
    }
}

