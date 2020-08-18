<?php
namespace app\admin\validate;

use think\Validate;

class Register extends Validate
{
    protected $rule = [
        'mobile'                        =>      'max:11',
        'mobile'                        =>      '/^1[0-9]{1}[0-9]{9}$/',//注册账号验证
        'mobile'                        =>      'unique:users',//注册账号验证
        'qq'                            =>      '[1-9][0-9]{4,14}'//注册账号验证
    ];
    protected $message = [
        'mobile.max:11'                 =>      '手机号码最多不能超过11个字符',
        'mobile./^1[0-9]{1}[0-9]{9}$/'  =>      '手机号码格式不正确',
        'mobile.unique'                 =>      '此手机号码已被注册',
        'qq'                            =>      'QQ号码格式不正确'
    ];
}
