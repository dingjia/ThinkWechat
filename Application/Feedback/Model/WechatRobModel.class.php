<?php
/* 梦在想我(QQ345340585)整理*/
namespace Feedback\Model;
use Think\Model;

/**
 * 响应型接口基类
 */
class WechatRobModel extends Model
{
    protected $Model;
    public $data;//接收到的数据，类型为关联数组
    var $returnParameters;//返回参数，类型为关联数组
    
    function _initialize()
    {
        $this->platform=array(1=>"美团",2=>"饿了么",3=>"淘点点",4=>"百度",5=>"微信");
        $this->status=array(1=>"录单",2=>"出品",3=>"打包",4=>"派送",5=>"签收");
     }

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('status', '1', self::MODEL_INSERT),
    );

    /**
     * 将微信的请求xml转换成关联数组，以方便数据处理
     */
    function qwechatRob($krob)
    {
        
        if($krob['rootkey']=="@")return $this->chat($krob);
        
       
        
    }

     public function chat($krob)
    {   
       
        if (!$krob['back']) return $this->view($krob);
        if (strstr($krob['back'],'改成')){
            $krob['back']=str_replace('改成','',$krob['back']);

            return $this->update($krob);
        }
        if ($krob['member']['openid']) $this->touserid($krob);
        if ($krob['member']['userid']) $this->toopenid($krob);
        return $this->view($krob);
   
    }

     public function update($krob)
    {   
      
        //根据反馈号找到反馈信息
       
        switch ($krob['back']) {
             case '产品好评':
                 $data['product']=1;
                 break;
            case '产品中评':
                 $data['product']=2;
                 break;
            
             case '产品差评':
                 $data['product']=3;
                 break;
            
             case '服务好评':
                 $data['service']=1;
                 break;
             case '服务中评':
                 $data['service']=2;
                 break;
             case '服务差评':
                 $data['service']=3;
                 break;
             
             default:
                 return '格式错误：产品编号@改成产品好评';
                 break;
             }
         $info=D('Feedback/FeedbackList')->where(array('id'=>$krob['before']))->save($data);
         if ($info<1){
             return '修改失败，可能原因是反馈编号，或者格式错误！';
         }else{
             $res=sendQmessage('@all',$krob['appid'],$krob['member']['name'].'修改了'.$krob['before'].'号客情，更改为'.$krob['back']);
             
             return '修改成功！';
         }
       

        //检查机器人发送信息
         
       
    }

   //发送信息给顾客
   public function toopenid($krob)
    {   
        
        
        //根据反馈号找到反馈信息
        $info=D('Feedback/FeedbackList')->info($krob['before']);

        if (strstr($krob['back'],'卡券')){
          $notice_type='card';
          $gift = D('Feedback/FeedbackGift')->info($info['appid'],'gift'.str_replace('卡券','',$krob['back']));
          $cardname=D('Wechat/WechatCard')->where(array('id'=>$gift))->getField('title');
        }

         //记录
        $data['feedid']=$krob['before'];
        $data['userid']=$krob['member']['userid'];
        $data['name']=$krob['member']['nickname']?$krob['member']['nickname']:$krob['member']['name'];
        $data['openid']=$info['openid'];
        $data['nickname']=$info['nickname'];
        $data['content']= $notice_type=='card'?'我给'.$info['nickname'].'发送了一张'.$cardname:$krob['back'];
        $data['chat_type']=1;
        $data['create_time']=NOW_TIME;
       
        M('FeedbackChat')->add($data);

        //发送信息
        if ($notice_type=='card'){
          $data['type']="wxcard";
          $data['card_id']=$gift;
          sendCustomMessage($info['openid'],$data);
         
        }else{
          sendCustomMessage($info['openid'],$this->view($krob));
         
        }

         $shop = M('QwechatShop')->field('id,name,department_id')->find($info['shopid']);
         $to['toparty']=$shop['department_id'];
         // $to['toparty']=D('Qwechat/QwechatDepartment')->getChilds(array('aid'=>$info['aid']),$shop['department_id'],'|');
         $my_rob=$this->checkRob($krob['aid']);
         sendQmessage($to,$my_rob,$this->view($krob));    
       
     
    }

    public function touserid($krob)
    {   
       
        //根据反馈号找到反馈信息
        $info=D('Feedback/FeedbackList')->info($krob['before']);

         //记录
        $data['feedid']=$krob['before'];
        $data['userid']=0;
        $data['name']='@所有人';
        $data['openid']=$krob['member']['openid'];
        $data['nickname']=$krob['member']['nickname'];
        $data['content']=$krob['back'];
        $data['chat_type']=0;
        $data['create_time']=NOW_TIME;
       
        M('FeedbackChat')->add($data);

        //检查机器人发送信息
        $my_rob=$this->checkRob($krob['aid']);
        sendQmessage('@all',$my_rob,$this->view($krob));
        
    }

    //根据AID检查发送客情的机器人
     public function checkRob($aid)
    {   
       
        //发送信息
         $map['aid']=$aid;
         $map['name']='_FEEDBACK_QWECHAT_ROB';
         $my_rob =  D('Config')->where($map)->getField('value');
                  
         if (!$my_rob) {
             $map['aid']=0;
             $my_rob =  D('Config')->where($map)->getField('value');;
         }

         return $my_rob;

     
     
    }

     public function view($krob,$info)
    {
        $info?$info:$info=D('Feedback/FeedbackList')->info($krob['before']);
        $rank=array(1=>"/:strong",2=>'/:,@f',3=>"/:weak");
        $shop = M('QwechatShop')->field('id,name')->find($info['shopid']);
             
        $notice=$info['nickname']."(".date('m-d H:i:s',$info['create_time']).")对".$shop['name']."反馈：\n服务".$rank[$info['service']].'；产品'.$rank[$info['product']].'；对象:'.$info['target']."\n-----------------\n".$info['content'];
        $notice.="\n--------------------";

        $chats=M('FeedbackChat')->where(array('feedid'=>$krob['before']))->select();
        foreach ($chats as $key => $chat) {
          if ($chat['chat_type']==1) {
            $notice.="\n /::D".$chat['name']."(".date('m-d H:i:s',$chat['create_time'])."):\n".$chat['content'];
           }else{
            $notice.="\n /::*".$chat['nickname']."(".date('m-d H:i:s',$chat['create_time'])."):\n".$chat['content'];
           }
          
        }
        $notice.="\n /:love 回复方法：".$krob['before']."@内容";
        if ($member['mobile']) $notice.="顾客电话".$krob['member']['mobile']."\n";
        if ($member['mobile']) $notice.="顾客粉丝号".$krob['member']['id']."\n";
       
        return $notice;
    }


    public function myInfo($krob)
    {
        $wechatMember=D('Wechat/WechatMember')->info($krob['member']['openid']);
        return $wechatMember['notice'];
 
    }

    

     public function replyNotice($notice,$krob)
    {
       
         $notice = str_replace('{$品牌}', $krob, $notice);
         $notice = str_replace('{$派送人}',$krob['member']['name'], $notice);
         $notice = str_replace('{$派送人电话}',$krob['member']['mobile'], $notice);
         $notice = str_replace('{$网址}','关注微信', $notice);
      
        return $notice;
    }


    
}
?>
