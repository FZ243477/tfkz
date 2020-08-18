<?php


namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;

class Sql extends Base
{
    private $tables = array();//要备份的数据表
    private $path;//文件保存路径
    private $dbname;//database name;

    public function _initialize()
    {
        parent::_initialize();
        $this->path = './sql/';//常量ROOT 定义的项目根目录 请自行解决
        $this->dbname = 'cs';// database 名称 请自行解决
        $this->check_path();
    }

    /**
     * @notes 数据备份页面
     * @date 2019/10/9
     * @time 17:00
     * @return \think\response\View
     */
    public function index()
    {
        if(request()->isAjax()){
            $list = $this->getFileInfo();
            return $this->success('success','',$list);
        }
        return view();
    }

    /**
     * @notes 获取所有表名
     * @date 2019/10/9
     * @time 14:56
     */
    private function get_tables ()
    {
        $sql = 'show tables';
        if( $data = Db::query($sql) ) {
            foreach ( $data as $val ) {
                $this->tables[] = $val['Tables_in_'.$this->dbname];
            }
        }
    }

    /**
     * @Function 备份数据
     * @User 扬风
     * @dateTime 2020/6/11 11:22
     */
    public function backTable(){
        $sql = "truncate table tfkz_user_back";
        Db::query($sql);
        $sql = "truncate table tfkz_seller_back";
        Db::query($sql);
        $userList = Db::name('users')->field('username,name,login_pwd,pay_pwd,mobile,qq,balance,reward')->select()->toArray();
        Db::name('user_back')->insertAll($userList);
        $sellerList = Db::name('seller')->field('seller_name,login_pwd,pay_pwd,mobile,qq,balance,reward,arrears')->select()->toArray();;
        Db::name('seller_back')->insertAll($sellerList);
    }

    /**
     * @notes 数据库备份
     * @date 2019/10/9
     * @time 17:11
     */
    public function backupAll(){
        set_time_limit(0);
        $data = $this->genTitle();
        $arr = [
            'tfkz_users',
            'tfkz_user_back',
            'tfkz_seller',
            'tfkz_seller_back',
        ];
        foreach ( $arr as $table )
        {
            //拿相关 create table 数据
            $ctable = $this->get_create_table($table);
            //生成表结构
            $data .= $this->get_table_structure($ctable);
            //表记录
            $data .= $this->get_table_records($table);
        }
        $name = 'bf'.date("Y-m-d-H-i-s") .'.sql';
        $filename = $this->path . $name;
        $res = file_put_contents($filename, $data);
        if(!$res)return $this->error('备份失败');
        $puth = '/sql/'.$name;
        return $this->success('备份成功！');
    }

    //sql 文件开头部分  可以省略 但 SET FOREIGN_KEY_CHECKS=0 最好有
    private function genTitle()
    {
        $time = date("Y-m-d H:i:s" ,time());
        $str = "/*************************\r\n";
        $str.= " * {$time} \r\n";
        $str.= " ************************/\r\n";
        $str.= "SET FOREIGN_KEY_CHECKS=0;\r\n";
        return $str;
    }



    //返回一个数组, 0=>表名称 ,1=>表结构(Create Table)
    private function get_create_table ($table)
    {
        $sql = "show create table $table";
        $arr = Db::query($sql);
        return array_values($arr);
    }

    //生成表结构
    private function get_table_structure ($ctable)
    {
        $str  = "-- ----------------------------\r\n";
        $str .= "-- Table structure for `{$ctable[0]['Table']}`\r\n";
        $str .= "-- ----------------------------\r\n";
        $str .= "DROP TABLE IF EXISTS `{$ctable[0]['Table']}`;\r\n".$ctable[0]['Create Table'].";\r\n\r\n";
        return $str;
    }

    //表记录的sql语句拼接  当还原的时候  就是逐条插入记录 到对应的表
    private function get_table_records ($table)
    {
        $sql = "select * from {$table}";
        if( $data = Db::query($sql) ) {
            $str = "-- ----------------------------\r\n";
            $str.= "-- Records of $table \r\n";
            $str.= "-- ----------------------------\r\n";

            foreach ( $data as $val ) {
                if( $val ) {
                    //$keyArr = array();
                    $valArr = array();
                    //这里看情况了,
                    foreach ( $val as $k => $v ) {
                        //$keyArr[] = "`".$k."`";
                        //对单引号和换行符进行一下转义
                        $valArr[] = "'".str_replace( array("'","\r\n"), array("\'","\\r\\n"), $v )."'";
                    }
                    //$keys = implode(', ', $keyArr);
                    $values = implode(', ', $valArr);
                    $str .= "INSERT INTO `{$table}` VALUES ($values);\r\n";//省略了字段名称
                }
            }
            $str .= "\r\n";
            return $str;
        }
        return '';
    }

    /**
     * @notes 判断文件是否存在
     * @date 2019/10/9
     * @time 15:24
     */
    private function check_path ()
    {
        if( !is_dir($this->path) ) {
            mkdir($this->path ,0777 ,true);
        }
    }

    /**
     * @notes 删除文件
     * @date 2019/10/9
     * @time 15:28
     * @param $file
     * @return bool
     */
    public function dbDel ($file)
    {
        $file = '.'.$file;
        if( file_exists($file) )
        {
            if(!unlink($file))return $this->error('删除失败！');
            return $this->success('删除成功！');
        }
        return $this->error('没有该文件，请刷新！');
    }

    /**
     * @notes 数据库文件列表
     * @date 2019/10/9
     * @time 15:39
     */
    public function file(){
        //扫描文件夹
        $files = scandir($this->path);
        $file_arr = [];
        foreach ($files as $file){
            if($file != '.' && $file != '..'){
                $file_arr[] = $file;
            }
        }
        //显示
    }

    /**
     * @notes 数据库文件列表
     * @date 2019/10/9
     * @time 15:39
     */
    public function getFileInfo()
    {
        $temp = array();
        if( is_dir($this->path) )
        {
            $handler = opendir($this->path);
            $num = 0;
            while ( $file = readdir($handler) ){
                if( $file !== '.' && $file !== '..' )
                {
                    $filename = $this->path.$file;
                    $temp[$num]['name'] = $file;
                    $temp[$num]['size'] = ceil(filesize($filename)/1024);
                    $temp[$num]['time'] = date("Y-m-d H:i:s" ,filemtime($filename));
                    $temp[$num]['path'] = str_replace("./","/",$filename);
                    $num ++;
                }
            }
        }
        return $temp;
    }

    /**
     * @notes 还原方法  拆分sql语句,  因为之前保存到文件中的语句都是以 ;\r\n 结尾的, 所以...
     * @date 2019/10/9
     * @time 15:47
     * @param $file
     * @return bool
     */
    public function restore ($file)
    {
        $filename = $file;
        if( !file_exists($filename) )
        {
            return false;
        }
        $str = fread( $hd = fopen($filename, "rb") , filesize($filename) );
        $sqls = explode(";\r\n", $str);//所以... 这里拆分sql
        if($sqls)
        {
            foreach($sqls as $sql)
            {
                Db::query($sql);//逐条执行
            }
        }
        fclose($hd);
        return true;
    }


    /**
     * @notes 清空表数据
     * @date 2019/10/9
     * @time 14:56
     */
    public function truncate ()
    {
        if(!request()->isAjax()){
            $list = [];
            $this->get_tables();
            foreach ($this->tables as $item){
                $res['name'] = $item;
                $sql="show create table $item";
                $query=Db::query($sql);
                $str = strrchr($query[0]['Create Table'],'COMMENT=\'');
                $str = substr($str,9);
                $str = str_replace('\'','',$str);
                $res['memo'] = $str;
                $res['num'] = Db::table($item)->count('id');
                $list[] = $res;
            }
            dump($list);exit;
            return $this->success('success','',$list);
        }
        return view();
    }


    /**
     * @notes 清空单个表
     * @date 2019/11/22
     * @time 11:44
     * @param Request $request
     */
    public function truncateDo (Request $request)
    {
        $data = $request->param();
        if(!$data['name'])return $this->error('表名不存在！');
        $table = $data['name'];
        $arr = config('sql.notTruncate');
        if(in_array($table,$arr))return $this->error('该表不能清空！');
        $sql = "truncate table ".$table;
        Db::query($sql);
        return $this->success("清空表{$table}成功！");
    }

    /**
     * @notes 清空多个表
     * @date 2019/11/22
     * @time 11:44
     * @param Request $request
     */
    public function truncateAllDo (Request $request)
    {
        $data = $request->param();
        if(!$data['data'])return $this->error('请选择要清空的表！');
        $num = 0;
        $nums = 0;
        foreach ($data['data'] as $table){
            $arr = config('sql.notTruncate');
            if(in_array($table['name'],$arr)){
                $num++;
                continue;
            }else{
                $nums++;
            }
            $sql = "truncate table ".$table['name'];
            Db::query($sql);
        }
        return $this->success("成功清空{$nums}张表，{$num}张表不能清空！");
    }
}