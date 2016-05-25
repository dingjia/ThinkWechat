<?php
namespace Qwechat\Controller;
use Think\Controller;
use Common\Builder\AdminConfigBuilder;
use Common\Builder\AdminListBuilder;


class IndexController extends Controller
{


     public function index($openid='',$service='',$product='',$shopid='',$target='',$content=''){
        

        
    }

     public function site($aid=0){
     $map['aid']=$aid;
     $site=M('QwechatSite')->where($map)->find();
   
      if(IS_POST){
         //记录
        $zhufu['aid']=$aid;
        $zhufu['name']=$_POST['name'];
        $zhufu['openid']=$_POST['openid'];
        $zhufu['content']=$_POST['content'];
        $zhufu['create_time']=time();
        $have=M('QwechatSiteZhufu')->where(array('aid'=>$aid,'name'=>$_POST['name'],'content'=>$_POST['content']))->find();
        if($have) $this->ajaxReturn(0);

        $zhufuid=M('MyfactoryMemberZhufu')->add($zhufu);
        
        $this->ajaxReturn(1);
       

      }else{
        
        $site['map']='http://apis.map.qq.com/uri/v1/routeplan?type=drive&fromcoord='. $member['coordinate'].'&to=湖北武汉'.($site['loction']?$site['loction']:$site['address']).'&tocoord='.$site['longitude'].','.$site['latitude'].'&policy=1&referer=myapp&key=4NVBZ-3SNKV-MCEPS-UFQ3Y-X624F-BOFXF';
        if ($site['pics'])$site['pics']=explode(',', $site['pics']);
       
        $this->assign('site',$site);
        $shops = M('QwechatShop')->where(array('aid'=>$aid,'status'=>array('EGT',0)))->select();
        $this->assign('shops',$shops);
       
        $appid=D('Wechat/Wechat')->mustOneWechat();
        $this->assign('appid',$appid);

        $zhufus = M('QwechatSiteZhufu')->where(array('aid'=>$aid,'status'=>1))->select();
        $this->assign('zhufus',$zhufus);

        $wx_show=array(
            'title'=>'发现一家好公司'.$site['name'],
            'desc'=>"这家公司不错，分享给你",
            'link'=>$site['show_link']?$site['show_link']: 'http://'.$_SERVER['HTTP_HOST'].U('Qwechat/index/site', array('aid' => $site['aid'])),
            'imgUrl'=>'http://'.$_SERVER['HTTP_HOST'].get_cover($site['logo'], 'path'),
        ); 

        //记录阅读数量
        M('QwechatSite')->where($map)->setInc('view');
       
        $this->assign('wx_show',$wx_show);
        $this->display('');
      }
    }


     public function editMy($userid=''){
         
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
          
            $res=D('QwechatMember')->editData();
           
            if ($res) {
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('editMy',array('userid'=>$userid)));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').$this->Model->getError());
            }
           
             
        } else {
           
            $member=D('Qwechat/QwechatMember')->infoByUserid($userid); 
         
            $map['aid']=$member['aid'];
            $shops = M('QwechatShop')->field('id,name')->where($map)->select();
            $shops =array_column($shops, 'name', 'id');
         
            $builder = new AdminConfigBuilder();
            $builder
                ->title( $member['name'].'的档案')
               
                ->keyHidden('id','编号') ->keyHidden('userid','编号') ->keyText('name','姓名') ->keySelect('gender','性别','',array (1=>'男',2=>'女'))->keyText('nickname','昵称') ->keyText('mobile','电话') ->keyText('qq','QQ')
                ->keySelect('shopid','分店','',$shops)->keyText('idcard','身份证','')->keyText('bankcard','银行卡')
                ->keyTime('birthday','生日','','date')->keySelect('calendar','生日历法','',array (1=>'阳历',2=>'农历'))
                ->data($member)
                ->group('基本信息','id,userid,name,nickname,gender,mobile,qq,shopid,idcard,bankcard,birthday,calendar')
                 
                ->buttonSubmit()->display();
         }

        
    }


     public function rob(){
        $today = date('Y-m-d', time());
        $notice=$today.'员工生日提醒';
        $today = strtotime($today); 


        $birthdays = D('Qwechat/QwechatMember')->getBirthday(30);
        foreach ($birthdays as $key => $birthday) {
        $notice.="\n".$birthday['name'].':'.date('Y-m-d',$birthday['birthday']).'-'.$birthday['calendar'];
        }
        dump($notice);
        sendQmessage('@all',13,$notice);        

           
    }

     //选择机器人
     public function mySpace($userid=0){
       
        $member =   D('Qwechat/QwechatMember')->infoByUserid($userid);
        
           if (!$member['avatar'])$member['avatar']='__NOTE_IMAGE__/avatar.jpg';
      

        // dump($member );
        $this->assign('member', $member);
        $this->display(T('Application://Qwechat@Index/myspace'));
    }








   

}