<?php
/**
 * 中文转拼音类
 * */
namespace Common\Model;

use Think\Model;

class PicModel extends Model{


/*
*说明：函数功能是把一个图像裁剪为任意大小的图像，图像不变形
* 参数说明：输入 需要处理图片的 文件名，生成新图片的保存文件名，生成新图片的宽，生成新图片的高
* written by smallchicken
* time 2008-12-18
*/
// 获得任意大小图像，不足地方拉伸，不产生变形，不留下空白
public function my_image_resize($src_file, $dst_file , $new_width , $new_height) {
if($new_width <1 || $new_height <1) {
return "params width or height error !";
exit();
}
if(!file_exists($src_file)) {
return $src_file . " is not exists !";
exit();
}
// 图像类型

$type=exif_imagetype($src_file);

$support_type=array(IMAGETYPE_JPEG , IMAGETYPE_PNG , IMAGETYPE_GIF);
if(!in_array($type, $support_type,true)) {
return "this type of image does not support! only support jpg , gif or png";
exit();
}
//Load image
switch($type) {
case IMAGETYPE_JPEG :
$src_img=imagecreatefromjpeg($src_file);
break;
case IMAGETYPE_PNG :
$src_img=imagecreatefrompng($src_file);
break;
case IMAGETYPE_GIF :
$src_img=imagecreatefromgif($src_file);
break;
default:
return "Load image error!";
exit();
}

$w=imagesx($src_img);
$h=imagesy($src_img);
$ratio_w=1.0 * $new_width / $w;
$ratio_h=1.0 * $new_height / $h;
$ratio=1.0;
// 生成的图像的高宽比原来的都小，或都大 ，原则是 取大比例放大，取大比例缩小（缩小的比例就比较小了）
if( ($ratio_w < 1 && $ratio_h < 1) || ($ratio_w > 1 && $ratio_h > 1)) {
if($ratio_w < $ratio_h) {
$ratio = $ratio_h ; // 情况一，宽度的比例比高度方向的小，按照高度的比例标准来裁剪或放大
}else {
$ratio = $ratio_w ;
}
// 定义一个中间的临时图像，该图像的宽高比 正好满足目标要求
$inter_w=(int)($new_width / $ratio);
$inter_h=(int) ($new_height / $ratio);
$inter_img=imagecreatetruecolor($inter_w , $inter_h);
imagecopy($inter_img, $src_img, 0,0,0,0,$inter_w,$inter_h);
// 生成一个以最大边长度为大小的是目标图像$ratio比例的临时图像
// 定义一个新的图像
$new_img=imagecreatetruecolor($new_width,$new_height);
imagecopyresampled($new_img,$inter_img,0,0,0,0,$new_width,$new_height,$inter_w,$inter_h);
switch($type) {
case IMAGETYPE_JPEG :
imagejpeg($new_img, $dst_file,100); // 存储图像
break;
case IMAGETYPE_PNG :
imagepng($new_img,$dst_file,100);
break;
case IMAGETYPE_GIF :
imagegif($new_img,$dst_file,100);
break;
default:
break;
}
} // end if 1
// 2 目标图像 的一个边大于原图，一个边小于原图 ，先放大平普图像，然后裁剪
// =if( ($ratio_w < 1 && $ratio_h > 1) || ($ratio_w >1 && $ratio_h <1) )
else{
$ratio=$ratio_h>$ratio_w? $ratio_h : $ratio_w; //取比例大的那个值
// 定义一个中间的大图像，该图像的高或宽和目标图像相等，然后对原图放大
$inter_w=(int)($w * $ratio);
$inter_h=(int) ($h * $ratio);
$inter_img=imagecreatetruecolor($inter_w , $inter_h);
//将原图缩放比例后裁剪
imagecopyresampled($inter_img,$src_img,0,0,0,0,$inter_w,$inter_h,$w,$h);
// 定义一个新的图像
$new_img=imagecreatetruecolor($new_width,$new_height);
imagecopy($new_img, $inter_img, 0,0,0,0,$new_width,$new_height);
switch($type) {
case IMAGETYPE_JPEG :
imagejpeg($new_img, $dst_file,100); // 存储图像
break;
case IMAGETYPE_PNG :
imagepng($new_img,$dst_file,100);
break;
case IMAGETYPE_GIF :
imagegif($new_img,$dst_file,100);
break;
default:
break;
}
}// if3
}// end function


	
	
	
	
	
public	function combine_image($image1,$image2,$image3,$opt = 100)  
{    
    $width  = 1319;  
    $height = 1491;  
    $im = imagecreatetruecolor($width, $height); 
    $white = imagecolorallocatealpha($im, 255, 255, 255,127);
    imagefill ($im, 0, 0, $white);
 $wimage_data = GetImageSize($image1); 
switch($wimage_data[2]) 
{ 
case 1: 
$im1=@ImageCreateFromGIF($image1); 
break; 
case 2: 
$im1=@ImageCreateFromJPEG($image1); 
break; 
case 3: 
$im1=@ImageCreateFromPNG($image1); 
break; 
}
 $wimage_data = GetImageSize($image2); 
switch($wimage_data[2]) 
{ 
case 1: 
$im2=@ImageCreateFromGIF($image2); 
break; 
case 2: 
$im2=@ImageCreateFromJPEG($image2); 
break; 
case 3: 
$im2=@ImageCreateFromPNG($image2); 
break; 
}
 $wimage_data = GetImageSize($image3); 
switch($wimage_data[2]) 
{ 
case 1: 
$im3=@ImageCreateFromGIF($image3); 
break; 
case 2: 
$im3=@ImageCreateFromJPEG($image3); 
break; 
case 3: 
$im3=@ImageCreateFromPNG($image3); 
break; 
}

 imagecopy($im1,$im2,0,0,0,0,100,100);  
// imagecopy($im1,$im3,10,0,0,0,143,266);  
    //================================================
    header('Content-Type: image/jpeg');  
    //$output_image = str_replace('.','_email.',$image1);  
    imagejpeg($im1,"a.jpg"); 
    imagedestroy($im);  
    imagedestroy($im1);  
       imagedestroy($im2); imagedestroy($im3);
        // imagedestroy($im_small);
}  
//combine_image('1.jpg','2.jpg');	
		
		
		
public function saveImage($path,$dirname) {

    if ( !preg_match('/\/([^\/]+\.[a-z]{3,4})$/i', $path, $matches) ) die('Use image please');

    $image_name = strToLower($matches[1]);

    $ch = curl_init ($path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

    $img = curl_exec ($ch);
    curl_close ($ch);
     
	//创建路径
   $dirname=VI_ROOT.$dirname;

   function createFolder($dirname){
   if (!file_exists($dirname)){   
   createFolder(dirname($dirname));     
   mkdir($dirname,0777); }
   }
   createFolder($dirname);  
	 
	 
    $fp = fopen($dirname."/".$image_name,'w');
    fwrite($fp, $img); 
    fclose($fp);

}




/**
  * 修改一个图片 让其翻转指定度数
  * 
  * @param string  $filename 文件名（包括文件路径）
  * @param  float $degrees 旋转度数
  * @return boolean
  * @author zhaocj
  */
  public  function  flip($filename,$src,$degrees = 90)
 {
  //读取图片
  $data = @getimagesize($filename);
  if($data==false)return false;
  //读取旧图片
  switch ($data[2]) {
   case 1:
    $src_f = imagecreatefromgif($filename);break;
   case 2:
    $src_f = imagecreatefromjpeg($filename);break;
   case 3:
    $src_f = imagecreatefrompng($filename);break;
  } 
  if($src_f=="")return false;
  $rotate = @imagerotate($src_f, $degrees,0);
  if(!imagejpeg($rotate,$src,100))return false;
  @imagedestroy($rotate);
  return true;
 }


 
 
	
}








/***********************************************************

类名：ImageWatermark

功能：用于生成图片或文字水印

************************************************************

合成水印：

1、图像水印appendImageMark(暂不可旋转)

2、文字水印appendTextMark(汉字水印需要设置汉字字体)（可旋转）



输出水印图像：write($filename=null)

1、输出到文件：指定$filename参数为输出的文件名。

2、输出到浏览器：不指定输出文件名，则输出到浏览器.



指定水印位置：

1、指定位置类型$markPosType:(default-0)

1-top left     2-top center     3-top right

4-middle left  5-middle center  6-middle right

7-bottom left  8-bottom center  9-bottom right

0-random

2、设置具体位置setMarkPos($x,$y)，若指定具体位置，则上面的位置类型无效。

************************************************************

*/
class WatermarkModel extends Model{


    public $markPosType = 0;          //水印位置，缺省为随机位置输出水印

    public $fontFile = 'arial.ttf';   //字体文件名

    public $color = '#CCCCCC';        //水印字体的颜色

    public $fontSize = 12;            //水印字体大小

    public $angle = 0;                //水印文字旋转的角度

    private $markPos = array();

    private $markImageFile = null, $destImageFile = null;

    private $mark_res = null, $mark_width = 0, $mark_height = 0, $mark_type = null;

    private $dest_res = null, $dest_width = 0, $dest_height = 0, $dest_type = null;



    //用目标图片作为构造函数的参数

    public function __construct($destImage){

        if(!file_exists($destImage)) return false;

        $this->destImageFile=$destImage;

        //获取图片大小、类型

        $imageInfo = getimagesize($this->destImageFile);

        $this->dest_width = $imageInfo[0];$this->dest_height = $imageInfo[1];$this->dest_type = $imageInfo[2];

        //得到图片资源句柄

        $this->dest_res = $this->getImageResource($this->destImageFile,$this->dest_type);

    }

    public function __destruct(){

        imagedestroy($this->dest_res);

    }

    //添加文字水印

    public function appendTextMark($markText){

        if($markText==null) return false;

        //计算水印文本的大小

        $box = imagettfbbox($this->fontSize,$this->angle,$this->fontFile,$markText);

        $this->mark_width = $box[2]-$box[6];

        $this->mark_height = $box[3]-$box[7];

        //计算水印位置

        $pos = ($this->markPos!=null)?$this->markPos:$this->getMarkPosition($this->markPosType);

        $pos[1]+=$this->mark_height;

        //将文字打印到图片上

        $RGB=$this->colorHexRgb($this->color);

        $imageColor=imagecolorallocate($this->dest_res,$RGB[0],$RGB[1],$RGB[2]);

        imagettftext($this->dest_res,$this->fontSize,$this->angle,$pos[0],$pos[1],$imageColor,$this->fontFile,$markText);

    }

    //添加图片水印

    public function appendImageMark($markImage){

        if(!file_exists($markImage)) return false;

        $this->markImageFile=$markImage;

        //获取水印图片大小、类型

        $imageInfo = getimagesize($this->markImageFile);

        $this->mark_width = $imageInfo[0];$this->mark_height = $imageInfo[1];$this->mark_type = $imageInfo[2];

        //得到图片资源句柄

        $this->mark_res = $this->getImageResource($this->markImageFile,$this->mark_type);

        //计算水印位置

        $pos = ($this->markPos!=null)?$this->markPos:$this->getMarkPosition($this->markPosType);

        //设置图像混色模式

        imagealphablending($this->dest_res, true);

        //复制叠加图像

        imagecopy($this->dest_res,$this->mark_res,$pos[0],$pos[1],0,0,$this->mark_width,$this->mark_height);

        imagedestroy($this->mark_res);

    }

    //将叠加水印后的图片写入指定文件，若不定文件名，则输出到浏览器

    public function write($filename){

        $this->writeImage($this->dest_res,$filename,$this->dest_type);

    }

    //设置水印x,y坐标

    public function setMarkPos($x,$y){

        $this->markPos[0]=$x; $this->markPos[1]=$y;

    }

    //将十六进制的颜色值分解成RGB形式

    private function colorHexRgb($color){

        $color = preg_replace('/#/','',$color);

        $R=hexdec($color[0].$color[1]);

        $G=hexdec($color[2].$color[3]);

        $B=hexdec($color[4].$color[5]);

        return array($R,$G,$B);

    }

    //计算水印位置

    private function getMarkPosition($type=0){

        switch($type){

            case 0: $x = rand(0,$this->dest_width-$this->mark_width);

                    $y = rand(0,$this->dest_height-$this->mark_height);

                    break;//random

            case 1: $x = 0;

                    $y = 0;

                    break;//topleft

            case 2: $x = ($this->dest_width-$this->mark_width)/2;

                    $y = 0;

                    break; //topcenter

            case 3: $x = $this->dest_width-$this->mark_width;

                    $y = 0;

                    break;// topright

            case 4: $x = 0;

                    $y = ($this->dest_height-$this->mark_height)/2;

                    break;//middleleft

            case 5: $x = ($this->dest_width-$this->mark_width)/2;

                    $y = ($this->dest_height-$this->mark_height)/2;

                    break;//middlecenter

            case 6: $x = $this->dest_width-$this->mark_width;

                    $y = ($this->dest_height-$this->mark_height)/2;

                    break;//middleright

            case 7: $x = 0; $y = $this->dest_height-$this->mark_height;

                    break;//bottomleft

            case 8: $x = ($this->dest_width-$this->mark_width)/2;

                    $y = $this->dest_height-$this->mark_height;

                    break;//bottomcenter

            case 9: $x = $this->dest_width-$this->mark_width;

                    $y = $this->dest_height-$this->mark_height;

                    break;//bottomright

            default:$x = rand(0,$this->dest_width-$this->mark_width);

                    $y = rand(0,$this->dest_height-$this->mark_height);

                    break;//random

        }

        return array($x,$y);

    }

    //从一个图像文件中取得图片资源标识符

    private function getImageResource($filename,$type=0){

        switch($type){

            case 1:return imagecreatefromgif($filename);break;

            case 2:return imagecreatefromjpeg($filename);break;

            case 3:return imagecreatefrompng($filename);break;

            // 以后可添加其它格式

            default:return null;

        }

    }

    //将图像写入文件或输出到浏览器

    private function writeImage($ImageRes,$filename=null,$type=0){

        switch($type) {

            case 1:imagegif($ImageRes,$filename);break;

            case 2:imagejpeg($ImageRes,$filename);break;

            case 3:imagepng($ImageRes,$filename);break;

            default:return null;

        }

        return true;

    }

}





//使用示例

// $markimg = new ImageWatermark('c_si.jpg');

// //$markimg->setMarkPos(100,200);//如何设置setMarkPos,则markPosType无效。

// $markimg->markPosType=5;

// $markimg->appendImageMark('mark.png');

// $markimg->fontFile='STCAIYUN.TTF';

// $markimg->color='#FFFFFF';

// $markimg->fontSize=24;

// $markimg->angle=45;//设置角度时，注意水印可能旋转出目标图片之外。

// $markimg->appendTextMark('汉字水印');

// $markimg->write();

// $markimg=null;



?>



 















