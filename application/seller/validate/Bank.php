<?php


namespace app\seller\validate;


use think\Validate;

class Bank extends Validate
{
    /**验证规则
     * @var array
     */
    protected $rule = [
        'bank_seller'  =>  'require',
        'branch_name'=>'require',
        'mobile'  =>  'require|max:11|/^1[3-9]{1}[0-9]{9}$/|unique:seller_bank',
        'idcard'  =>  'require|max:18',
        'bank_number'  =>  'require|number|max:21|min:13',
        'bank_id'  =>  'require|number',
        'province'  =>  'require',
        'city'  =>  'require',
    ];
    /**验证提示语
     * @var array
     */
    protected $message = [

        'bank_seller.require'  =>  '请输入开户名',
        'branch_name.require'  =>  '请输入开户支行',
        'mobile.require'  =>  '请输入手机号码',
        'mobile.max:11'  =>  '手机号码最多不能超过11个字符',
        'mobile./^1[3-9]{1}[0-9]{9}$/'  =>  '手机号码格式不正确',
        'mobile.unique'  =>  '手机号已被使用',
        'idcard.require'  =>  '身份证不能为空',
        'idcard.max'  =>  '身份证最多18位字符',
        'bank_number.require'  =>  '卡号不能为空',
        'bank_number.number'  =>  '卡号只能是数字',
        'bank_number.max'  =>  '卡号最多21位数字',
        'bank_number.min'  =>  '卡号最少13位数字',
        'bank_id.require'  =>  '请选择银行',
        'bank_id.number'  =>  '银行只能在上面选',
        'province.require'  =>  '省份不能为空',
        'city.require'  =>  '城市不能为空',
    ];
    /**验证场景
     * @var array
     */
    protected $scene = [
        'add'  =>  ['bank_seller','branch_name','mobile','idcard','bank_number','bank_id','province','city'],
        'edit'  =>  ['branch_name','idcard','bank_number','bank_id','province','city'],
    ];
}