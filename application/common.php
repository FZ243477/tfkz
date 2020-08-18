<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 驼峰命名转下划线命名
 * @param $str
 * @return string
 */
function toUnderScore($str)
{
    $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
        return '_' . strtolower($matchs[0]);
    }, $str);
    return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
}

/**
 * 写财务
 * @param $uid 会员id
 * @param $user_type 1商家 2买手
 * @param $price 金额
 * @param $price_type 1本金  2银锭
 * @param $type 财务类型详情看配置文件
 * @param $memo  财务描述
 * @param $from_type  来源类型 1买手2商家
 * @param $fromuser  财务来源
 */
function finance($uid, $user_type, $price, $price_type, $type, $memo, $from_type = 0, $fromuser = '无')
{
    $insert_data['uid'] = $uid;
    $insert_data['price'] = $price;
    $insert_data['type'] = $type;
    $insert_data['memo'] = $memo;
    $insert_data['from_type'] = $from_type;
    $insert_data['fromuser'] = $fromuser;
    $insert_data['create_time'] = time();
    if ($user_type == 1) {
        $y_table = "seller";
    } elseif ($user_type == 2) {
        $y_table = "users";
    }
    if ($price_type == 1) {
        $p_type = "balance";
    } elseif ($price_type == 2) {
        $p_type = "reward";
    }
    $yprice = db($y_table)->where('id', $uid)->value($p_type);//查询余额
    $insert_data['yprice'] = $yprice;
    if ($user_type == 1 && $price_type == 1) {
        $table = "seller_deposit_recharge";
    } elseif ($user_type == 1 && $price_type == 2) {
        $table = "seller_reward_recharge";
    } elseif ($user_type == 2 && $price_type == 1) {
        $table = "user_deposit_recharge";
    } elseif ($user_type == 2 && $price_type == 2) {
        $table = "user_reward_recharge";
    }
    // var_dump($table,$insert_data);exit;
    $res = db($table)->insert($insert_data);
    return $res;
}

/**
 * 数据导出
 * @param array $title 标题行名称
 * @param array $data 导出数据
 * @param string $fileName 文件名
 * @param string $savePath 保存路径
 * @param $type   是否下载  false--保存   true--下载
 * @return string   返回文件全路径
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 */

function exportExcel($title = array(), $data = array(), $fileName = '', $savePath = './Excel/', $isDown = true)
{
    /** Include PHPExcel_IOFactory */
    vendor("PHPExcel.PHPExcel");
    vendor("PHPExcel.PHPExcel.IOFactory");
    $obj = new \PHPExcel();
    //横向单元格标识
    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称
    $_row = 1;   //设置纵向单元格标识
    if ($title) {
        $_cnt = count($title);
        $obj->getActiveSheet(0)->mergeCells('A' . $_row . ':' . $cellName[$_cnt - 1] . $_row);   //合并单元格
        $obj->setActiveSheetIndex(0)->setCellValue('A' . $_row, '数据导出：' . date('Y-m-d H:i:s'));  //设置合并后的单元格内容
        $_row++;
        $i = 0;
        foreach ($title AS $v) {   //设置列标题
            $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i] . $_row, $v);
            $i++;
        }
        $_row++;
    }
    //填写数据
    if ($data) {
        $i = 0;
        foreach ($data AS $_v) {
            $j = 0;
            foreach ($_v AS $_cell) {
                $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $_row), $_cell);
                $j++;
            }
            $i++;
        }
    }
    //文件名处理
    if (!$fileName) {
        $fileName = uniqid(time(), true);
    }
    $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
    if ($isDown) {   //网页下载
        header('pragma:public');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWrite->save('php://output');
        exit;
    }
    $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
    $_savePath = $savePath . $_fileName . '.xlsx';
    $objWrite->save($_savePath);
    return $savePath . $fileName . '.xlsx';
}

//管理员操作日志
function admin_log($content, $model)
{
    $insert_data['content'] = $content;
    $insert_data['model'] = $model;
    $insert_data['admin_id'] = session('admin_id');
    $insert_data['create_time'] = time();
    $res = db('admin_log')->insert($insert_data);
    return $res;
}

//解析淘口令
function http_curl($url, $post_data)
{
    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    //curl_setopt($curl, CURLOPT_HEADER, 1);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return $data;
}


/*移动端判断*/
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

    /**
     * 单文件上传
     *  $file = request()->file('file');  //获取到上传的文件
     * @param $file
     * @return array|string
     */
   function uploadFile($file)
    {
        import('alioss.autoload',EXTEND_PATH,'.php');
        $resResult = Image::open($file);
        // 尝试执行
        try {
            $config = Config::get('aliyunOss'); //获取Oss的配置
            //实例化对象 将配置传入
            $ossClient = new OssClient($config['KeyId'], $config['KeySecret'], $config['Endpoint']);
            //这里是有sha1加密 生成文件名 之后连接上后缀
            $fileName = sha1(date('YmdHis', time()) . uniqid()) . '.' . $resResult->type();
            //执行阿里云上传
            $result = $ossClient->uploadFile($config['Bucket'], $fileName, $file->getInfo()['tmp_name']);
            $arr = [
                'address:' => $result['info']['url'],
            ];
            return $arr;
        } catch (OssException $e) {
            return $e->getMessage();
        }
        //将结果输出
        dump($arr);
    }


/**
     * 多图片上传
     * @param $file_arr
     * @return array|string
     */
     function uploadarr($file_arr){
        import('alioss.autoload',EXTEND_PATH,'.php');
        foreach($file_arr as $key=>$val){
            $resResult = Image::open($val);
            // 尝试执行
            try {
                $config = Config::get('aliyunOss'); //获取Oss的配置
                //实例化对象 将配置传入
                $ossClient = new OssClient($config['KeyId'], $config['KeySecret'], $config['Endpoint']);
                //这里是有sha1加密 生成文件名 之后连接上后缀
                $fileName = sha1(date('YmdHis', time()) . uniqid()) . '.' . $resResult->type();
                //执行阿里云上传
                $result = $ossClient->uploadFile($config['Bucket'], $fileName, $val->getInfo()['tmp_name']);
                $arr[$key] = $result['info']['url'];
            } catch (OssException $e) {
                return $e->getMessage();
            }
        }//endforeach
        return $arr;
    }

