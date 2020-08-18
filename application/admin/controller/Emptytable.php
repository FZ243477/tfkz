<?php


namespace app\admin\controller;

use think\Controller;
use think\Db;

class Emptytable extends Controller
{

    /**
     * @notes 获取所有表名
     * @date 2019/10/9
     * @time 14:56
     */
    public function truncate ()
    {
        exit;
        $tables = [
            "tfkz_admin_log",
            "tfkz_goods",
            "tfkz_goods_key",
            "tfkz_goods_key_world",
            "tfkz_message",
            "tfkz_notice",
            "tfkz_recharge",
            "tfkz_seller",
            "tfkz_seller_bank",
            "tfkz_seller_cash",
            "tfkz_seller_deposit_recharge",
            "tfkz_seller_limit",
            "tfkz_seller_reward_recharge",
            "tfkz_seller_task",
            "tfkz_seller_task_praise",
            "tfkz_shop",
            "tfkz_user_address",
            "tfkz_user_bank",
            "tfkz_user_buyno",
            "tfkz_user_cash",
            "tfkz_user_deposit_recharge",
            "tfkz_user_reward_recharge",
            "tfkz_user_task",
            "tfkz_users",
            "tfkz_vip_record"
        ];
//        foreach ($tables as $table){
//            $sql = "truncate table ".$table;
//            Db::query($sql);
//        }
        dump('系统清空成功！');
    }


}
