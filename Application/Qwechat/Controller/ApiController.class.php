<?php
namespace Qwechat\Controller;
use Think\Controller;
use Qwechat\Sdk\TPWechat;
use Qwechat\Sdk\Wechat;
use Qwechat\Sdk\errCode;


// use Qwechat\RobController;


class ApiController extends Controller{


    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function index($appid=0){
        
        $options = D('Qwechat/Qwechat')->GetOptions($appid);
        
        $weObj = new TPWechat( $options['base']);
        $ret=$weObj->valid();
        $from = $weObj->getRev()->getRevFrom();
        $type = $weObj->getRev()->getRevType();
        $data = $weObj->getRev()->getRevData();
    
        //构造消息列
        $data['appid']=$appid;
        $data['aid']=$options['aid'];
        $notice=$data;
        
       
        //将必要的信息读取到data
        $map['userid']=$from;
        $map['aid']=$options['aid'];
        $data['member']=M('QwechatMember')->where($map)->find();
        $data['options']=$options;
        mylog($data);


        //记录消息
        $notice['name']= $data['member']['name'];
       
        $notice_res=D('QwechatNotice')->addNotice($notice);
        if (!$notice_res) {
            send_mail('','轻时光系统警报：企业号消息收到微信重试','轻时光系统警报：企业号消息收到微信重试'.print_r($data, 1));
            // $weObj->text('success')->reply();
        }

       

        switch($type) {
            case Wechat::MSGTYPE_TEXT:
                $rootBack=$this->checkRootKey($data);

                if ( $rootBack) {
                   $info['content']=$rootBack;
                   $info['type']="text";  
                 }else{
                    
                   $info = D('News/NewsRob')->getRob($data['Content']); 
                 
                 }

                if ($appid=20){
             
                 $kf=array(
                    'sender'=>array('type'=>'openid','id'=>'oq9Vct3mU7NchDnc6dwDdu7lz2Kk'),
                    'receiver'=>array('type'=>'kf','id'=>'1211'),
                    'msgtype'=>'text',
                    'text'=>array('content'=>$data['Content'])
                    );

                 $rs=$weObj->kfSend($kf);
                 if (!$rs) mylog($error= ErrCode::getErrText($weObj->errCode));
                 }

               

                break;
            case Wechat::MSGTYPE_IMAGE:
                    $info = D('News/NewsRob')->getRob($type);
                    break;
            case Wechat::MSGTYPE_VOICE:
                   //这里应该写专门的语音分析。
                    $category="music,weather,search";
                    $voice_data = $weObj->querySemantic($data['FromUserName'],$data['Recognition'], $category,'','','武汉市');
                    $back=$weObj->log($voice_data);
                    if ( $voice_data['type']=='weather'){
                        $info['type']='text';
                        $info['content']=$voice_data['semantic']['details']['datetime']['date_ori'].'de'.$voice_data['semantic']['details']['location']['loc_ori'].'谁说的清楚呢！';
                    }else{
                       $info = D('News/NewsRob')->getRob($data['Recognition']);  
                    }
                   
                    break;
            case Wechat::MSGTYPE_MUSIC:
                    $info = D('News/NewsRob')->getRob($type); 
                    break;
            case Wechat::MSGTYPE_SHORTVIDEO:
                    // $info = D('News/NewsRob')->getRob($type);
                    break;
            case Wechat::MSGTYPE_VIDEO:
                    $info = D('News/NewsRob')->getRob($type);
                    break;
            case Wechat::MSGTYPE_LOCATION:
                    $info = D('News/NewsRob')->getRob($type);
                    break;
            case Wechat::MSGTYPE_LINK:
                    $info = D('News/NewsRob')->getRob($type);
                    break;

            case Wechat::MSGTYPE_EVENT:
                    switch($data['Event']){
                        case Wechat::EVENT_SUBSCRIBE:
                            $info = D('News/NewsRob')->getRob($options['subscribe_rob']);
                            // $back=$weObj->log($info);
                            break;
                        case Wechat::EVENT_UNSUBSCRIBE:
                            $info =  D('News/NewsRob')->getAttention();
                            // $back=$weObj->log($info);
                            break;
                        case Wechat::EVENT_LOCATION:
                             $loc['Latitude']=$data['Latitude'];
                             $loc['Longitude']=$data['Longitude'];
                             $loc['Precision']=$data['Precision'];
                             
                             $my = D('Qwechat/QwechatMember')->where('userid='.$from)->save($loc);
                             $info['content']='';
                             $info['type']='text';
                             break;
                         case Wechat::EVENT_ENTER_AGENT:
                             $info = D('News/NewsRob')->getRob($options['enter_agent_rob'],$data);


                            
                            break;
                        case Wechat::EVENT_MENU_VIEW:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_MENU_CLICK:
                             $rootBack=$this->checkRootKey($data);
                             if ( $rootBack) {
                               $info['content']=$rootBack;
                               $info['type']="text";  
                             }else{
                               $info = D('News/NewsRob')->getRob($data['EventKey'],$data);   
                             }
                            break;
                        case Wechat::EVENT_MENU_SCAN_PUSH:
                             $scanInfo = $weObj->getRev()->getRevScanInfo();
                             $info['content']='扫码结果为：'.$scanInfo['ScanResult'];
                             $data['ScanResult']=$scanInfo['ScanResult'];
                             if ($options['scan_waitmsg_rob']) $info = D('News/NewsRob')->getRob($options['scan_waitmsg_rob'],$data); 
                            break;
                        case Wechat::EVENT_MENU_SCAN_WAITMSG:
                            $scanInfo = $weObj->getRev()->getRevScanInfo();
                             $info['content']='扫码结果为：'.$scanInfo['ScanResult'];
                             $data['ScanResult']=$scanInfo['ScanResult'];
                             if ($options['scan_waitmsg_rob']) $info = D('News/NewsRob')->getRob($options['scan_waitmsg_rob'],$data); 
                            break;
                        case Wechat::EVENT_MENU_PIC_SYS:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_MENU_PIC_PHOTO:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_MENU_PIC_WEIXIN:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_MENU_LOCATION:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_SEND_MASS:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_SEND_TEMPLATE:
                             $info = D('News/NewsRob')->getRob($data);
                            break;
                       
                        default:
                            $info=array('type'=>'text','content'=>'机器人对"'.$data['Content'].$data['Recognition'].$data['Latitude'].'"这个问题无能为力，不过代码和我爱着你！');
        


                       
                    }
                    break;
           
            default:
                    $info=array('type'=>'text','content'=>'机器人对"'.$data['Content'].$data['Recognition'].'"这个问题无能为力，不过代码和我爱着你！');
        }

        if(!$info){     //机器人无法回复，则转多客服
               
                    $info=array('type'=>'text','content'=>'机器人对"'.$data['Content'].$data['Recognition'].$data['Latitude'].'"这个问题无能为力，不过代码和我爱着你！');
                    if ($options['auto_rob']) $info = D('News/NewsRob')->getRob($options['auto_rob']);  
                
        }

        switch ($info['type']) {
            case Wechat::MSGTYPE_TEXT:
                $info['content']=rootKeyReplace($info['content'],$data);
                break;
            case Wechat::MSGTYPE_NEWS:
                foreach ($info['content'] as $key => $value) {
                     $info['content'][$key]['Title']=rootKeyReplace( $value['Title'],$data);
                     
               
                }
                break;
            
            default:
                # code...
                break;
        }
    

        $weObj->$info['type']($info['content'])->reply();

       
       
    }

   

     protected function checkRootKey($data){
       
                    $map['id']=array('in',$data['options']['wechat_rob']);
                    $rootkeys = D('News/NewsRob')->getRobs($map,'keywords,content');
                    //处理扫描事件
                    if($data['EventKey']){
                        $rob = M('NewsRob')->find($data['EventKey']);
                        $data['Content']=$rob['keywords'];
                    }
                   
                    if ( $rootkeys){
                      foreach ($rootkeys as  $rootkey) {

                        if (strstr($data['Content'],$rootkey['keywords'])){

                           $krob['rootkey']=$rootkey['keywords'];
                           $krob['before'] = substr($data['Content'],0,strrpos($data['Content'],$rootkey['keywords']));
                           $krob['back']=str_replace($krob['before'].$rootkey['keywords'],"",$data['Content']);
                           //去对应模块去处理数据 
                           $krob['member']=$data['member'];
                           $krob['aid']=$data['member']['can_aid']?$data['member']['can_aid']:$data['member']['aid'];
                           $krob['appid']=$data['appid'];
                           $krob['shopid']=$data['member']['shopid'];
                           $back=D($rootkey['content'].'/WechatRob')->qwechatRob($krob);  
                          
                        }
                      }
                    }
                    return $back;


                
    }




   


}