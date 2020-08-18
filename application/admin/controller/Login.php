<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Session;
use PHPExcel_IOFactory;
use PHPExcel;

/**
 * 会员登录
 * Class Login
 * @package app\admin\controller
 */
class Login extends Controller
{


    public function index(){
        return view();
    }


  public function login_in(){
      $data=input();
      $ip = request()->ip();
      if(!isset($data['user_name']) ||empty($data['user_name'])){
       return $this->error('请输入用户名!');
      }

      if(!isset($data['password']) ||empty($data['password'])){
          return $this->error('请输入密码!');
      }
      $info=db('admin_user')->where('user_name',$data['user_name'])->field('id,user_name,password,state,logins_num')->find();//查询会员信息
      if(empty($info)){
          return $this->error('会员账号未注册!');
      }
      if($info['state']==0){
          return $this->error('已被限制登录!');
      }
      if(md5($data['password'])==$info['password']){
          Session::set('admin_id',$info['id']);
          Session::set('admin_name',$info['user_name']);
          $updata['logins_ip']=$ip;
          $updata['logins_num']=$info['logins_num']+1;
          $updata['logins_time']=time();

          db('admin_user')->where('user_name',$data['user_name'])->update($updata);
          return $this->success('登录成功！',url('index/index'));//登录成功！
      }else{
          return $this->error('密码错误!');
      }
  }

    /**
     * 退出登录
     */
  public function out(){
      session('admin_id','null');
      session('admin_name','null');
      $this->success("退出成功！",'login/index');
  }
    /**
     * 数据导出
     * @param array $title   标题行名称
     * @param array $data   导出数据
     * @param string $fileName 文件名
     * @param string $savePath 保存路径
     * @param $type   是否下载  false--保存   true--下载
     * @return string   返回文件全路径
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */

    function exportExcel($title=array(), $data=array(), $fileName='', $savePath='./Excel/', $isDown=true){
        /** Include PHPExcel_IOFactory */
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.IOFactory");
        $obj = new \PHPExcel();
        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称
        $_row = 1;   //设置纵向单元格标识
        if($title){
            $_cnt = count($title);
            $obj->getActiveSheet(0)->mergeCells('A'.$_row.':'.$cellName[$_cnt-1].$_row);   //合并单元格
            $obj->setActiveSheetIndex(0)->setCellValue('A'.$_row, '数据导出：'.date('Y-m-d H:i:s'));  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach($title AS $v){   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i].$_row, $v);
                $i++;
            }
            $_row++;
        }
        //填写数据
        if($data){
            $i = 0;
            foreach($data AS $_v){
                $j = 0;
                foreach($_v AS $_cell){
                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i+$_row), $_cell);
                    $j++;
                }
                $i++;
            }
        }
        //文件名处理
        if(!$fileName){
            $fileName = uniqid(time(),true);
        }
        $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        if($isDown){   //网页下载
            header('pragma:public');
            header("Content-Disposition:attachment;filename=$fileName.xls");
            $objWrite->save('php://output');exit;
        }
        $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
        $_savePath = $savePath.$_fileName.'.xlsx';
        $objWrite->save($_savePath);
        return $savePath.$fileName.'.xlsx';
    }



    function excelToArray(){
        /** Include PHPExcel_IOFactory */
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.IOFactory");
       // $obj = new \PHPExcel();

        //加载excel文件
        $filename = '买家任务导出表.xls';
        if (!file_exists($filename)) {
            exit("文件".$filename."不存在");
        }
        $objPHPExcel = PHPExcel_IOFactory::load($filename);
        //开始读取上传到服务器中的Excel文件，返回一个二维数组
        $dataArray = $objPHPExcel->getSheet(0)->toArray();
        $sheet_count = $objPHPExcel->getSheetCount();
        for ($s = 0; $s < $sheet_count; $s++)
        {
            $currentSheet = $objPHPExcel->getSheet($s);// 当前页
            $row_num = $currentSheet->getHighestRow();// 当前页行数
            $col_max = $currentSheet->getHighestColumn(); // 当前页最大列号

            // 循环从第二行开始，第一行往往是表头
            for($i = 3; $i <= $row_num; $i++)
            {
                $cell_values = array();
                for($j = 'A'; $j < $col_max; $j++)
                {
                    $address = $j . $i; // 单元格坐标
                    $cell_values[] = $currentSheet->getCell($address)->getFormattedValue();
                }
                $data_list[]=$cell_values;
                // 看看数据

            }
            print_r($data_list);
        }
    }


    public function automatic_delivery(){
        $task_list=db('user_task')->where(['delivery_state'=>1,'state'=>3])->select();
        $delivery=time()-60*60*60; //60小时发货
        $number=array(
            "1580535416647809-1580537038644", "1580626979712925-1580629259117","1580706029942436-1580709586919",
            "1580525228179132-1580526425810", "1580382606675234-1580536987846","1580524918465002-1580528838983",
            "1580619866437585-1580619906573", "1580723211502239-1580726890343","1580456141261682-1580456625198",
            "1580530414297745-1580541381991", "1580005941765347-1580387210383","1580628454646543-1580628888216",
            "1580636520630498-1580637652123", "1580719871679926-1580728550359","1580874198575468-1580876669528",
            "1580535398446959-1580536098615", "1580539125170481-1580539233411","1580566193942612-1580612988308",
            "1580787383885491-1580794149140", "1580969768826872-1580972665175","1580690876185861-1580696726410",
            "1580524984581009-1580526195730", "1580436298277265-1580436952126","1580520607412858-1580627304984",
            "1580481384382742-1580485081961", "1580462481157298-1580493652777","1580475088220528-1580488956436",
            "1580434190298800-1580492884606", "1580810924802098-1580811321212","1581217472816393-1581217955686",
            "1580881744115184-1580885121904", "1581220606166179-1581221027389","1580984757202937-1580985138456",
            "1580885935244655-1580886157291", "1580970382243463-1580970726696","1580975942389828-1580982827819",
            "1581226064117600-1581226373699", "1581224054555958-1581224293576","1580795984632597-1580796449585",
            "1580987976172661-1580988505256", "1580716605912916-1580727772526","1580730652228760-1580734016265",
            "1580888517389515-1580888976420", "1580989125312668-1580989627384","1580796811169045-1580797460699",
            "1581251739500663-1581254323486", "1581130846278904-1581137323986","1580962963112521-1580963565810",
            "1581817831307227-1581818064370", "1581586640793328-1581587053531","1581788380738866-1581815402195",
            "1581588245619833-1581588601554", "1580608601137080-1580611996996","1581162854569822-1581209181263",
            "1583579442395535-1583579961696", "1580613304204640-1580624666250","1580622940162213-1580628660518",
        );
        foreach ($task_list as $v){
//            if(in_array($v['task_number'], $number)){
//                continue;
//            }
            if($v['delivery_time']<=$delivery){
                db('user_task')->where('id',$v['id'])->update(['delivery_state'=>2,'state'=>4,'fahuo_time'=>time()]);
            }
        }
        echo "操作成功！";exit;
    }

}
