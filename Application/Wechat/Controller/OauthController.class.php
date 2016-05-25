<?php
namespace Wechat\Controller;
use Think\Controller;
use Wechat\Sdk\TPWechat;
use Wechat\Sdk\Wechat;
use Wechat\Sdk\errCode;



class OauthController extends Controller{
    private $options;
    public $openid;
    public $wxuser;
    
    public function __construct($options){
        $this->options = $options;
        $this->wxoauth();
        session_start();
    }
    
   public function wxoauth(){
        $scope = 'snsapi_base';
        $code = isset($_GET['code'])?$_GET['code']:'';
        $token_time = isset($_SESSION['token_time'])?$_SESSION['token_time']:0;
        if(!$code && isset($_SESSION['openid']) && isset($_SESSION['user_token']) && $token_time>time()-3600)
        {
            if (!$this->wxuser) {
                $this->wxuser = $_SESSION['wxuser'];
            }
            $this->openid = $_SESSION['openid'];
            return $this->openid;
        }
        else
        {
            $options = array(
                    'token'=>$this->options["token"], //填写你设定的key
                    'appid'=>$this->options["appid"], //填写高级调用功能的app id
                    'appsecret'=>$this->options["appsecret"] //填写高级调用功能的密钥
            );
            $we_obj = new Wechat($options);
            if ($code) {
                $json = $we_obj->getOauthAccessToken();
                if (!$json) {
                    unset($_SESSION['wx_redirect']);
                    $this->error('获取用户授权失败，请重新确认');
                }
                $_SESSION['openid'] = $this->openid = $json["openid"];
                $access_token = $json['access_token'];
                $_SESSION['user_token'] = $access_token;
                $_SESSION['token_time'] = time();
                $userinfo = $we_obj->getUserInfo($this->openid);
                if ($userinfo && !empty($userinfo['nickname'])) {
                    $this->wxuser = array(
                            'openid'=>$this->openid,
                            'nickname'=>$userinfo['nickname'],
                            'sex'=>intval($userinfo['sex']),
                            'location'=>$userinfo['province'].'-'.$userinfo['city'],
                            'avatar'=>$userinfo['headimgurl']
                    );
                } elseif (strstr($json['scope'],'snsapi_userinfo')!==false) {
                    $userinfo = $we_obj->getOauthUserinfo($access_token,$this->openid);
                    if ($userinfo && !empty($userinfo['nickname'])) {
                        $this->wxuser = array(
                                'openid'=>$this->openid,
                                'nickname'=>$userinfo['nickname'],
                                'sex'=>intval($userinfo['sex']),
                                'location'=>$userinfo['province'].'-'.$userinfo['city'],
                                'headimgurl'=>$userinfo['headimgurl']
                        );
                    } else {
                        return $this->openid;
                    }
                }
                if ($this->wxuser) {
                    $_SESSION['wxuser'] = $this->wxuser;
                    $_SESSION['openid'] =  $json["openid"];
                    unset($_SESSION['wx_redirect']);
                    return $this->openid;
                }
                $scope = 'snsapi_userinfo';
            }
            if ($scope=='snsapi_base') {
                $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $_SESSION['wx_redirect'] = $url;
            } else {
                $url = $_SESSION['wx_redirect'];
            }
            if (!$url) {
                unset($_SESSION['wx_redirect']);
                $this->error('获取用户授权失败');
            }
            $oauth_url = $we_obj->getOauthRedirect($url,"wxbase",$scope);
            header('Location: ' . $oauth_url);
        }
    }
}