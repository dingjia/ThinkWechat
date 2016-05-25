<?php
namespace Wechat\Controller;
use Think\Controller;
use Wechat\Sdk\TPWechat;
use Wechat\Sdk\Wechat;
use Wechat\Sdk\errCode;

class BaseController extends Controller{
    protected $debug = true;

    protected function options($appid){

        $options = S('options');
        if($this->debug || !$options){
            $options = array();
            $map['id']=$appid;
            $options = D('Wechat')->where($map)->limit(1)->find();
            $options['base']=array('token'=>$options['token'],'encodingaeskey'=>$options['encodingaeskey'],'appid'=>$options['appid'],'appsecret'=>$options['appsecret'],'agentid'=>$options['agentid']);
            // $options['base']['debug']=1;
            S('options', $options);
        }

        return $options;
    }


     public function getShortUrl($long_url,$aid=0){

        $appid=D('Wechat/Wechat')->mustOneWechat($aid);
        $options =D('Wechat/Wechat')->GetOptions($appid);
   
                 $weObj = new Wechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $shortUrl=ErrCode::getErrText($weObj->errCode);

                 $shortUrl=$weObj->getShortUrl($long_url);  
                 if (!$shortUrl)  $shortUrl=ErrCode::getErrText($weObj->errCode);

        return $shortUrl;
    }

   

   
}