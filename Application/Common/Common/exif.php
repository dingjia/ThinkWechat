<?
/**
* 获取图象信息的函数
* 一个全面获取图象信息的函数
* @access public
* @param string $img 图片路径
* @return array
*/
function GetImageInfoVal($ImageInfo,$val_arr) {
  $InfoVal  =  "未知";
  foreach($val_arr as $name=>$val) {
    if ($name==$ImageInfo) {
      $InfoVal  =  &$val;
      break;
    }
  }
  return $InfoVal;
}
function GetImageInfo($img) {
  $imgtype      =  array("", "GIF", "JPG", "PNG", "SWF", "PSD", "BMP", "TIFF(intel byte order)", "TIFF(motorola byte order)", "JPC", "JP2", "JPX", "JB2", "SWC", "IFF", "WBMP", "XBM");
  $Orientation    =  array("", "top left side", "top right side", "bottom right side", "bottom left side", "left side top", "right side top", "right side bottom", "left side bottom");
  $ResolutionUnit    =  array("", "", "英寸", "厘米");
  $YCbCrPositioning  =  array("", "the center of pixel array", "the datum point");
  $ExposureProgram  =  array("未定义", "手动", "标准程序", "光圈先决", "快门先决", "景深先决", "运动模式", "肖像模式", "风景模式");
  $MeteringMode_arr  =  array(
    "0"    =>  "未知",
    "1"    =>  "平均",
    "2"    =>  "中央重点平均测光",
    "3"    =>  "点测",
    "4"    =>  "分区",
    "5"    =>  "评估",
    "6"    =>  "局部",
    "255"  =>  "其他"
    );
  $Lightsource_arr  =  array(
    "0"    =>  "未知",
    "1"    =>  "日光",
    "2"    =>  "荧光灯",
    "3"    =>  "钨丝灯",
    "10"  =>  "闪光灯",
    "17"  =>  "标准灯光A",
    "18"  =>  "标准灯光B",
    "19"  =>  "标准灯光C",
    "20"  =>  "D55",
    "21"  =>  "D65",
    "22"  =>  "D75",
    "255"  =>  "其他"
    );
  $Flash_arr      =  array(
    "0"    =>  "flash did not fire",
    "1"    =>  "flash fired",
    "5"    =>  "flash fired but strobe return light not detected",
    "7"    =>  "flash fired and strobe return light detected",
    );
   
  $exif = exif_read_data ($img,"IFD0");
  if ($exif===false) {
    $new_img_info  =  array ("文件信息"    =>  "没有图片EXIF信息");
  }
  else
  {
    $exif = exif_read_data ($img,0,true);
    $new_img_info  =  array (
      "文件信息"    =>  "-----------------------------",
      "文件名"    =>  $exif[FILE][FileName],
      "文件类型"    =>  $imgtype[$exif[FILE][FileType]],
      "文件格式"    =>  $exif[FILE][MimeType],
      "文件大小"    =>  $exif[FILE][FileSize],
      "时间戳"    =>  date("Y-m-d H:i:s",$exif[FILE][FileDateTime]),
      "图像信息"    =>  "-----------------------------",
      "图片说明"    =>  $exif[IFD0][ImageDescription],
      "制造商"    =>  $exif[IFD0][Make],
      "型号"      =>  $exif[IFD0][Model],
      "方向"      =>  $Orientation[$exif[IFD0][Orientation]],
      "水平分辨率"  =>  $exif[IFD0][XResolution].$ResolutionUnit[$exif[IFD0][ResolutionUnit]],
      "垂直分辨率"  =>  $exif[IFD0][YResolution].$ResolutionUnit[$exif[IFD0][ResolutionUnit]],
      "创建软件"    =>  $exif[IFD0][Software],
      "修改时间"    =>  $exif[IFD0][DateTime],
      "作者"      =>  $exif[IFD0][Artist],
      "YCbCr位置控制"  =>  $YCbCrPositioning[$exif[IFD0][YCbCrPositioning]],
      "版权"      =>  $exif[IFD0][Copyright],
      "摄影版权"    =>  $exif[COMPUTED][Copyright.Photographer],
      "编辑版权"    =>  $exif[COMPUTED][Copyright.Editor],
      "拍摄信息"    =>  "-----------------------------",
      "Exif版本"    =>  $exif[EXIF][ExifVersion],
      "FlashPix版本"  =>  "Ver. ".number_format($exif[EXIF][FlashPixVersion]/100,2),
      "拍摄时间"    =>  $exif[EXIF][DateTimeOriginal],
      "数字化时间"  =>  $exif[EXIF][DateTimeDigitized],
      "拍摄分辨率高"  =>  $exif[COMPUTED][Height],
      "拍摄分辨率宽"  =>  $exif[COMPUTED][Width],
      /*
      The actual aperture value of lens when the image was taken.
      Unit is APEX.
      To convert this value to ordinary F-number(F-stop),
      calculate this value's power of root 2 (=1.4142).
      For example, if the ApertureValue is '5', F-number is pow(1.41425,5) = F5.6.
      */
      "光圈"      =>  $exif[EXIF][ApertureValue],
      "快门速度"    =>  $exif[EXIF][ShutterSpeedValue],
      "快门光圈"    =>  $exif[COMPUTED][ApertureFNumber],
      "最大光圈值"  =>  "F".$exif[EXIF][MaxApertureValue],
      "曝光时间"    =>  $exif[EXIF][ExposureTime],
      "F-Number"    =>  $exif[EXIF][FNumber],
      "测光模式"    =>  GetImageInfoVal($exif[EXIF][MeteringMode],$MeteringMode_arr),
      "光源"      =>  GetImageInfoVal($exif[EXIF][LightSource], $Lightsource_arr),
      "闪光灯"    =>  GetImageInfoVal($exif[EXIF][Flash], $Flash_arr),
      "曝光模式"    =>  ($exif[EXIF][ExposureMode]==1?"手动":"自动"),
      "白平衡"    =>  ($exif[EXIF][WhiteBalance]==1?"手动":"自动"),
      "曝光程序"    =>  $ExposureProgram[$exif[EXIF][ExposureProgram]],
      /*
      Brightness of taken subject, unit is APEX. To calculate Exposure(Ev) from BrigtnessValue(Bv), you must add SensitivityValue(Sv).
      Ev=Bv+Sv  Sv=log((ISOSpeedRating/3.125),2)
      ISO100:Sv=5, ISO200:Sv=6, ISO400:Sv=7, ISO125:Sv=5.32. 
      */
      "曝光补偿"    =>  $exif[EXIF][ExposureBiasValue]."EV",
      "ISO感光度"    =>  $exif[EXIF][ISOSpeedRatings],
      "分量配置"    =>  (bin2hex($exif[EXIF][ComponentsConfiguration])=="01020300"?"YCbCr":"RGB"),//'0x04,0x05,0x06,0x00'="RGB" '0x01,0x02,0x03,0x00'="YCbCr"
      "图像压缩率"  =>  $exif[EXIF][CompressedBitsPerPixel]."Bits/Pixel",
      "对焦距离"    =>  $exif[COMPUTED][FocusDistance]."m",
      "焦距"      =>  $exif[EXIF][FocalLength]."mm",
      "等价35mm焦距"  =>  $exif[EXIF][FocalLengthIn35mmFilm]."mm",
      /*
      Stores user comment. This tag allows to use two-byte character code or unicode. First 8 bytes describe the character code. 'JIS' is a Japanese character code (known as Kanji).
      '0x41,0x53,0x43,0x49,0x49,0x00,0x00,0x00':ASCII
      '0x4a,0x49,0x53,0x00,0x00,0x00,0x00,0x00':JIS
      '0x55,0x4e,0x49,0x43,0x4f,0x44,0x45,0x00':Unicode
      '0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00':Undefined
      */
      "用户注释编码"  =>  $exif[COMPUTED][UserCommentEncoding],
      "用户注释"    =>  $exif[COMPUTED][UserComment],
      "色彩空间"    =>  ($exif[EXIF][ColorSpace]==1?"sRGB":"Uncalibrated"),
      "Exif图像宽度"  =>  $exif[EXIF][ExifImageLength],
      "Exif图像高度"  =>  $exif[EXIF][ExifImageWidth],
      "文件来源"    =>  (bin2hex($exif[EXIF][FileSource])==0x03?"digital still camera":"unknown"),
      "场景类型"    =>  (bin2hex($exif[EXIF][SceneType])==0x01?"A directly photographed image":"unknown"),
      "缩略图文件格式"  =>  $exif[COMPUTED][Thumbnail.FileType],
      "缩略图Mime格式"  =>  $exif[COMPUTED][Thumbnail.MimeType]
    );
  }
  return $new_img_info;
}
 
// $innerhtml  =  "";
// $exif  =  GetImageInfo($_GET['img']);
// $innerhtml  .=  "<TABLE>";
 
// foreach($exif as $name=>$val) {
//   $innerhtml  .=  "<TR><TD>{$name}</TD><TD>{$val}</TD></TR>";
// }
 
// $innerhtml  .=  "<TR><TD colspan=\"2\">";
// if ($_GET['img']) {
//   $image = exif_thumbnail($_GET['img']);
// } else {
//   $image = false;
// }
// if ($image!==false) {
//   $innerhtml  .=  "<img src=\"thumbnail.php?img=".$_GET['img']."\">";
// } else {
//   // no thumbnail available, handle the error here
//   $innerhtml  .=  "No thumbnail available";
// }
 
// $innerhtml  .=  "</TD></TR></TABLE>";
?>
