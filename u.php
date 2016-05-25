<?php
function _GET($n) { return isset($_GET[$n]) ? $_GET[$n] : NULL; }
function _SERVER($n) { return isset($_SERVER[$n]) ? $_SERVER[$n] : '[undefine]'; }
if (_GET('act') == 'phpinfo') {
if (function_exists('phpinfo')) phpinfo();
else echo 'phpinfo() Function has been disabled.';
exit;
}
$Info = array();
$Info['php_ini_file'] = function_exists('php_ini_loaded_file') ? php_ini_loaded_file() : '[undefine]';
function get_ea_info($name) { $ea_info = eaccelerator_info(); return $ea_info[$name]; }
function get_gd_info($name) { $gd_info = gd_info(); return $gd_info[$name]; }
function memory_usage() { $memory  = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB'; return $memory;}
function micro_time_float() { $mtime = microtime(); $mtime = explode(' ', $mtime); return $mtime[1] + $mtime[0];}
define('YES', '<span style="color: #008000; font-weight : bold;">已开启</span>');
define('NO', '<span style="color: #e74c3c; font-weight : bold;">未开启</span>');
$up_start = micro_time_float();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PHP探针-UPUPW绿色服务器平台APACHE专用版</title>
<meta name="keywords" content="PHP探针,PHP组件,UPUPW,apache,绿色服务器,php,sqlsrv,Pthreads,ionCube,memcache,XCache,SendMail" />
<meta name="description" content="UPUPW APACHE专用版PHP探针可检测ionCube,sqlsrv,Pthreads,memcache,XCache,和SendMail等PHP组件." />
<meta name="author" content="UPUPW" />
<meta name="reply-to" content="webmaster@upupw.net" />
<meta name="copyright" content="UPUPW Team" />
<style type="text/css">
<!--
*{margin:0px;padding:0px;}
body {background-color:#FFFFFF;color:#000000;margin:0px;font-family:"微软雅黑", Tahoma, sans-serif;}
input {text-align:center;width:200px;height:20px;padding:5px;}
a:link {color:#e74c3c; text-decoration:none;}
a:visited {color:#e74c3c;text-decoration:none;}
a:active {color:#ed776b;text-decoration:none;}
a:hover {color:#ed776b;text-decoration:none;}
table {border-collapse:collapse;margin:10px 0px;clear:both;}
.inp tr th, td {padding:2px 5px 2px 5px;vertical-align:center;text-align:center;height:30px; border:1px #FFFFFF solid;}
.head1 { background-color: #2c3e50; width: 100%; font-size: 36px; color: #ffffff; padding-top: 10px; text-align: center; font-family: Georgia, "Times New Roman", Times, serif; font-weight: bold; }
.head2 { background-color: #1abc9c; width: 100%; font-size: 18px; height: 18px; color: #ffffff; }
.el { text-align: center; background-color: #d3e1e5; }
.er { text-align: right; background-color: #d3e1e5; }
.ec { text-align: center; background-color: #1abc9c; font-weight: bold; color: #FFFFFF; }
.fl { text-align: left; background-color: #ecf0f1; color: #505050; }
.fr {text-align:right;background-color:#eeeeee;color:#505050;}
.fc { text-align: center; background-color: #ecf0f1; color: #505050; }
.ft {text-align:center;background-color: #D9F9DE;color:#060;}
a.arrow {font-family:webdings,sans-serif;font-size:10px;}
a.arrow:hover {color:#ff0000;text-decoration:none;}
-->
</style>
</head>
<body>
<div class="head1">{ UPUPW PHP 探针 }</div>
<div class="head2"></div>
<div style="margin:0 auto;width:1001px;overflow:hidden;">
<table width="100%" class="inp">
<tr>
<th colspan="2" class="ec" width="50%">服务器信息</th>
<th colspan="2" class="ec" width="50%">PHP功能组件开启状态</th>
</tr>
<tr>
<td width="100"  class="er">服务器域名</td>
<td  class="fl"><?=_SERVER('SERVER_NAME')?></td>
<td class="er">MySQL Client组件</td>
<td width="285" class="fc"><?=function_exists('mysql_close') ? YES : NO ?></td>
</tr>
<tr>
<td class="er">服务器端口</td>
<td class="fl"><?=_SERVER('SERVER_ADDR').':'._SERVER('SERVER_PORT')?></td>
<td class="er">SQLite Client组件</td>
<td class="fc"><?=phpversion('pdo_sqlite') ? YES : NO ?></td>   
</tr>
<tr>
<td class="er">服务器环境</td>
<td class="fl"><?=stripos(_SERVER('SERVER_SOFTWARE'), 'PHP')?_SERVER('SERVER_SOFTWARE'):_SERVER('SERVER_SOFTWARE')?></td>
<td class="er">GD library组件</td>
<td class="fc"><?=function_exists('gd_info') ? YES : NO ?></td>    
</tr>
<tr>
<td class="er">PHP运行环境</td>
<td class="fl"><?=PHP_SAPI .' PHP/'.PHP_VERSION?></td>
<td class="er">EXIF信息查看组件</td>
<td class="fc"><?=phpversion('exif') ? YES : NO ?></td>   
</tr>
<tr>
<td class="er" style="color: #e74c3c;">PHP配置文件</td>
<td class="fl"><?=$Info['php_ini_file']?></td>
<td class="er">OpenSSL协议组件</td>
<td class="fc"><?=function_exists('openssl_open') ? YES : NO ?></td>   
</tr>
<tr>
<td widtd="165" class="er">当前网站目录</td>
<td widtd="235" class="fl"><?=_SERVER('DOCUMENT_ROOT')?></td>
<td class="er">Mcrypt加密处理组件</td>
<td class="fc"><?=function_exists('mcrypt_cbc') ? YES : NO ?></td>    
</tr>
<tr>
<td class="er">服务器标准时</td>
<td class="fl">
<?=gmdate('Y-m-d', time()+TimeZone*3600)?> <?=gmdate('H:i:s', time()+TimeZone*3600)?> <span style="color: #999999;">(<?=(TimeZone<0?'-':'+').gmdate('H:i', abs(TimeZone)*3600)?>)</span>
</td>
<td widtd="190" class="er" >IMAP电子邮件函数库</td>
<td widtd="310" class="fc"><?=function_exists('imap_close') ? YES : NO ?></td>
</tr>
<tr>
<td class="er">软件管理设置</td>
<td class="fl">
<a target="_blank" href='<?=_SERVER('PHP_SELF')?>?act=phpinfo'>PHP详细信息</a> | <a target="_blank" href="/pmd/">phpMyAdmin管理</a>
</td>
<td class="er">SendMail电子邮件支持</td>
<td class="fc"><?=phpversion('standard') ? YES : NO ?></td>
</tr>
</table>
<table width="100%" class="inp">
<tr>
<td colspan="1" class="ec" width="25%">PHP 多线程组件</td>
<td colspan="1" class="ec" width="25%">PHP Zend解密组件</td>
<td colspan="3" class="ec" width="50%">PHP 缓存优化组件</td>
</tr>
<tr>
<td class="el">PHP Pthreads</td>
<td class="el">ionCube Loader</td>
<td class="el">XCache</td>
<td class="el">ZendOPcache</td>
<td class="el">Memcache</td>
</tr>
<tr>
<td class="fc"><?=phpversion('Pthreads') ? YES : NO ?></td>
<td class="fc"><?=function_exists('ionCube_Loader_version') ? YES : NO ?></td>
<td class="fc"><?=phpversion('XCache') ? YES : NO ?></td>
<td class="fc"><?=phpversion('Zend OPcache') ? YES : NO ?></td>
<td class="fc"><?=function_exists('memcache_close') ? YES : NO ?></td>
</tr>
<tr>
<td colspan="8" class="ft">UPUPW <?=PHP_SAPI .' PHP/'.PHP_VERSION?>包含以上部分组件，不同PHP版本包含组件不同，请根据需要在UPUPW面板PHP功能选项开启</td>
</tr>
</table>
<table width="100%" class="inp">
<tr>
<th class="ec">PHP已编译模块检测</th>
</tr>
<tr>
<td class="fl" style="text-align:center;">
<?php
$able=get_loaded_extensions();
foreach ($able as $key=>$value) {
if ($key!=0 && $key%13==0) {
echo '<br />';
}
echo "$value&nbsp;&nbsp;&nbsp;&nbsp;";
}
?>
</td>
</tr>
</table>
<form method="post" action="<?=_SERVER('PHP_SELF')?>">
<table width="100%" class="inp">
<tr>
<th colspan="4" class="ec">数据库连接测试</th>
</tr>
<tr>
<td colspan="4" class="ft">请及时登录phpMyAdmin修改数据库默认用户名和密码</td>
</tr>
<tr>
<td width="25%" class="er">数据库服务器</td>
<td width="25%" class="fl"><input type="text" name="mysqlHost" value="localhost" /></td>
<td width="25%" class="er">数据库数据名称</td>
<td width="25%" class="fl"><input type="text" name="mysqlDb" value="test" /></td>
</tr>
<tr>
<td class="er">数据库用户名</td>
<td class="fl"><input type="text" name="mysqlUser" value="root" /></td>
<td class="er">数据库用户密码</td>
<td class="fl"><input type="text" name="mysqlPassword" /></td>
</tr>
<tr>
<td colspan="4" align="center"><input type="submit" value=" 连 接 " name="act" style="height:30px;" /></td>
</tr>
</table>
</form>
<?php if(isset($_POST['act'])) {?>
<table width="100%" class="inp">
<tr>
<th colspan="4" class="ec">数据库测试结果</th>
</tr>
<?php
$link = @mysql_connect($_POST['mysqlHost'], $_POST['mysqlUser'], $_POST['mysqlPassword']);
$errno = mysql_errno();
if ($link) $str1 = '<span style="color: #008000; font-weight: bold;">连接正常</span> ('.mysql_get_server_info($link).')';
else $str1 = '<span style="color: #ff0000; font-weight: bold;">连接错误</span><br />'.mysql_error();
?>
<tr>
<td colspan="2" class="er" width="50%"><?=$_POST['mysqlHost']?></td>
<td colspan="2" class="fl" width="50%"><?=$str1?></td>
</tr>
<tr>
<td colspan="2" class="er">数据库<?=$_POST['mysqlDb']?></td>
<td colspan="2" class="fl"><?=(@mysql_select_db($_POST['mysqlDb'],$link))?'<span style="color: #008000; font-weight: bold;">访问正常</span>':'<span style="color: #ff0000; font-weight: bold;">访问错误</span>'?></td>
</tr>
</table>
<?
}
?>
<p style="color:#33384e;font-size:14px;text-align:center; margin-bottom:2px;">
<?php $up_time = sprintf('%0.6f', micro_time_float() - $up_start);?>页面执行时间 <?php echo $up_time?> 秒&nbsp;&nbsp;&nbsp;消耗内存 <?php echo memory_usage();?>
</p>
<hr style="width:100%; color:#cdcdcd" noshade="noshade" size="1" />
<p style="color:#505050; font-size:14px; text-align:center;">&copy;2015 <a href="http://www.upupw.net">WWW.UPUPW.NET</a> 版权所有</p>
</div>
</body>
</html>
