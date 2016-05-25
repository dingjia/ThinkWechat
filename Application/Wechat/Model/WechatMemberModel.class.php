<?php
/**
 * 所属项目 OpenSNS
 * 开发者: 陈一枭
 * 创建日期: 2014-12-01
 * 创建时间: 15:55
 */

namespace Wechat\Model;


use Think\Model;

class WechatMemberModel extends Model
{
   

    /**下载微信粉丝
     * @param int  $id
     * @param bool $field
     * @return array
     * @auth 陈一枭
     */
    public function updateMember($member=array(),$aid='')
    {
        if (!$aid)$aid=session('user_auth.aid');
        if ($member['openid']){
          $map['openid']=$member['openid'];   
        }elseif($member['mobile']){
          $map['mobile']=$member['mobile'];   
        }
       
        // $map['aid']=$aid;
        $member['aid']=$aid;

        //数据处理
       

        $have = M("WechatMember")->where($map)->getField('id');

        if ($have){
            $this->where($map)->save($member);
            return "update";
        }else{
            $this->add($member);
            return "add";
        }
    }

    




    /**
     * 获取微信用户信息
     * @param  milit   $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function info($id, $field = true)
    {
        if (!$id) return false;
        /* 获取分类信息 */
        $map = array();
        if (is_numeric($id)){
            $map['id'] = $id;
        }else{
            $map['openid'] = $id;
        }

        $wechatMember=$this->field($field)->where($map)->find();
        return $wechatMember;
    }


      public function infoByMobile($mobile, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        $map['mobile'] = $mobile;
       
        $members=$this->field($field)->where($map)->select();
       
       foreach ($members as $key => $member) {
            $wechat=D('Wechat/Wechat')->info($member['appid']);
            $notice.="/:sun微信：".$wechat['name']." \n";
            $notice.="/:sun粉丝号：".$member['id']." \n";
            $notice.="/:sun会员号：". ($member['mobile']?$member['mobile']:'<a href="http://'.$_SERVER[HTTP_HOST].U('Wechat/index/index',array('openid'=>$member['openid'])).'">点击完善信息</a>')." \n";
            $notice.="/:sun会员号：". ($member['mobile']?$member['mobile']:'未完善信息')." \n";
            $notice.="/:sun持卡人：". ($member['remark']?$member['remark']:$member['nickname'])." \n";
            $notice.="/:sun账户余额：".($member['amount']?$member['amount']:0)."元 \n";
            $notice.="/:sun你的星星：".($member['score']?$member['score']:0)."颗 \n";
            $notice.="/:sun打印特权：".$member['balance']."张\n";
            $notice.=" ------------------\n";
            $notice.="/:,@f余额，星星，打印问题专属客服：17092610050 微信同号\n";
            $notice.="\n ------------------\n";
       }
       
        
        return $notice;
    }

     public function format_myinfo($wechatMember)
    {
        if (!$wechatMember) return false;
       
        $weprinterMember=D('Weprinter/WeprinterMember')->info($wechatMember['openid'],'balance');

        $options = D('Wechat/Wechat')->GetOptions($wechatMember['appid']);
       

        if ( $options['id_show']==1) {
          $notice.="/:sun粉丝号：".$wechatMember['id']." \n";
          $notice.="/:sun会员号：". ($wechatMember['mobile']?$wechatMember['mobile']:'<a href="http://'.$_SERVER[HTTP_HOST].U('Wechat/index/index',array('openid'=>$wechatMember['openid'])).'">点击完善信息</a>')." \n";
          $notice.="/:sun持卡人：". ($wechatMember['remark']?$wechatMember['remark']:$wechatMember['nickname'])." \n";
          $notice.="/:sun账户余额：".($wechatMember['amount']?$wechatMember['amount']:0)."元 \n";
          $notice.="/:sun你的星星：".($wechatMember['score']?$wechatMember['score']:0)."颗 \n";
          $notice.="/:sun打印特权：".$weprinterMember['balance']."张\n";
          $notice.="/:sun个人主页：".'<a href="http://'.$_SERVER[HTTP_HOST].U('Wechat/index/mySpace',array('openid'=>$wechatMember['openid'])).'">点击进入主页</a>'."\n";
          $notice.=" ------------------\n";
          $notice.="/:,@f余额，星星，打印充值等粉丝财产问题专属客服：17092610050 微信同号\n";
         
          $games=  D('News/NewsGameScore')->getGift($wechatMember['openid']);
          if ($games){
            $notice.="\n ----------------";
            $notice.="\n 游戏奖品：";
            $notice.="\n 1.每桌只能使用一个奖品";
            $notice.="\n 2.奖品当天有效";
            $notice.="\n 3.以下奖品你可以随便选择一个，领取后其他奖品随之失效";
            foreach ($games as $key => $game) {
             $notice.="\n /:gift".date('Y-m-d',$game['create_time']).'获得'.$game['gift'];
           }
        }

        }else{

          $notice.="/:li本微信不再支持会员储值，积分（积星），请关注本店大号，已有积分与储值请在2016年12月31号使用完，谢谢\n粉丝号：".$wechatMember['id']." \n";
          $notice.="/:sun账户余额：".($wechatMember['amount']?$wechatMember['amount']:0)."元 \n";
          $notice.="/:sun你的星星：".($wechatMember['score']?$wechatMember['score']:0)."颗 \n";
          $notice.="/:sun打印特权：".$weprinterMember['balance']."张\n";

        }


       
       
          
        return $notice;
    }


    


} 