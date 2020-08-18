<?php


namespace app\seller\validate;


use think\Validate;

class Task extends Validate
{
    /**验证规则
     * @var array
     */
    protected $rule = [
        'shop_id'  =>  'require',
        'task_type'=>'require',
        'goods_id'  =>  'require',
        'terminal'  =>  'require',
        'is_free_shiping'  =>  'require',
        'weight'  =>  'require|number',
        'add_reward'  =>  'require|number',
        'num'  =>  'require|number',
    ];
    /**验证提示语
     * @var array
     */
    protected $message = [

        'task_type.require'  =>  '请选择任务类型',
        'shop_id.require'  =>  '请选择店铺id',
        'goods_id.require'  =>  '商品不能为空',
        'terminal.require'  =>  '请选择任务终端',
        'is_free_shiping.require'  =>  '请选择是否包邮',
        'weight.require'  =>  '重量不能为空',
        'weight.number'  =>  '重量只能是数字',
        'add_reward.require'  =>  '加赏佣金不能为空',
        'add_reward.number'  =>  '加赏佣金只能是数字',
        'num.require'  =>  '单数不能为空',
        'num.number'  =>  '单数只能是数字',
    ];
    /**验证场景
     * @var array
     */
    protected $scene = [
        'add'  =>  ['shop_id','task_type','goods_id','terminal','is_free_shiping','weight','add_reward','num'],
        'edit'  =>  ['shop_id','task_type','goods_id','terminal','is_free_shiping','weight','add_reward','num'],
    ];
}