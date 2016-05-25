<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/16/14
 * 创建时间: 10:56 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Wechat\Model;
use Wechat\Sdk\Wechat;
use Wechat\Sdk\TPWechat;
use Wechat\Sdk\errCode;

use Think\Model;

class WechatMessageModel extends Model
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
    public function sendCustomMessage($to_uids,$info)
    {
       
   
       //处理发送对象
       $to_uids = is_array($to_uids) ? $to_uids : array($to_uids);
       foreach ($to_uids as $to_uid) {
       //寻找发送对象    
            if (is_numeric($to_uid)) {
                $member=M('WechatMember')->find($to_uid);
            }else{
                $map['openid']=$to_uid;
                $member=M('WechatMember')->where($map)->find();
            }

        //构造发送信息
            //如果是个数字，直接找机器人 
           $reply['member']=$member;
           if (is_numeric($info) ) $data = D('News/NewsRob')->getRob($info,$reply);   //通过精准的id获取素材
           //如果不是数组进行数组处理
           if (!is_array($info) and !is_numeric($info)){
              $data['content']=rootKeyReplace($info);
              $data['type']='text';
           }
           if (is_array($info) )  $data=$info;
            $message['msgtype'] = $data['type'];
            switch ($data['type']) {
                case 'text':
                case 'music':
                case 'video':
                case 'image':
                     $message[$data['type']]['content'] = $data['content'];
                     break;
                case 'news':
                     $message[$data['type']]['articles'] = $data['content'];
                     break;
                case 'wxcard':
                     $message[$data['type']]['card_id'] = $data['card_id'];
                     break;
                 }
        
        //开始发送   
            $options = D('Wechat/Wechat')->GetOptions($member['appid']);
            $weObj = new Wechat( $options['base']);
            $ret=$weObj->checkAuth();
            if (!$ret) $error= (ErrCode::getErrText($weObj->errCode).$member['appid']);
            if ($data){
                $message['touser'] = $member['openid'];
                $list=$weObj->sendCustomMessage( $message); 
                if (!$list) $error= (ErrCode::getErrText($weObj->errCode).$member['appid']);    
            }
        }
        return $eror;
    }


     /**
     * sendTemplateMessage  发送模板消息
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
    public function sendTemplateMessage($to_uids,$template_id,$url='http://baidu.com',$first,$keynote1,$keynote2='',$keynote3='',$keynote4='',$keynote5='',$remark='')
    {
      
        $message['template_id']=$template_id;
        $message['url']=$url;
        $message['data']['first']=array( "value"=>$first, "color"=>"#173177" );
        $message['data']['keyword1']=array( "value"=>$keynote1, "color"=>"#173177" );
        if ($keynote2)$message['data']['keyword2']=array( "value"=>$keynote2, "color"=>"#173177" );
        if ($keynote3)$message['data']['keyword3']=array( "value"=>$keynote3, "color"=>"#173177" );
        if ($keynote4)$message['data']['keyword4']=array( "value"=>$keynote4, "color"=>"#173177" );
        if ($keynote5)$message['data']['keyword5']=array( "value"=>$keynote5, "color"=>"#173177" );
        if ($remark)$message['data']['remark']=array( "value"=>$remark, "color"=>"#173177" );

         //处理发送对象
       $to_uids = is_array($to_uids) ? $to_uids : array($to_uids);
       foreach ($to_uids as $to_uid) {
           
            if (is_numeric($to_uid)) {
                $member=M('WechatMember')->find($to_uid);
            }else{
                $map['openid']=$to_uid;
                $member=M('WechatMember')->where($map)->find();
            }
           
            $options = D('Wechat/Wechat')->GetOptions($member['appid']);
            $weObj = new TPWechat( $options['base']);
            $ret=$weObj->checkAuth();
            if (!$ret) $error= (ErrCode::getErrText($weObj->errCode).'8888888'.$member['appid']);
            $message['touser'] = $member['openid'];
            $list=$weObj->sendTemplateMessage( $message); 
           
        }

       
        if (!$list) $error= (ErrCode::getErrText($weObj->errCode));
         
       

        return true;
    }


    /**
     * addMessageContent  添加消息内容到表
     * @param $from_uid 发送消息的用户
     * @param $title 消息的标题
     * @param $content 消息内容
     * @param $url 消息指向的路径，U函数的第一个参数
     * @param $url_args 消息链接的参数，U函数的第二个参数
     * @param $type 消息类型，0系统，1用户，2应用
     * @return mixed
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    private function addMessageContent($from_uid, $title, $content, $url, $url_args, $type)
    {
        $data_content['from_id'] = $from_uid;
        $data_content['title'] = $title;
        $data_content['content'] = $content;
        $data_content['url'] = $url;
        $data_content['args'] = empty($url_args) ? '':json_encode($url_args);
        $data_content['type'] = $type;
        $data_content['create_time'] = time();
        $data_content['status'] = 1;
        $message_id = D('message_content')->add($data_content);
        return $message_id;
    }


}