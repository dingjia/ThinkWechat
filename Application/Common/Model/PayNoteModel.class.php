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

class PayNoteModel extends Model
{

   
    /**流水
     * @param $module,$um,$balance,$notice='',$disc='',$appid=0,$krob=array('1,2,3'),$openid='',$mobile=''
     * @return int|mixed
     */
    public function addNote($data)
    {
     
    if ($data['userid'])$qmember=D('Qwechat/QwechatMember')->infoByUserid($data['userid']);
    if ($data['openid'])$member=D('Wechat/WechatMember')->info($data['openid']);
        //查询上次操作这个顾客的员工，从而统计老顾客
       $last_sender= $this->field('userid,name')->where(array('openid'=>$data['openid'],'um'=>array('EGT',0),'module'=>$data['module'],'field'=>$data['field']))->order('create_time desc')->find();
       if ($last_sender)  {
        $data['last_sender']=$last_sender['userid'];
        $data['last_sender_name']=$last_sender['name'];
        $data['is_old']=1;
       }

       //构造通知
        $sys_notice=$qmember['name']."为".$member['nickname']."变更".$data['field_name'].$data['um'].',余额'.$data['balance'];
        $notice=$member['nickname']."您好，".$qmember['name'].'为您'.$data['field_name'].$data['um'].',累计'.$data['balance'].'请知晓';
        $data['notice']=D('Common/SendNotice')->send(array('openid'=>$member['openid']),$notice);
        $data['aid']=$member['aid'];
        $data['shopid']=$qmember['shopid'];
        $data['appid']=$member['appid'];

        $data['sys_id']=$member['id'];
        $data['nickname']=$member['nickname'];
        $data['mobile']=$member['mobile'];
        $data['name']=$qmember['name']?$qmember['name']:'系统';
        $data['weixinid']=$qmember['weixinid'];
        $data['create_time']=NOW_TIME;
        $data['desc']=$sys_notice;
        $res=$this->add($data);
       

    //发送变更通知
   
      
        //添加成功后发群公告
        // sendQmessage('@all',$data['appid'],$data['sys_notice']);
        return $res;
    }

     /**流水
     * @param $module,$um,$balance,$notice='',$disc='',$appid=0,$krob=array('1,2,3'),$openid='',$mobile=''
     * @return int|mixed
     */
    public function haveAddToday($map)
    {
     
      
       $cur_date = strtotime(date('Y-m-d',time()));
       $map['create_time'] = array('EGT',$cur_date);
       
       $res=$this->where($map)->order('create_time desc')->count('id');
     
       
       return $res;
    }



  

} 