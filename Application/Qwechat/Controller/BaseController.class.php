<?php
namespace Qwechat\Controller;
use Think\Controller;

class BaseController extends Controller{
    protected $debug = true;

    protected function options($appid){

        $options = S('options');
        if($this->debug || !$options){
            $options = array();
            $map['id']=$appid;
            $options = D('Qwechat')->where($map)->limit(1)->find();
            $options['base']=array('token'=>$options['token'],'encodingaeskey'=>$options['encodingaeskey'],'appid'=>$options['appid'],'appsecret'=>$options['appsecret'],'agentid'=>$options['agentid']);
            $options['base']['debug']=1;
            $options['base']['logcallback']='logdebug';
            S('options', $options);
        }

        return $options;
    }

    protected function getAreplyModel(){
        return D('Weixin/WeixinAreply');
    }


}