<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 茉莉清茶 <57143976@qq.com> <http://www.3spp.cn>
// +----------------------------------------------------------------------


/**
 * 系统公共库文件扩展
 * 主要定义系统公共函数库扩展
 */

/**
 * 获取 IP  地理位置
 * 淘宝IP接口
 * @Return: array
 */
use Vendor\PHPMailer;



function get_city_by_ip($ip)
{
    $url = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
    $ipinfo = json_decode(file_get_contents($url));
    if ($ipinfo->code == '1') {
        return false;
    }
    $city = $ipinfo->data->region . $ipinfo->data->city; //省市县
    $ip = $ipinfo->data->ip; //IP地址
    $ips = $ipinfo->data->isp; //运营商
    $guo = $ipinfo->data->country; //国家
    if ($guo == L('_CHINA_')) {
        $guo = '';
    }
    return $guo . $city . $ips . '[' . $ip . ']';

}

/**
* 验证手机号是否正确
* @author 范鸿飞
* @param INT $mobile
*/
 function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
 }

 //判断是否是手机
function checkAgent()
{
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $is_pc = (strpos($agent, 'windows nt')) ? true : false;
        $is_mac = (strpos($agent, 'mac os')) ? true : false;
        $is_iphone = (strpos($agent, 'iphone')) ? true : false;
        $is_android = (strpos($agent, 'android')) ? true : false;
        $is_ipad = (strpos($agent, 'ipad')) ? true : false;
        

        if($is_pc){
              return  'pc';
        }
        
        if($is_mac){
              return  'mac';
        }
        
        if($is_iphone){
              return  'iphone';
        }
        
        if($is_android){
              return  'android';
        }
        
        if($is_ipad){
              return  'ipad';
        }
}

/**
 * 系统邮件发送函数
 * @param string $to 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * @茉莉清茶 57143976@qq.com
 */
function send_mail($to = '498944516@qq.com', $subject = '', $body = '', $name = '', $attachment = null)
{
    $host = C('MAIL_SMTP_HOST');
    $user = C('MAIL_SMTP_USER');
    $pass = C('MAIL_SMTP_PASS');
    if (empty($host) || empty($user) || empty($pass)) {
        return L('_THE_ADMINISTRATOR_HAS_NOT_YET_CONFIGURED_THE_MESSAGE_INFORMATION_PLEASE_CONTACT_THE_ADMINISTRATOR_CONFIGURATION_');
    }

    if (is_sae()) {
        return sae_mail($to, $subject, $body, $name);
    } else {
        return send_mail_local($to, $subject, $body, $name, $attachment);
    }
}

/**
 * SAE邮件发送函数
 * @param string $to 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * @茉莉清茶 57143976@qq.com
 */
function sae_mail($to = '', $subject = '', $body = '', $name = '')
{
    $site_name = modC('WEB_SITE_NAME', '轻时光', 'Config');
    if ($to == '') {
        $to = C('MAIL_SMTP_CE'); //邮件地址为空时，默认使用后台默认邮件测试地址
    }
    if ($name == '') {
        $name = $site_name; //发送者名称为空时，默认使用网站名称
    }
    if ($subject == '') {
        $subject = $site_name; //邮件主题为空时，默认使用网站标题
    }
    if ($body == '') {
        $body = $site_name; //邮件内容为空时，默认使用网站描述
    }
    $mail = new SaeMail();
    $mail->setOpt(array(
        'from' => C('MAIL_SMTP_USER'),
        'to' => $to,
        'smtp_host' => C('MAIL_SMTP_HOST'),
        'smtp_username' => C('MAIL_SMTP_USER'),
        'smtp_password' => C('MAIL_SMTP_PASS'),
        'subject' => $subject,
        'content' => $body,
        'content_type' => 'HTML'
    ));

    $ret = $mail->send();
    return $ret ? true : $mail->errmsg(); //返回错误信息
}

function is_sae()
{
    return function_exists('sae_debug');
}

function is_local()
{
    return strtolower(C('PICTURE_UPLOAD_DRIVER')) == 'local' ? true : false;
}

/**
 * 用常规方式发送邮件。
 */
function send_mail_local($to = '', $subject = '', $body = '', $name = '', $attachment = null)
{
    $from_email = C('MAIL_SMTP_USER');
    $from_name = modC('WEB_SITE_NAME', L('_OPENSNS_OPEN_SOURCE_SOCIAL_SYSTEM_'), 'Config');
    $reply_email = '';
    $reply_name = '';

    //require_once('./ThinkPHP/Library/Vendor/PHPMailer/phpmailer.class.php');增加命名空间，可以注释掉此行
    $mail = new PHPMailer(); //实例化PHPMailer
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP(); // 设定使用SMTP服务
    $mail->SMTPDebug = 0; // 关闭SMTP调试功能
    // 1 = errors and messages
    // 2 = messages only
    $mail->SMTPAuth = true; // 启用 SMTP 验证功能

    $mail->SMTPSecure = ''; // 使用安全协议
    $mail->Host = C('MAIL_SMTP_HOST'); // SMTP 服务器
    $mail->Port = C('MAIL_SMTP_PORT'); // SMTP服务器的端口号
    $mail->Username = C('MAIL_SMTP_USER'); // SMTP服务器用户名
    $mail->Password = C('MAIL_SMTP_PASS'); // SMTP服务器密码
    $mail->SetFrom($from_email, $from_name);
    $replyEmail = $reply_email ? $reply_email : $from_email;
    $replyName = $reply_name ? $reply_name : $from_name;
    if ($to == '') {
        $to = C('MAIL_SMTP_CE'); //邮件地址为空时，默认使用后台默认邮件测试地址
    }
    if ($name == '') {
        $name = modC('WEB_SITE_NAME', L('_OPENSNS_OPEN_SOURCE_SOCIAL_SYSTEM_'), 'Config'); //发送者名称为空时，默认使用网站名称
    }
    if ($subject == '') {
        $subject = modC('WEB_SITE_NAME', L('_OPENSNS_OPEN_SOURCE_SOCIAL_SYSTEM_'), 'Config'); //邮件主题为空时，默认使用网站标题
    }
    if ($body == '') {
        $body = modC('WEB_SITE_NAME', L('_OPENSNS_OPEN_SOURCE_SOCIAL_SYSTEM_'), 'Config'); //邮件内容为空时，默认使用网站描述
    }
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body); //解析
    $mail->AddAddress($to, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }

    return $mail->Send() ? true : $mail->ErrorInfo; //返回错误信息
}

function thinkox_hash($message, $salt = "OpenSNS")
{
    $s01 = $message . $salt;
    $s02 = md5($s01) . $salt;
    $s03 = sha1($s01) . md5($s02) . $salt;
    $s04 = $salt . md5($s03) . $salt . $s02;
    $s05 = $salt . sha1($s04) . md5($s04) . crc32($salt . $s04);
    return md5($s05);
}

/**获取模块的后台设置
 * @param        $key 获取模块的配置
 * @param string $default 默认值
 * @param string $module 模块名，不设置用当前模块名
 * @return string
 * @auth 陈一枭
 */
function modC($key, $default = '', $module = '')
{
    $mod = $module ? $module : MODULE_NAME;
    $must_aid=session('user_auth.aid')?session('user_auth.aid'):0;
    $aid=$aid?$aid:$must_aid;
    
    if(MODULE_NAME=="Install"&&$key=="NOW_THEME"){
        return $default;
    }
    $result = S('conf_' . strtoupper($mod) . '_' . strtoupper($key) .'_' .$aid);
    if (empty($result)) {
        $map['aid'] = $aid;
        $map['name'] ='_' . strtoupper($mod) . '_' . strtoupper($key);
        $config = D('Config')->where($map)->find();
        if (!$config) {
            $result = $default;
        } else {
            $result = $config['value'];
        }
        S('conf_' . strtoupper($mod) . '_' . strtoupper($key) .'_' .$aid,$result);
    }

    return $result;
}

function myConfig($key,$aid=null, $default = '', $module = '')
{
    $mod = $module ? $module : MODULE_NAME;
    $must_aid=session('user_auth.aid')?session('user_auth.aid'):0;
    $aid=$aid?$aid:$must_aid;
    if(MODULE_NAME=="Install"&&$key=="NOW_THEME"){
        return $default;
    }
    $result = S('conf_' . strtoupper($mod) . '_' . strtoupper($key) .'_' .$aid);
    if (empty($result)) {
        $map['aid']=$aid;
        $map['name'] ='_' . strtoupper($mod) . '_' . strtoupper($key);
        $config = D('Config')->where($map)->find();

        if (!$config['value']) {
            $result = $default;
        } else {
            $result = $config['value'];
        }
       
    S('conf_' . strtoupper($mod) . '_' . strtoupper($key) .'_' .$aid,$result);
    }
    return $result;
}



/**发送短消息
 * @param        $mobile 手机号码
 * @param        $content 内容
 * @return string
 * @auth 肖骏涛
 */
function sendSMS($mobile, $content)
{

    // $sms_hook = modC('SMS_HOOK','SmsBao','USERCONFIG');   //不知道为什么这里获取不了
    $sms_hook='SmsBao';
    $sms_hook =  check_sms_hook_is_exist($sms_hook);

    if($sms_hook == 'none'){
        return  $sms_hook.L('_THE_ADMINISTRATOR_HAS_NOT_CONFIGURED_THE_SMS_SERVICE_PROVIDER_INFORMATION_PLEASE_CONTACT_THE_ADMINISTRATOR_');
    }
    $name = get_addon_class($sms_hook);
    $class = new $name();
    return $class->sendSms($mobile,$content);

}

/**发送微信消息
 * @param        $mobile 手机号码
 * @param        $content 内容
 * @return string
 * @auth 肖骏涛
 */
function sendQmessage($to,$appid,$data)
{
  
   $ret=D('Qwechat/QwechatMessage')->sendQmessage($to,$appid,$data);
   
       
   return $ret; 

}

/**发送微信消息
 * @param        $mobile 手机号码
 * @param        $content 内容
 * @return string
 * @auth 肖骏涛
 */
function sendCustomMessage($to_uids,$info)
{

   $ret=D('Wechat/WechatMessage')->sendCustomMessage($to_uids,$info);
   return $ret; 

}

function sendTemplateMessage($to_uids,$template_id,$url,$first,$keynote1,$keynote2='',$keynote3='',$keynote4='',$keynote5='',$remark='')
{
   $ret=D('Wechat/WechatMessage')->sendTemplateMessage($to_uids,$template_id,$url,$first,$keynote1,$keynote2,$keynote3,$keynote4,$keynote5,$remark);
   return $ret; 
}

 function mylog($log){
    if (is_array($log)) $log = print_r($log,true);
    file_put_contents('mylog.txt',$log."\n",FILE_APPEND); 
}

function mymysql($module){
    mylog($module->getlastsql());
}





/**
 * get_kanban_config  获取看板配置
 * @param $key
 * @param $kanban
 * @param string $default
 * @param string $module
 * @return array|bool
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function get_kanban_config($key, $kanban, $default = '', $module = '')
{
    $config = modC($key, $default, $module);
    if (is_array($config)) {
        return $config;
    } else {
        $config = json_decode($config, true);
        foreach ($config as $v) {
            if ($v['data-id'] == $kanban) {
                $res = $v['items'];
                break;
            }
        }
        return getSubByKey($res, 'data-id');
    }


}




/**
 *
 * function qrcode(){
 *     $filename='qrcode.png';
 *     $logo=SITE_PATH."\\Public\\Home\\images\\logo_80.png";
 *     qrcode('http://www.dellidc.com',$filename,false,$logo,8,'L',2,true);
 * }
 *
 * @param $data 二维码包含的文字内容
 * @param $filename 保存二维码输出的文件名称，*.png
 * @param bool $picPath 二维码输出的路径
 * @param bool $logo 二维码中包含的LOGO图片路径
 * @param string $size 二维码的大小
 * @param string $level 二维码编码纠错级别：L、M、Q、H
 * @param int $padding 二维码边框的间距
 * @param bool $saveandprint 是否保存到文件并在浏览器直接输出，true:同时保存和输出，false:只保存文件
 * return string
 */
function qrcode($data,$filename,$picPath=false,$logo=false,$size='4',$level='L',$padding=2,$saveandprint=false){
    vendor("phpqrcode.phpqrcode");//引入工具包
    // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
    $path = $picPath?$picPath:__ROOT__."\\Uploads\\Picture\\QRcode"; //图片输出路径
    mkdir($path);//dump($path);exit;
    //在二维码上面添加LOGO
    if(empty($logo) || $logo=== false) { //不包含LOGO
        if ($filename==false) {
            QRcode::png($data, false, $level, $size, $padding, $saveandprint); //直接输出到浏览器，不含LOGO
        }else{
            $filename=$path.'/'.$filename; //合成路径
            QRcode::png($data, $filename, $level, $size, $padding, $saveandprint); //直接输出到浏览器，不含LOGO
        }
    }else { //包含LOGO
        if ($filename==false){
            //$filename=tempnam('','').'.png';//生成临时文件
            die(L('_PARAMETER_ERROR_'));
        }else {
            //生成二维码,保存到文件
            $filename = $path . '\\' . $filename; //合成路径
        }
        QRcode::png($data, $filename, $level, $size, $padding);
        $QR = imagecreatefromstring(file_get_contents($filename));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        if ($filename === false) {
            Header("Content-type: image/png");
            imagepng($QR);
        } else {
            if ($saveandprint === true) {
                imagepng($QR, $filename);
                header("Content-type: image/png");//输出到浏览器
                imagepng($QR);
            } else {
                imagepng($QR, $filename);
            }
        }
    }
    return $filename;
}

function import_lang($module_name){
    $file=APP_PATH . '/'.$module_name.'/Lang/' . LANG_SET . '.php';
    if (is_file($file))
        L(include $file);
}

/**
 * 及时显示提示信息
 * @param  string $msg 提示信息
 */
function show_msg($msg, $class = '',$progress=100)
{
    echo "<script type=\"text/javascript\">showmsg(\"{$msg}\", \"{$class}\", \"{$progress}\")</script>";
    ob_flush();
    flush();


}

//传id 获取 父id
 function unLimitForParents($cate , $id ){
        $arr = array();
        unset($cate['id']);
       
        foreach($cate as $val){     
       
            if($val['id'] == $id){
                
                $arr[] = $val['id'];
                
                $arr = array_merge(unLimitForParents($cate , $val['pid']) , $arr);
                
            }
    
        }
        
        return $arr;

 }


  function unLimitForLevel($cate , $pid = 1 , $level = 0){
        $arr = array();
    
        foreach($cate as $key => $val){     
    
            if($val['pid'] == $pid){
                
                $val['level'] = $level + 1;
                $last_data= unLimitForLevel($cate , $val['id'] , $level+1);
                if ($last_data)$val['nodes']=$last_data;
                $arr[] = $val;
                
                
            }
    
        }
        return $arr;

    }

 function unLimitForLayer($cate , $pid = 1){
        $arr = array();
    
        foreach($cate as $val){     
    
            if($val['pid'] == $pid){
                
                $v['name'] = unLimitForLayer($cate , $val['id']);
                
                $arr[] = $v;
                
            }
    
        }
        return $arr;

    }

function oneToTwo($a){
        $b = array();
    
        foreach ($a as $key => $value) {
          $b[]=Array('id'=>$key,'value'=>$value);
        }
        return $b;

    }


 function birthdayReminder($birthday,$reminder ){

     $preg = '/^(\d{4}|\d{2}|)[- ]?(\d{2})[- ]?(\d{2})$/';
     $Ymd = array();
     
     preg_match($preg, $birthday, $Ymd);
     if (empty($Ymd) ||empty($birthday)) return false;

     
     $birthday = $Ymd[2].'-'.$Ymd[3];
     $time = time();

     for ($i = 1; $i <=  $reminder; $i++){
         
      if (date('m-d', $time) == $birthday) {
         if ($i==1) {
            return "(今天生日)";
          }else {
             return '('.$i."天后生日)";
          }
      }
      $time = $time + 24 * 3600;
     }
     return "";

}


//替换用户信息
  function rootKeyReplace($str,$data=array()){
      
       if ($data['member']['openid']){
         $str=str_replace("{粉丝OPENID}",$data['member']['openid'], $str);
         $str=str_replace("{粉丝ID}",$data['member']['id'], $str);
         $str=str_replace("{粉丝昵称}",$data['member']['nickname'], $str);
       }
       if ($data['member']['userid']){
        $str=str_replace("{员工USERID}",$data['FromUserName'], $str);
        $str=str_replace("{员工姓名}",$data['member']['name'], $str);
        $str=str_replace("{员工电话}",$data['member']['mobile'], $str);
       }

        // $str=str_replace("{员工空间}",'您好：', $str);
        $str=str_replace("{时间}",date('Y-m-d',time()), $str);
        $str=str_replace("{系统域名}", 'http://'.$_SERVER[HTTP_HOST], $str);

         $str=str_replace("</br>","\n", $str);
       
       
        return $str;
    }


function simplexml_to_array($simplexml_obj, $array_tags=array(), $strip_white=1)
{    
    if( $simplexml_obj )
    {
        if( count($simplexml_obj)==0 )
            return $strip_white?trim((string)$simplexml_obj):(string)$simplexml_obj;
 
        $attr = array();
        foreach ($simplexml_obj as $k=>$val) {
            if( !empty($array_tags) && in_array($k, $array_tags) ) {
                $attr[] = simplexml_to_array($val, $array_tags, $strip_white);
            }else{
                $attr[$k] = simplexml_to_array($val, $array_tags, $strip_white);
            }
        }
        return $attr;
    }
     
    return FALSE;
}



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

