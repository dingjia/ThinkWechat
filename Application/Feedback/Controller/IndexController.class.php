<?php
/**
 * Created by PhpStorm.
 * User: ludashi
 * Date: 2015/10/9
 * Time: 16:16
 */

namespace Feedback\Controller;

use Think\Controller;
use Common\Builder\AdminConfigBuilder;
use Common\Builder\AdminListBuilder;

class IndexController extends Controller
{
    protected $Model;
    public function _initialize(){

        $this->Model = D('FeedbackList');
        $this->rank=array(1=>'好评',2=>'中评',3=>'差评');
        $this->rankico=array(1=>"/:strong",2=>'/:,@f',3=>"/:weak");
      
    }

    public function rob(){
        $yesterday = date("Y-m-d",strtotime("-1 day"));
        $notice=$yesterday.'客情反馈报表';
        $where='DATEDIFF(NOW() , FROM_UNIXTIME(`create_time`)) = 1 ';
        $admins=M('Member')->field('aid,nickname')->where('aid>0 and aid=uid')->select();
        foreach ($admins as $key => $admin) {
          $shops = M('FeedbackList')->field('aid,shopid,count(id) as total,count(CASE WHEN product=1 THEN id END) AS product_best,count(CASE WHEN product=2 THEN id END) AS product_good,count(CASE WHEN product=3 THEN id END) AS product_bad,count(CASE WHEN service=1 THEN id END) AS service_best,count(CASE WHEN service=2 THEN id END) AS service_good,count(CASE WHEN service=3 THEN id END) AS service_bad' )->where('status>-1  and aid='.$admin['aid'].' and ' . $where )->group('shopid')-> select();
          if ($shops ){
           foreach ($shops as $key => $shop) {
            // if ($shop['total']<1 )continue;
            $shop_info=M('QwechatShop')->find($shop['shopid']);
            $notice.="\n".($shop_info['name']?$shop_info['name']:'未知分店').':'.$shop['total'];
            $notice.="\n 产品：\n".$this->rankico[1].$shop['product_best'].'/'.floor($shop['product_best']/$shop['total']*100)."%".$this->rankico[2].$shop['product_good'].'/'.floor($shop['product_good']/$shop['total']*100).'%'.$this->rankico[3].$shop['product_bad'].'-'.floor($shop['product_bad']/$shop['total']*100).'%';
            $notice.="\n 服务：\n".$this->rankico[1].$shop['service_best'].'/'.floor($shop['service_best']/$shop['total']*100)."%".$this->rankico[2].$shop['service_good'].'/'.floor($shop['service_good']/$shop['total']*100).'%'.$this->rankico[3].$shop['service_bad'].'-'.floor($shop['service_bad']/$shop['total']*100).'%';
            $notice.="\n --------------";
           }

           $my_rob=D('Feedback/WechatRob')->checkRob($admin['aid']);
           sendQmessage('@all',$my_rob,$notice);
           // $sys_notice.="\n".$notice;
           }

        }

          // $my_rob=D('Feedback/WechatRob')->checkRob(0);
          // sendQmessage('@all',$my_rob,$sys_notice);
       
           
    }

   
   
    public function index($openid='',$mobile=''){
        if ($openid)$member=D('Wechat/WechatMember')->info($openid); 
        if ($mobile)$member=D('Takeout/TakeoutMember')->info($mobile); 
      

        if (IS_POST) {
            
            $isEdit = $id ? true : false;
            $have=D('FeedbackList')->haveToday($openid);
            if ($have>0) $this->error('今天已经点评过哦，你的反馈我们已经接收，谢谢！');
            
            $_POST['appid']=$member['appid'];
            $_POST['aid']=$member['aid'];
            $_POST['nickname']=$member['nickname'];
            $_POST['mobile']=$mobile?$mobile:$member['mobile'];

            $res=$this->Model->editData();

            if ($res) {
                $member['shopid']=$_POST['shopid'];
                $member['product']=$_POST['product'];
                $member['service']=$_POST['service'];
                $member['target']=$_POST['target'];
                $member['content']=$_POST['content'];
                $member['res']=$res;
                $this->sendNotice($member);
                $map['aid']=$member['aid'];
                $map['name']='_FEEDBACK_SUCCESS_URL';
                $url =  D('Config')->where($map)->getField('value');
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), $url);
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').$this->Model->getError());
            }
           
             
        } else {
            $data=$this->findShopid($openid);   //智能检测对象
            $data['openid']=I('openid');

            $map['aid']=$member['aid'];
            $shops = M('QwechatShop')->field('id,name')->where($map)->select();
            $shops =array_column($shops, 'name', 'id');
           
            $builder = new AdminConfigBuilder();
            $builder
                // ->title( 'VIP顾客反馈')->suggest('你的反馈将会直达经理，THINKS')
                ->keyRadio('product', '产品评价', '产品评价', $this->rank)
                ->keyRadio('service', '服务评价', '服务评价', $this->rank)
                ->keyHidden('openid','微信号')->keySelect('shopid','餐厅','',$shops)->keyText('target','服务员','请输入桌号或者服务员姓名')->keyTextarea('content','建议/投诉')
                ->data($data)
                ->group('请告诉我谁服务了您！','openid,shopid,target')
                ->group('我们的菜品您还满意吗？','product')
                ->group('我们的服务您还满意吗？','service')
                
                ->group('我知道您肯定有很多话要对我们老板讲','content')
                ->buttonSubmit()->display();
         }

        
    }

    //自动检查会员所在分店
    public function findShopid($openid){
                 //自动判断分店和服务员
            $ma = M('WechatMaMember')->field('scene_id')->where(array('openid'=>$openid,'ma_type'=>1))->order('create_time desc')->find();
            if ($ma['scene_id']){
               $member_info= M('WechatMa')->field('userid,name')->where(array('scene_id'=>$ma['scene_id']))->find();
               $data['target']=$member_info['name'];
            }

            if ($member_info['userid']){
              $shop_info= D('Qwechat/QwechatMember')->infoByUserid($member_info['userid'],'shopid');
              $data['shopid']=$shop_info['shopid'];
            }
           
            return $data;
           

    }
   
    //发送反馈邀请
    public function sendNotice($member){
                $shop = M('QwechatShop')->field('id,name,department_id')->find($member['shopid']);
                $to['toparty']=$shop['department_id'];
                $to['toparty']=D('Qwechat/QwechatDepartment')->getChilds(array('aid'=>$member['aid']),$shop['department_id'],'|');
                $options = D('Wechat/Wechat')->GetOptions($member['appid']);
               

                //发送企业微信通知
                $notice='收到来自'.$shop['name'].'【'.$member['nickname']."】的点评：\n";
                $notice.="------------------\n";
                $notice.="对象：".$member['target']."\n";
                $notice.="服务：".$this->rank[$member['service']]." ; 产品：".$this->rank[$member['product']]."\n";
                $notice.="-----------------\n";
                $notice.="留言：".$member['content']."\n";
                $notice.="----------------\n";
                $notice.="回复：".$member['res']."@内容 \n";
                if ($member['id']) $notice.="顾客粉丝号".$member['id']."\n";
                
                $notice.="-----------------\n";


                $mas = M('WechatMaMember')->field('openid,nickname,ma,create_time')->where(array('openid'=>$member['openid']))->limit(3)->select();
                
                          
               foreach ($mas as $key => $ma) {
                    $notice.=date('m-d H:i',$ma['create_time']).'扫'.$ma['ma']."\n";
               }


               
                  $gift_auto = D('Feedback/FeedbackGift')->info($member['appid'],'gift_auto');

                  $data['type']="wxcard";
                  $data['card_id']=$gift_auto;
                  $res=sendCustomMessage($member['openid'],$data);
                  
                
                  $my_rob=D('Feedback/WechatRob')->checkRob($member['aid']);
                  sendQmessage($to,$my_rob,$notice);

        

    }


  
   


   //发送反馈邀请
    public function sendRob(){
       if (date("H")<19){
        $cur_date = time()-3600*10;
        }else{
        $cur_date = time()-3600*5;    
        }
       $map['create_time'] = array('EGT',$cur_date);
       $customers = M('WechatMaMember')->field('openid,nickname')->where($map)->group('openid')->select();
       
       if ($customers){
         foreach ($customers as $key => $customer) {
             $url='http://'.$_SERVER[HTTP_HOST].U('Feedback/Index/Index',array('openid'=>$customer['openid']));
             $info=$customer['nickname'].'/:love 感谢您的光临，请对我们的产品和服务进行评价，对于有效的评价，我们将送上一份/:gift，<a href="'.$url.'">点击这里反馈</a>';
              echo $back= sendCustomMessage($customer['openid'],$info);
           }
       }
       dump($customers);
       echo $info;
       die;
       $this->ajaxReturn($customer);
    }
    public function backlist($aid=2,$type=1){
      $map['aid']=$aid;
    if ($type==1){
       $map['product']=1;
       $map['service']=1;
     }else{
       $map['product|service']=array('EGT',2);
     }
     
     $data=D('FeedbackList')->where($map)->order('create_time desc')->limit(35)->select();
     foreach ($data as $key => &$value) {
       $value['member']=D('Wechat/WechatMember')->info($value['openid']);
       $value['shop']=D('Qwechat/QwechatShop')->info($value['shopid']);

     }
     $this->assign('list',$data);
  
    $this->display(T('Application://Feedback@Index/list'));
   }



    

    
}