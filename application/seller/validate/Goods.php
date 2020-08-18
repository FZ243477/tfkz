<?php


namespace app\seller\validate;


use think\Validate;

class Goods extends Validate
{
    /**验证规则
     * @var array
     */
    protected $rule = [
        'name'  =>  'require',
        'shop_id'=>'require',
        'link'  =>  'require',
        'pc_img'  =>  'require',
        'show_price'  =>  'require|number',
    ];
    /**验证提示语
     * @var array
     */
    protected $message = [

        'name.require'  =>  '请输入商品名',
        'shop_id.require'  =>  '请选择店铺id',
        'link.require'  =>  '请填写商品链接',
        'pc_img.require'  =>  '请上传pc商品图片',
        'show_price.require'  =>  '展示价格不能为空',
        'show_price.number'  =>  '展示价格只能是数字',
    ];
    /**验证场景
     * @var array
     */
    protected $scene = [
        'add'  =>  ['name','shop_id','link','pc_img','show_price'],
        'edit'  =>  ['name','shop_id','link','show_price'],
    ];
}