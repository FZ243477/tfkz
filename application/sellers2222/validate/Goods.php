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
        'goods_key_id'  =>  'require',
        'link'  =>  'require',
        'pc_img'  =>  'require',
        'price'  =>  'require|number',
        'show_price'  =>  'require|number',
        'num'  =>  'require|number',
    ];
    /**验证提示语
     * @var array
     */
    protected $message = [

        'name.require'  =>  '请输入商品名',
        'shop_id.require'  =>  '请选择店铺id',
        'goods_key_id.require'  =>  '请选择关键词',
        'link.require'  =>  '请填写商品链接',
        'pc_img.require'  =>  '请上传pc商品图片',
        'price.require'  =>  '价格不能为空',
        'price.number'  =>  '价格只能是数字',
        'show_price.require'  =>  '展示价格不能为空',
        'show_price.number'  =>  '展示价格只能是数字',
        'num.require'  =>  '件数不能为空',
        'num.number'  =>  '件数只能是数字',
    ];
    /**验证场景
     * @var array
     */
    protected $scene = [
        'add'  =>  ['name','shop_id','goods_key_id','link','pc_img','price','show_price','num'],
        'edit'  =>  ['name','shop_id','goods_key_id','link','price','show_price','num'],
    ];
}