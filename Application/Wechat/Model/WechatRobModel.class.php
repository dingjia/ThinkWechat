<?php
/* 梦在想我(QQ345340585)整理*/
namespace Wechat\Model;
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
        //101充值1 
        if($krob['rootkey']=="S")return $this->score($krob);
        if($krob['rootkey']=="M")return $this->amount($krob);
        if($krob['rootkey']=="C")return $this->chat($krob);
        if($krob['rootkey']=="myInfo")return $this->myInfo($krob);
        if($krob['rootkey']=="getCards")return $this->getCards($krob);
        
    }

     public function score($krob)
    {   
        //电话号码查询资料
        if (isMobile($krob['before'])) return D('Wechat/WechatMember')->infoByMobile($krob['before']);
        if ($krob['back']>1) return '【错误提示】每个顾客每天只能积2次星星！';
        
        $wechatMember=D('Wechat/WechatMember')->info($krob['before']);
        if (!$wechatMember) {
        
          return $krob['before']."号顾客变更成功\n";
        }
        if (!$krob['back']) return D('Wechat/WechatMember')->format_myinfo($wechatMember);
        //检查今天是否积分
           $map['openid']=$wechatMember['openid'];
           $map['field']='score';
           $map['module']='Wechat';
           $map['um']=array('EGT',1);
           $haveAddToday=D('Common/PayNote')->haveAddToday($map);

           if ($haveAddToday>1 and $krob['back']>0) {
              return $wechatMember['id'].'号顾客'.$wechatMember['nickname']."今日已经积星，不能重复积星！本次异常操作已经记录";
           }else{
              if ($krob['back']<0 and $wechatMember['score']+$krob['back']<0) return '该用户的积分不够消费,积分数是'.$wechatMember['score'];
              $res=M('WechatMember')->where(array('id'=>$wechatMember['id']))->setInc('score',$krob['back']?$krob['back']:1);
           }

           //记录流水,发送通知
           if ($res>0){
                 $log=array(
                'aid'=>$wechatMember['aid'],
                'shopid'=>$krob['member']['shopid'],
                'appid'=>$wechatMember['appid'],
                'sys_id'=>$wechatMember['id'],
                'openid'=>$wechatMember['openid'],
                'mobile'=>$wechatMember['mobile'],
                'nickname'=>$wechatMember['nickname'],
                'userid'=>$krob['member']['userid'],
                'weixinid'=>$krob['member']['weixinid'],
                'name'=>$krob['member']['name'],
                'um'=>$krob['back'],
                'balance'=>$wechatMember['score']+$krob['back'],
                'create_time'=>time()
                 );

                 //查询上次操作这个顾客的员工，从而统计老顾客
                $last_sender= M('WechatScoreLog')->field('userid,name')->where(array('openid'=>$wechatMember['openid'],'um'=>array('EGT',0)))->order('create_time desc')->find();
                if ($last_sender)  {
                $log['last_sender']=$last_sender['userid'];
                $log['last_sender_name']=$last_sender['name'];
                $log['is_old']=1;
                }

               //构造通知
                $notice=$wechatMember['nickname']."您好，".$krob['member']['name'].'为您积分'.$log['um'].',结余'.$log['balance'].'请知晓';
                $log['notice']=D('Common/SendNotice')->send(array('openid'=>$wechatMember['openid'],'aid'=>$wechatMember['aid']),$notice);
                $log['disc']=$notice;
                
                $note_res=M('WechatScoreLog')->add($log); 
                $error.=$wechatMember['id']."号顾客".$wechatMember['nickname']."资料变更成功".($note_res>0?'，记录本次操作成功！':',记录本次操作失败！')."\n";
            }else{
                $error.=$wechatMember['id']."号顾客积分变更失败\n" ;
            }

         return $error;
     
    }


     public function amount($krob)
    {
       

        if (isMobile($krob['before'])) return D('Wechat/WechatMember')->infoByMobile($krob['before']);
        $wechatMember=D('Wechat/WechatMember')->info($krob['before']);
        if (!$wechatMember) return $wechatMember['id']."号顾客不存在！请检查\n";
        if (!$krob['back']) return D('Wechat/WechatMember')->format_myinfo($wechatMember);

        $res=M('WechatMember')->where(array('id'=>$wechatMember['id']))->setInc('amount',$krob['back']);
        if ($res>0){
           $log=array(
                'aid'=>$wechatMember['aid'],
                'shopid'=>$krob['member']['shopid'],
                'appid'=>$wechatMember['appid'],
                'sys_id'=>$wechatMember['id'],
                'openid'=>$wechatMember['openid'],
                'mobile'=>$wechatMember['mobile'],
                'nickname'=>$wechatMember['nickname'],
                'userid'=>$krob['member']['userid'],
                'weixinid'=>$krob['member']['weixinid'],
                'name'=>$krob['member']['name'],
                'um'=>$krob['back'],
                'balance'=>$wechatMember['amount']+$krob['back'],
                'create_time'=>time()
                 );
            //构造通知
                $notice=$wechatMember['nickname']."您好，".$krob['member']['name'].'变更你的储值'.$log['um'].',结余'.$log['balance'].'请知晓';

                if($wechatMember['openid'])$log['notice']=D('Common/SendNotice')->send(array('openid'=>$wechatMember['openid'],'mobile'=>$wechatMember['mobile'],'aid'=>$wechatMember['aid']),$notice,$wechatMember['aid']);
      
                
                $note_res=M('WechatAmountLog')->add($log); 

        
           
              $sys_notice=$krob['member']['name']."为".$wechatMember['nickname']."变更储值".$log['um'].',余额'.$log['balance'];
              sendQmessage('@all',$krob['appid'],$sys_notice);
              $error.=$wechatMember['id']."号顾客".$wechatMember['nickname']."资料变更成功".($note_res>0?'，记录本次操作成功！':',记录本次操作失败！')."\n";
        }else{
          $error='变更失败，请联系管理员';  
        }
            
        
        return $error;
    }


    public function myInfo($krob)
    {
        $wechatMember=D('Wechat/WechatMember')->info($krob['member']['openid']);
        return D('Wechat/WechatMember')->format_myinfo($wechatMember);
 
    }

     public function chat($krob)
    {
        //判断对象是否存在
        $member=D('Wechat/WechatMember')->info($krob['before']);
        if(!$member)return '粉丝号不存在，请仔细核对！';
        if (strstr($krob['back'],'卡券')){
          $card_id=str_replace('卡券','',$krob['back']);
          $card=D('Wechat/WechatCard')->find($card_id);
          if(!$card)return '卡券不存在！';
          $data['type']="wxcard";
          $data['card_id']=$card['id'];
          $res=sendCustomMessage($member['openid'],$data);
          if(!$res) sendQmessage('@all',$krob['appid'],'[财务通知]'.$krob['member']['name'].'给'.$member['nickname'].'成功发送'.$card['title']);
        }else{
          $res=sendCustomMessage($member['openid'],$krob['back']);  
        }

        return $res?$res:'发送成功！';
 
    }

     public function getCards($krob)
    {
        $map['aid']=$krob['aid'];
        $map['status']=array('neq','CARD_STATUS_DELETE');

        $cards=D('Wechat/WechatCard')->getCards($map);
        foreach ($cards as $key => $card) {
         $notice.=$card['card_id'].'='.$card['title']."\n";
        }
        return $notice;
 
    }

    
    
}
?>
