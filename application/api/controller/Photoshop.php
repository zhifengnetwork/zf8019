<?php

namespace app\api\controller;


use app\common\controller\ApiBase;
use QrCode\src\QrCode;
use think\Db;
use think\Exception;
use think\Image;
use think\Request;

class Photoshop extends ApiBase {

    private static $Qrcode_url = 'http://newretailweb.zhifengwangluo.com/Register';
    private static $savePath = '';
    private static $bg_path = '';
    private static $mof_bg_path = '';
    private static $host = '';
    private static $black_ttf_path = '';
    private static $regular_ttf_path = '';
    private static $default_head_path = '';

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

    }

    /**
     * 合成图片
     * @param invite_code 邀请码
     * @return array|string
     * @throws \QrCode\src\Exception\InvalidWriterException
     */
    public static function makePhoto($invite_code){
        //底部背景图
        $img_bg = self::$bg_path;
        //保存路径
        $savePath = self::$savePath;

        //图片名称
        $haibao_name = $invite_code.'.jpg';
        //返回地址
        $path = self::$host.$haibao_name;
        try{
            //判断保存路径文件夹是否存在
            if (!file_exists($savePath))
                mkdir($savePath,777,true);

            //拼接二维码链接
            $qr_url = self::$Qrcode_url.'?uid='.$invite_code;

            //获取二维码文件路径
            $qr_code_path = self::getQRcode($qr_url,'file',$invite_code);

            //生成二维码缩略图
            $width = '104';
            $height = '104';
            $qrcode = Image::open($qr_code_path);
            $qrcode->thumb($width,$height,Image::THUMB_CENTER)->save($qr_code_path);


            //添加二维码水印
            $bg = Image::open($img_bg);
            $bg->water($qr_code_path,[96,950])->save($savePath.$haibao_name);


            //删除二维码文件
            unlink($qr_code_path);
        }catch (Exception $e){
            return ['result'=>false,'msg'=>$e->getMessage()];
        }

        return $path;
    }


    /**
     * 生成二维码
     * @param url  二维码链接
     * @param string $type 生成类型 image：二维码 file：文件
     * @param string $file_name 保存文件名
     * @return string
     * @throws \QrCode\src\Exception\InvalidWriterException
     */
    public static function getQRcode($url,$type='image',$file_name=''){
        $qr_url = 'outpauth://fenziweilai/'.$url;
        $qr = new QrCode($qr_url);
        header('Content-Type:'.$qr->getContentType());
        $qr->setText($url)
            ->setMargin(10)
            ->setForegroundColor(['r'=>0,'g'=>0,'b'=>0])
            ->setBackgroundColor(['r'=>255,'g'=>255,'b'=>255]);
        //清除缓存
        ob_clean();
        if ($type=='file'){
            $savePath = self::$savePath.$file_name.'QrCode.jpg';
            $qr->writeFile($savePath);
            return $savePath;
        }
        echo $qr->writeString();
        exit();
    }


    public function getPosterPhoto(){
        $result = [];
        try{
            $user_id = $this->get_user_id();
            header('Access-Control-Allow-Origin:*');
            //获取用户头像、昵称
            $user = $this->userInfo;
            //判断用户名
            $member = Db::name('member')->where(['id'=>$user_id])->find();
            $name = '我是';
            $avatar = 'tou.png';
            //获取用户分子裂变累计收益
            $amount=$member['realname'];

            if ($avatar==''||$avatar==null){
                $avatar_path = self::$default_head_path;
            }else {
                //拼接用户实际头像路径
                $names = 'poster';
                if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){
                    mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
                }

                $upload_path = dirname(realpath(APP_PATH)) ."\\public\\upload\\poster\\";

                $avatar_path = $upload_path . $avatar;
                //判断头像不存在的情况
                if(!is_file($avatar_path)){
                    $avatar_path = self::$default_head_path;
                }
            }
            //转换为圆形
            $radius_avatar_path = $this->getRadiusImg($avatar_path,142,142,71,self::$savePath);
            if ($radius_avatar_path ==false)
                return $this->failResult("Pictures can't be found.");


            //贴文字
            $bg = Image::open($upload_path.'bg.jpg');

            //我是
            $name_font = dirname(realpath(APP_PATH)).'\\vendor\\topthink\\think-captcha\\assets\\zhttfs\\1.ttf';
            $name_size = '24';
            $name_color = '#000000';

            //名字
            $amount_size = '24';
            $amount_color = '#FF0000';

            $enter_font_value = '长按二维码保存';
            $amount_size = '24';
            $enter_font_color = '#000000';

            //贴二维码
            //拼接二维码链接
            $qr_url = self::$Qrcode_url.'?uid='.$user_id;

            //获取二维码文件路径
            $qr_code_path = self::getQRcode($qr_url,'file',$user_id);

            //生成二维码缩略图
            //添加二维码水印
            $width = '304';
            $height = '304';
            $qrcode = Image::open($qr_code_path);
            $qrcode->thumb($width,$height,Image::THUMB_CENTER)->save($qr_code_path);

            //保存图片到本地
            $save_path = ROOT_PATH .Config('c_pub.img').'poster/'.$user_id.'.jpg';

            //贴图贴文字
                    $bg ->text($name,$name_font,$name_size,$name_color,['300','310'])
                        ->text($amount,$name_font,$amount_size,$amount_color,['380','310'])
                        ->text($enter_font_value,$name_font,$amount_size,$enter_font_color,['300','880'])
                        ->water($radius_avatar_path,['330','150'])
                        ->water($qr_code_path,[250,530])->save($save_path)
                        ->save($save_path);

            //删除二维码文件
            unlink($qr_code_path);

            //删除png格式头像
            unlink($radius_avatar_path);
            $return_path = SITE_URL.Config('c_pub.img').'poster/'.$user_id.'.jpg';
            return $return_path;
        }catch (Exception $e){
            $result = $this->failResult($e->getMessage());
        }
        return $result;
    }

    /**
     * 获取圆角图片
     * @param file_path   原文件路径
     * @param width    宽
     * @param height   高
     * @param radius   圆角半径 (圆形图片 宽=高 圆角半径=宽/2)
     * @param save_path 保存路径，到文件夹
     * @return bool|string
     */
    public function getRadiusImg($file_path,$width,$height,$radius,$save_path){

        if (!is_file($file_path))
            return false;
        $file_name = pathinfo($file_path)['filename'];
        //生成缩略图（固定大小）
        $img =  Image::open($file_path);
        $jpg_save_path = $save_path.$file_name.'.jpg';
        $img->thumb($width,$height,Image::THUMB_CENTER)->save($jpg_save_path);
        //生成圆形
        $radius_img = $this->radius_img($jpg_save_path,$radius);
        //头像转png
        $png_save_path = $save_path.$file_name.'.png';
        imagepng($radius_img,$png_save_path);
        //删除jpg格式图片
        unlink($jpg_save_path);
        return $png_save_path;
    }


    /**
     * 处理圆角图片
     * @param  string  $imgpath 源图片路径
     * @param  integer $radius  圆角半径长度默认为15,处理成圆型
     * @return [type]           [description]
     */
    function radius_img($imgpath = './t.png', $radius = 15) {
        $imageInfo = getimagesize($imgpath);
        $src_img = null;
        switch ($imageInfo['mime']) {
            case "image/gif":
                $src_img = imagecreatefromgif($imgpath);
                break;
            case "image/jpeg":
                $src_img = imagecreatefromjpeg($imgpath);
                break;
            case "image/png":
                $src_img = imagecreatefrompng($imgpath);
                break;
            case "image/bmp":
                $src_img = imagecreatefromwbmp($imgpath);
                break;
        }
        $w = $imageInfo[0];
        $h = $imageInfo[1];
        // $radius = $radius == 0 ? (min($w, $h) / 2) : $radius;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }

}