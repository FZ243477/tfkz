<?php


namespace app\seller\validate;


use think\Validate;

class Shop extends Validate
{
    /**验证规则
     * @var array
     */
    protected $rule = [
        'shop_name'  =>  'require|unique:shop',
        'wangwang'  =>  'require|unique:shop',
        'name'=>'require',
        'mobile'  =>  'require|max:11|/^1[3-9]{1}[0-9]{9}$/',
        'province'  =>  'require',
        'city'  =>  'require',
        'area'  =>  'require',
        'address'  =>  'require',
        'link'  =>  'require',
        'type'  =>  'require',
    ];
    /**验证提示语
     * @var array
     */
    protected $message = [

        'shop_name.require'  =>  '请输入店铺名',
        'shop_name.unique'  =>  '店铺名已被使用',
        'wangwang.require'  =>  '请输入旺旺号',
        'wangwang.unique'  =>  '旺旺号已被使用',
        'name.require'  =>  '请输入发件人',
        'mobile.require'  =>  '请输入手机号码',
        'mobile.max:11'  =>  '手机号码最多不能超过11个字符',
        'mobile./^1[3-9]{1}[0-9]{9}$/'  =>  '手机号码格式不正确',
        'province.require'  =>  '请选择发件地址',
        'city.require'  =>  '请选择发件地址',
        'area.require'  =>  '请选择发件地址',
        'address.number'  =>  '详细地址不能为空',
        'bank_id.require'  =>  '请选择银行',
        'bank_id.number'  =>  '银行只能在上面选',
        'link.require'  =>  '店铺链接不能为空',
        'type.require'  =>  '请选择店铺类型',
    ];

    /**验证场景
     * @var array
     */
    protected $scene = [
        'add'  =>  ['shop_name','wangwang','name','mobile','province','city','area','address','link','type'],
        'edit'  =>  ['name','mobile','province','city','area','address','link','type'],
    ];

}