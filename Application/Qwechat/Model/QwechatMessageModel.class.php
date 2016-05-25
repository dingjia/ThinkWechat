<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/16/14
 * 创建时间: 10:56 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Qwechat\Model;

use Qwechat\Sdk\TPWechat;
use Qwechat\Sdk\errCode;

use Think\Model;

class QwechatMessageModel extends Model
{
    
  


      /**
     * sendCustomMessage  发送客服消息
     * @param $to_uids 接收消息的用户们
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param string $url 消息指向的路径，U函数的第一个参数
     * @param array $url_args 消息链接的参数，U函数的第二个参数
     * @param int $from_uid 发送消息的用户
     * @param int $type 消息类型，0系统，1用户，2应用
     * @return bool
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    public function sendQmessage($to,$appid,$data)
    {
        
    //构造消息体 
       if (is_numeric($data)) $info = D('News/NewsRob')->getRob($rob);   //通过精准的id获取素材
       if (!is_array($data)){
          $info['content']=$data;
          $info['type']='text';
       }
            
      $message['msgtype'] = $info['type'];
      switch ($info['type']) {
          case 'text':
          case 'wxcard':
          case 'music':
          case 'video':
          case 'image':
               $message[$info['type']]['content'] = $info['content'];
               break;
          case 'news':
               $message[$info['type']]['articles'] = $info['content'];
               break;
           }

      //登录微信
       $options = D('Qwechat/Qwechat')->GetOptions($appid);
       $message['agentid'] = $options['agentid'];
       $weObj = new TPWechat( $options['base']);
       $ret=$weObj->checkAuth();
      if (!$ret) $error= (ErrCode::getErrText($weObj->errCode).'8888888'.$member['appid']);
     
   
       //处理发送对象
       if (!is_array($to)){
          $message['touser'] = $to;
        }else{
          $message['touser'] = $to['touser'];
          $message['toparty'] = $to['toparty'];
          $message['totag'] = $to['totag'];
       }
      
         

      //  }else{

      //      // $to_uids = is_array($to_uids) ? $to_uids : array($to_uids);
      //      // foreach ($to_uids as $to_uid) {
      //      //     $map['userid']=$to_uid;
      //      //      // $map['userid']=1510;
      //      //      $member=M('QwechatMember')->where($map)->find();
      //      //      if ($member){
      //      //          $message['touser'] = $member['userid'];
      //      //          $list=$weObj->sendMessage( $message);     
      //      //      }
      //      //  }
      // }
       
       
       $res=$weObj->sendMessage( $message); 
      if (!$res) $error= ErrCode::getErrText($weObj->errCode);
       return $error;
    }


  

}