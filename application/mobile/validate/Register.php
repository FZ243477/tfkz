<?php
namespace app\mobile\validate;

use think\Validate;

class Register extends Validate
{
    protected $rule = [
        'username'                      =>       'unique:users',//注册用户名验证
        'qq'                            =>      'unique:users|[1-9][0-9]{4,14}',//注册账号验证
        'mobile'                        =>      'max:11|/^1[0-9]{1}[0-9]{9}$/|unique:users',

    ];
    protected $message = [
        'username.unique'               =>      '此用户名已被注册',
        'qq.unique'                     =>      '此qq号码已被注册',
        'qq.[1-9][0-9]{4,14}'           =>      'QQ号码格式不正确',
        'mobile.max:11'                 =>      '手机号码最多不能超过11个字符',
        'mobile./^1[0-9]{1}[0-9]{9}$/'  =>      '手机号码格式不正确',
        'mobile.unique'                 =>      '此手机号码已被注册',
    ];
}