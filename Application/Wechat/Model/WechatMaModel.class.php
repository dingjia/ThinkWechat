<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 下午1:22
 * @author 钱枪枪<8314028@qq.com>
 */

namespace Wechat\Model;

use Think\Model;


class WechatMaModel extends Model{

    
    public function scan($data)
    {
       
        $scene_id=str_replace("qrscene_","", $data['EventKey']);
        if ( $scene_id){    //如果带参二维码则记录轨迹
          $ma=M("WechatMa")->field('subscribe_rob,scan_rob,ma_type,userid,name')->where(array('appid'=>$data['appid'],'scene_id'=>$scene_id))->find();  
        }else{
          $scan_data['scene_id']=0;
        }
        //记录轨迹,谁扫描了哪个微信的哪个二维码
        $this->scanWay($data['FromUserName'],$data['member']['nickname'],$data['appid'],$scene_id,$ma['ma_type'],$ma['name']);
        
        if ($data['Event']=='subscribe'){
            $info = D('News/NewsRob')->getRob($ma['subscribe_rob']?$ma['subscribe_rob']:$data['options']['subscribe_rob']);
            //增加一个用户到这个二维码
            M("WechatMa")->where(array('appid'=>$data['appid'],'scene_id'=>$scene_id))->setInc('members');
           }
        if ($data['Event']=='SCAN'){
             if ($ma['scan_rob'])$info = D('News/NewsRob')->getRob($ma['scan_rob']);
             if ($data['options']['ma_rob']) $info_base = D('News/NewsRob')->getRob($data['options']['ma_rob']);
             if ($info_base['type']=='text' and $ma['ma_type']==1 ) $info['content']=($info['content']?$info['content']."\n":'').$info_base['content'];
        }

        

        //获取码的主人信息
        if ($ma['userid'])$qmember=D('Qwechat/QwechatMember')->infoByUserid($ma['userid']);
        $mySpace='<a href="http://'.$_SERVER[HTTP_HOST].U('Qwechat/index/myspace',array('userid'=>$ma['userid'])).'">'.$qmember['nickname'].'很高兴为您服务，您可以猛击这里了解我，打赏我！</a>';
        $info['content']=str_replace("{员工空间}", $mySpace, $info['content']);
        $info['content']=str_replace("{二维码名称}", $ma['name'],$info['content']);

        

        return $info;
    }

    public function scanWay($from,$nickname='',$appid,$scene_id=0,$ma_type=0,$ma='主码')
    {
         //每天只记录一次
         if ($this->getLastScan($from,array('appid'=>$appid,'scene_id'=>$scene_id))){
         
         }else{

         $wechat=D('Wechat/Wechat')->field('name')->find($appid);
         $scan_data['scene_id']=$scene_id;
         $scan_data['ma']=$ma?$ma:$wechat['name'];
         $scan_data['ma_type']=$ma_type?$ma_type:0;
         $scan_data['appid']=$appid;
         $scan_data['openid']=$from;
         $scan_data['nickname']=$nickname;
         $scan_data['create_time']=time();
         M("WechatMaMember")->add($scan_data);
         }
        
       
         M("WechatMa")->where(array('appid'=>$appid,'scene_id'=>$scene_id))->setInc('scan_times');
       
        
    }

     //今天某个用户扫描码情况
     public function getLastScan($openid,$map)
    {
      
        $map['openid']=$openid;
        $cur_date = strtotime(date('Y-m-d',time()));
        $map['create_time'] = array('EGT',$cur_date);
        $scene_id=M("WechatMaMember")->where($map)->order('create_time desc')->getField('scene_id');
       
        return $scene_id;
        
    }

     



}