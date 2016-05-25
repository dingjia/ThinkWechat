<?php
/* 梦在想我(QQ345340585)整理*/
namespace News\Model;
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
       
        if($krob['rootkey']=="G")return $this->moveGift($krob);
       
        
    }

   
     public function moveGift($krob)
    {
        // if (isMobile($krob['before'])) return D('Wechat/WechatMember')->infoByMobile($krob['before']);
        $wechatMember=D('Wechat/WechatMember')->info($krob['before']);
       
        if (!$wechatMember){
            $res='不存在该用户，请仔细核对粉丝号是否正确'; 
        }else{
            if (!$krob['back']) return D('Wechat/WechatMember')->format_myinfo($wechatMember);
            $res=M('NewsGameScore')->where(array('openid'=>$wechatMember['openid']))->save(array('status'=>1,'update_time'=>time()));
             if ($res>0){
              $notice=$wechatMember['nickname']."您好，".$krob['member']['name'].'为您兑换了游戏奖品请知晓';
              $notice_res=D('Common/SendNotice')->send(array('openid'=>$wechatMember['openid']),$notice);

              $res='变更成功'.($notice_res?'，并成功发送'.$notice_res.'消息（'.$notice.'）给用户！':',试图发送消息给用户失败！'.$notice_res);
            }else{
              $res='变更失败，请联系管理员';  
            }
            
        }
        return $res;
    }

    
}
?>
