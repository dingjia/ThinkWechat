<?php
/* 梦在想我(QQ345340585)整理*/
namespace Qwechat\Model;
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
        
    
        if($krob['rootkey']=="QM")return $this->amount($krob);
        if($krob['rootkey']=="QmyInfo")return $this->myInfo($krob);
       
       
    }

     

     public function amount($krob)
    {
      
        $wechatMember=D('Qwechat/QwechatMember')->info($krob['before']);

        if (!$krob['back']) return $wechatMember['notice'];
        if (!$wechatMember){
            $res='不存在该用户，请仔细核对粉丝号是否正确'; 
        }else{
            $res=M('QwechatMember')->where(array('userid'=>$wechatMember['userid']))->setInc('amount',$krob['back']);
             if ($res>0){
              $notice=$wechatMember['name']."您好，".$krob['member']['name'].'为您更改金额'.$krob['back'].'元，余额'.($wechatMember['amount']+$krob['back']).'请知晓';
              $sys_notice=$krob['member']['name']."为".$wechatMember['name']."变更余额".$krob['back'].'元,余额'.($wechatMember['amount']+$krob['back']);
              $notice_res=D('Common/SendNotice')->send(array('userid'=>$wechatMember['userid'],'appid'=>$krob['appid'],'aid'=>$wechatMember['aid']),$notice,'qwechat');
              
               $note=array(
                'module'=>'Qwechat',
                'um'=>$krob['back'],
                'balance'=>$wechatMember['amount']+$krob['back'],
                'appid'=>$krob['appid'],
                'userid'=>$krob['member']['userid'],
                'userid'=>$wechatMember['userid'],
                'notice'=>$notice_res,
                'sys_notice'=>"【财产变动通知】".$sys_notice,
                'disc'=>$sys_notice,
                'field'=>'amount'

                );
              $note_res=D('Common/PayNote')->addNote($note); 
              sendQmessage('@all',$krob['appid'],$sys_notice);
              $res='变更成功'.($note_res>0?'，记录本次操作成功！':',记录本次操作失败！').($notice_res?'，并成功发送'.$notice_res.'消息（'.$notice.'）给用户！':',试图发送消息给用户失败！'.$notice_res);
            }else{
              $res='变更失败，请联系管理员';  
            }
            
        }
        return $res;
    }


    public function myInfo($krob)
    {
        $wechatMember=D('Qwechat/QwechatMember')->infoByUserid($krob['member']['userid']);
        return $wechatMember['notice'];
 
    }

    

    


    
}
?>
