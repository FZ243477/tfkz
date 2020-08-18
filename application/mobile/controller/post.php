<?
//本接口适合 优云宝 X6 使用
//www.youyunnet.com
//数据库连接信息

$dbhost = 'rm-bp1s06d12cgu1cl0wgo.mysql.rds.aliyuncs.com';    //服务器

$dbuser = 'cs';         //账号

$dbpw = 'AHyssql2020';           //密码

$dbname = 'cs';      //数据库名称

$tablepre = '';

$dbgbk='utf8';

$key2="3cb7acca77f54aa43b047957c9a9cc91";//KEY和软件配置一致

//==================================================以下代码请不用调整
//====================================================================
$conn = @mysql_connect("$dbhost","$dbuser","$dbpw") or die("数据库连接失败");
mysql_select_db("$dbname",$conn);
mysql_query("set names '$dbgbk'");
//====================================================================

$ddh=trim($_POST['ddh']);
$money=str_replace(',', '', trim($_POST['money']));
$name=trim($_POST['name']);
$key=trim($_POST['key']);
$lb=trim($_POST['lb']);
$moneyy= $money;
$date1 = date('Y-m-d H:i:s',time()); //获取日期时间
if (($ddh=="") or ($money=="") or ($key=="")){
    echo "no";
    exit();
}
if ($key !== $key2){
    echo "key no";
}else{
    //查询单号是否存在
    $sql = "SELECT * FROM bf_topup WHERE id ='$ddh'";
    $query = mysql_query($sql);
    $num = mysql_num_rows($query);
    if($num == 0){
        //查询账号是否正确
        if($lb=='1'){
            $sql2 = "SELECT * FROM bf_memberfields WHERE uid='$name'";
            $lbtext='alipay';
            $lbid='0';
        }
        if($lb=='2'){
            $sql2 = "SELECT * FROM bf_memberfields WHERE uid='$name'";
            $lbtext='tenpay';
            $lbid='1';
        }

        $query2 = mysql_query($sql2);
        $num2 = mysql_num_rows($query2);
        $times=time();
        if($num2 == 0){
            //当用户名不存在 缓存订单号
            $bsqlr = "insert into bf_topup (`id`, `type`, `uid`, `money`, `status`,`ptimestamp`) VALUES ('$ddh','$lbtext','$name', '$money', '3', '$times')";
            mysql_query($bsqlr);
            echo "no";
        }else{
            //获取原来的金额
            $query2 = mysql_query($sql2);
            $navrow = mysql_fetch_array($query2);
            $id=$navrow['uid'];//ID
            $pay_money = $navrow['money'];//金额

            //插入订单数据
            $bsqlr = "insert into bf_topup (`id`, `type`, `uid`, `money`, `status`,`ptimestamp`) VALUES ('$ddh','$lbtext','$name', '$money', '1', '$times')";
            mysql_query($bsqlr);
            //给用户充值
            $money=$pay_money+$money;
            $csql = "UPDATE bf_memberfields SET money='$money' WHERE uid = '$id'";
            mysql_query($csql);

            echo "ok";
        }


    }
    else
    {

        echo "ddh error";
    }
}
?>