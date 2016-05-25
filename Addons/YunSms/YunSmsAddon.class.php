<?php

namespace Addons\YunSms;

use Common\Controller\Addon;

class YunSmsAddon extends Addon
{
    public $info = array(
        'name' => 'YunSms',
        'title' => '云短信网',
        'description' => '云短信网短信插件 http://www.yunsms.cn/',
        'status' => 1,
        'author' => '駿濤',
        'version' => '1.0.0'
    );

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    /**
     * sms  短信钩子，必需，用于确定插件是短信服务
     * @return bool
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    public function sms()
    {
        return true;
    }
    public function sendSms($mobile, $content){
        $uid = modC('SMS_UID', '', 'USERCONFIG');
        $pwd = modC('SMS_PWD', '', 'USERCONFIG');
        $http = 'http://http.yunsms.cn/tx/';
        if (empty($uid) || empty($pwd)) {
            return '管理员还未配置短信信息，请联系管理员配置';
        }
        $data = array
        (
            'uid' => $uid, //用户账号
            'pwd' => strtolower(md5($pwd)), //MD5位32密码
            'mobile' => $mobile, //号码
            'content' => $content, //内容
            'time' => '', //定时发送
            'mid' => '', //子扩展号
            'encode' => 'utf8',
        );
        $re = $this->postSMS($http, $data); //POST方式提交
        if (trim($re) == '100') {
            return true;
        } else {
            return "发送失败! 状态：" . $re;
        }
    }


    private function postSMS($url, $data = '')
    {
        $row = parse_url($url);
        $host = $row['host'];
        $port = $row['port'] ? $row['port'] : 80;
        $file = $row['path'];
        $post = '';
        while (list($k, $v) = each($data)) {
            $post .= rawurlencode($k) . "=" . rawurlencode($v) . "&"; //转URL标准码
        }
        $post = substr($post, 0, -1);
        $len = strlen($post);
        $fp = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$fp) {
            return "$errstr ($errno)\n";
        } else {
            $receive = '';
            $out = "POST $file HTTP/1.0\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Content-type: application/x-www-form-urlencoded\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Content-Length: $len\r\n\r\n";
            $out .= $post;
            fwrite($fp, $out);
            while (!feof($fp)) {
                $receive .= fgets($fp, 128);
            }
            fclose($fp);
            $receive = explode("\r\n\r\n", $receive);
            unset($receive[0]);
            return implode("", $receive);
        }
    }



}