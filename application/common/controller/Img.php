<?php


namespace app\common\controller;


use think\Controller;
use think\Image;

class Img extends Controller
{
    /**
     * @notes 图片缩略处理
     * @date 2019/7/27
     * @time 14:36
     * @param int $file 图片地址
     * @param int $pathname 保存路径
     * @param int $width 新图片宽度
     * @param int $height 新图片高度
     * @param int $type 缩略类型  1等比例 2 填充 3 居中剪裁 4左上角裁剪 5右下角裁剪 6固定尺寸缩放
     */
    static public function compressImg($file,$pathname,$width=800,$height=800,$type=1){
        $image = Image::open($file);
        $image->thumb($width,$height,$type)->rotate(0)->save($pathname);
    }

    /**
     * [将Base64图片转换为本地图片并保存]
     * @param $base64_image_content [要保存的Base64]
     * @param $up_dir [要保存的路径]//存放在当前目录的upload文件夹下
     * @return bool|string
     */
    static public function base64_image_content($base64_img,$up_dir= './uploads/'){
        $img_path = $up_dir.date('Y/m/d').'/';
        if(!file_exists($img_path)){
            mkdir($img_path,0777,true);
        }
        if(!preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) return [ 'code'=>0,'data'=>'文件错误',];//文件错误
        $type = $result[2];
        if(!in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png')))return ['code'=>0,'data'=>'图片上传类型错误',];//文件类型错误
        //$new_file = $up_dir.date('YmdHis_').'.'.$type;
        $new_file = $img_path.time().rand(0,999999).'.'.$type;
        if(!file_put_contents($new_file,base64_decode(str_replace($result[1], '', $base64_img))))return ['code'=>0,'data'=>'图片上传失败',];//上传写入失败

        $size = filesize($img_path);

        if($size > 1024*1024)self::compressImg($img_path,$img_path,800,800,1);
        $img_path = str_replace('./', '/', $new_file);
        return ['code'=>1,'data'=>$img_path,];
    }

    /**
     * @Function [上传好评图片]
     * @Author 扬风
     * @Date: 2020/3/23
     * @Time: 16:16
     * @param $base64_img  要保存的Base64
     * @param string $up_dir [要保存的路径]//存放在当前目录的upload文件夹下
     * @return array
     */
    static public function base64_image_praise($base64_img,$up_dir= './uploads/'){
        $img_path = $up_dir.date('Y/m/d').'/';
        if(!file_exists($img_path)){
            mkdir($img_path,0777,true);
        }
        if(!preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) return [ 'code'=>0,'data'=>'文件错误',];//文件错误
        $type = $result[2];
        if(!in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png')))return ['code'=>0,'data'=>'图片上传类型错误',];//文件类型错误
        $new_file = $img_path.time().rand(0,999999).'.'.$type;
        if(!file_put_contents($new_file,base64_decode(str_replace($result[1], '', $base64_img))))return ['code'=>0,'data'=>'图片上传失败',];//上传写入失败

        $size = filesize($img_path);
        if($size > 3 * 1024*1024)self::compressImg($img_path,$img_path,1600,1600,1);
        $img_path = str_replace('./', '/', $new_file);
        return ['code'=>1,'data'=>$img_path,];
    }
    /**
     * [将Base64图片转换为本地图片并保存] 商品图片上传专用
     * @param $base64_image_content [要保存的Base64]
     * @param $up_dir [要保存的路径]//存放在当前目录的upload文件夹下
     * @return bool|string
     */
    static public function base64_image_goods($base64_img,$up_dir= './uploads/goods/'){
        $img_path = $up_dir;
        if(!file_exists($img_path)){
            mkdir($img_path,0777,true);
        }
        if(!preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) return [ 'code'=>0,'data'=>'文件错误',];//文件错误
        $type = $result[2];
        if(!in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png')))return ['code'=>0,'data'=>'图片上传类型错误',];//文件类型错误
        //$new_file = $up_dir.date('YmdHis_').'.'.$type;
        $new_file = $img_path.time().rand(0,999999).'.'.$type;
        if(!file_put_contents($new_file,base64_decode(str_replace($result[1], '', $base64_img))))return ['code'=>0,'data'=>'图片上传失败',];//上传写入失败
        $img_path = str_replace('./', '/', $new_file);
        $img = str_replace('./', '', $new_file);
        $size = filesize($img);//da xiao
        self::compressImg($img,$img,500,500,6);
        self::waterImg($img);
        return ['code'=>1,'data'=>$img_path,];
    }

    /**
     * @notes 给图片加水印平铺整个图片
     * @date 2019/8/26
     * @time 16:13
     * @param $img /图片图路径
     * @param int $num 选着水印图片
     * @param int $transparency 透明度（0~100）
     */
    static public function goodsImg($img,$num=1,$transparency=50){
        $img_path = "./uploads/goods/";
        if(!file_exists($img_path)){
            mkdir($img_path,0777,true);
        }
        $pics = "user/".$img;
        $image = Image::open($img);
        $pic = 'static/seller/img/imgs.png';
        if($num==2){
            $pic = 'static/seller/img/imgss.png';
        }
        if($num==3){
            $pic = 'static/seller/img/img.png';
        }
        if($num==4){
            $pic = 'static/seller/img/img2.png';
        }
        // 给原图添加水印平铺并保存
        $image->tilewater($pic,$transparency)->save($pics);
    }

    /**
     * @notes 给图片$img 加上$pic图片水印
     * @date 2019/8/26
     * @time 16:22
     * @param $img 原图
     * @param $pic 水印图
     * @param int $locate 水印位置
     * @param int $transparency
     */
    static public function waterImg($img,$locate=5,$transparency=100){
        $pic = "static/seller/img/images.png";
        //$locate，标识左上角水印 1;
        //$locate，标识上居中水印 2;
        //$locate，标识右上角水印3;
        //$locate，标识左居中水印4;
        //$locate，标识居中水印5;
        //$locate，标识右居中水印6;
        //$locate，标识左下角水印7;
        //$locate，标识下居中水印8;
        //$locate，标识右下角水印9;
        $pics = "user/".$img;
        $image = Image::open($img);
        // 给原图添加水印平铺并保存
        $image->water($pic,$locate,$transparency)->save($pics);
    }


    /**
     * [将Base64视频转换为本地视频并保存] 商品图片上传专用
     * @param $base64_image_content [要保存的Base64]
     * @param $up_dir [要保存的路径]//存放在当前目录的upload文件夹下
     * @return bool|string
     */
    static public function base64_image_video($base64_img,$up_dir= './uploads/video/'){
        $img_path = $up_dir.date('Y/m/d').'/';
        if(!file_exists($img_path)){
            mkdir($img_path,0777,true);
        }
        if(!preg_match('/^(data:s*video\/(\w+);base64,)/', $base64_img, $result)) return [ 'code'=>0,'data'=>'文件错误',];//文件错误
        $type = $result[2];
        if(!in_array($type,array('mp4')))return ['code'=>0,'data'=>'视频上传类型错误',];//文件类型错误
        $new_file = $up_dir.date('YmdHis_').'.'.$type;
        if(!file_put_contents($new_file,base64_decode(str_replace($result[1], '', $base64_img))))return ['code'=>0,'data'=>'视频上传失败',];//上传写入失败
        $img_path = str_replace('./', '/', $new_file);
        return ['code'=>1,'data'=>$img_path,];
    }

}