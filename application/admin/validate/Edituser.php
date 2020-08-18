<?php
namespace app\admin\validate;

use think\Validate;

class Edituser extends Validate
{
    protected $rule = [
        'mobile'                        =>      'max:11',
        'mobile'                        =>      '/^1[0-9]{1}[0-9]{9}$/',//注册账号验证
    ];
    protected $message = [
        'mobile.max:11'                 =>      '手机号码最多不能超过11个字符',
        'mobile./^1[0-9]{1}[0-9]{9}$/'  =>      '手机号码格式不正确',
    ];
}