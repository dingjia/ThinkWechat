<?php
/**
 * 所属项目 充值流水.
 * 开发者: faith
 * 创建日期: 3/21/14
 * 创建时间: 10:17 AM
 * 版权所有 黄冈咸鱼计算机科技有限公司(www.ourstu.com)
 */

namespace Common\Model;


use Think\Model;

class SendNoticeModel extends Model
{

   
    /**流水
     * @param $uid
     * @return int|mixed
     */
    public function send($to,$notice)
    {
      

       if (!$to['aid'])$to['aid']=session('user_auth.aid');
       $shop=M('Member')->find($to['aid']);
      
       if(is_array($notice)){
        $data=$notice;
       }else{
        $data['wechat']=$notice;
        $data['sns']=$notice;
        $data['email']=$notice;
       }

       if ($to['userid']){
               $res=sendQmessage($to['userid'],$to['appid'],$data['wechat']);
                D('Common/Message')->sendMessageLog($to,$data,1);
               if (!$res)$res=1;
        }
               
       if ($to['openid']){
         $openids=explode(',',$to['openid']);
         foreach ($openids as $key => $openid) {
               $res=sendCustomMessage($openid,$data['wechat']);
                D('Common/Message')->sendMessageLog($to,$data,2);
          }
               if (!$res)$res=2;
        }

       if ($to['email']){
        $emails=explode(',',$to['email']);
        foreach ($emails as $key => $email) {
          $res=send_mail($email,$data['title'],$data['email']);
           D('Common/Message')->sendMessageLog($to,$data,3);
          
        }
        if ($res==1)$res=3;
        }

      if ($to['mobile']){
         $mobiles=explode(',',$to['mobile']);
          foreach ($mobiles as $key => $mobile) {
           $res=sendSMS($mobile,"【".$shop['nickname']."】".$data['sns']);
           D('Common/Message')->sendMessageLog($to,$data,4);     
          }
          if ($res==1)$res=4;
        }
        //记录
       
        return $res; 
    }


   
  

} 