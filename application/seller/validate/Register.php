<?php
namespace app\seller\validate;

use think\Validate;

class Register extends Validate
{
    protected $rule = [
        'mobile'=>'require|max:11|/^1[3-9]{1}[0-9]{9}$/|unique:seller',
        'seller_name'=>'require|unique:seller',
        'code'  =>  'require',
        'login_pwd'  =>  'require|min:6',
        'qq'  =>  'require|number',
    ];
    protected $message = [

        'mobile.require'  =>  '请输入手机号码',
        'seller_name.require'  =>  '请输入用户名',
        'mobile.max:11'  =>  '手机号码最多不能超过11个字符',
        'mobile./^1[3-9]{1}[0-9]{9}$/'  =>  '手机号码格式不正确',
        'mobile.unique'  =>  '手机号已使用，不能重复',
        'seller_name.unique'  =>  '用户名已使用，不能重复',
        'code.require'  =>  '验证码不能为空',
        'login_pwd.require'  =>  '密码不能为空',
        'login_pwd.min'  =>  '密码不能少于6位字符',
        'qq.require'  =>  'qq不能为空',
        'qq.number'  =>  'qq只能是数字',
    ];
}