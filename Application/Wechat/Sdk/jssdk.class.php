<?php
/**
 * 微信oAuth认证示例
 */
namespace Wechat\Sdk;
// include("wechat.class.php");
class jssdk extends Wechat {
	private $options;
	public $openid;
	public $jssdk;
	
	public function __construct($appid){
		$options=D('Wechat/Wechat')->GetOptions($appid); 
		$this->options = $options['base'];
		$this->myjssdk();
		session_start();
	}
	
	public function myjssdk(){
		$weObj = new Wechat($this->options);
        $auth = $weObj->checkAuth();
        $js_ticket = $weObj->getJsTicket();
        if (!$js_ticket) $this->error(ErrCode::getErrText($weObj->errCode) );  
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $this->jssdk = $weObj->getJsSign($url);
        return  $this->jssdk ;
	}
}
// $options = array(
// 		'token'=>'tokenaccesskey', //填写你设定的key
// 		'appid'=>'wxdk1234567890', //填写高级调用功能的app id, 请在微信开发模式后台查询
// 		'appsecret'=>'xxxxxxxxxxxxxxxxxxx', //填写高级调用功能的密钥
// );
// $auth = new wxauth($options);
// var_dump($auth->wxuser);
