<?php
/**
 * 中文转拼音类
 * */
namespace Common\Model;



class ImageWatermarkModel {

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





// //使用示例

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



 















