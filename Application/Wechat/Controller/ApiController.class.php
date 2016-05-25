<?php
namespace Wechat\Controller;
use Think\Controller;
use Wechat\Sdk\TPWechat;
use Wechat\Sdk\Wechat;
use Wechat\Sdk\errCode;





class ApiController extends Controller{


    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function index($appid=0){
      
        $options = D('Wechat/Wechat')->GetOptions($appid);
       

        $weObj = new Wechat( $options['base']);
        $ret=$weObj->valid();

        $from = $weObj->getRev()->getRevFrom();
        $type = $weObj->getRev()->getRevType();
        $data = $weObj->getRev()->getRevData();
      

       
        $data['content']=trim($data['content']);  //去掉空格
        $data['appid']=$appid;
        $data['aid']=$options['aid'];
        $notice=$data;
       
        //获取当前用户信息，如果存在就下拉并且存储
        $map['openid']=$from;
        $map['appid']=$appid;
        
        $data['member']=M('WechatMember')->where($map)->find();
       
        $data['options']=$options;
        if (!$data['member']){
            $member=$weObj->getUserInfo($from); 
            $member["appid"]=$options['id'];
            $member["wechat"]=$options['name'];
            $member["aid"]=$options['aid'];
            $back= M('WechatMember')->add($member);
            $data['member']=$member;
        }
       
        
        //记录消息
        $notice['nickname']= $data['member']['nickname'];
        $notice_res=D('WechatNotice')->addNotice($notice);
       
        if (!$notice_res) {
            send_mail('','轻时光系统警报：服务号消息收到微信重试','轻时光系统警报：服务号消息收到微信重试'.print_r($data, 1));
            $weObj->text('success')->reply();
        }

       
       
       
      
        switch($type) {
            case Wechat::MSGTYPE_TEXT:
 
                 $rootBack=$this->checkRootKey($data);
                
                  if ( $rootBack) {
                    if (!is_array( $rootBack)){
                    $info['content']=$rootBack;
                    $info['type']="text";  
                    }else{
                    $info =$rootBack;  
                    }

                  
                 }else{
                   $info = D('News/NewsRob')->getRob($data['Content'],$data);   
                 }

                 mylog($info);
             
                break;
            case Wechat::MSGTYPE_IMAGE:
                   
                    $pic = $weObj->getMedia($data['MediaId']);
                    $data['pic']=$pic;
                    $back=D('Weprinter/WeprinterOrder')->index($data); 
                  
                    if (!is_array( $back)){
                    $info['content']=$back?$back:'系统正在维护，请稍后';
                    $info['type']="text";
                    }else{
                    $info =$back;  
                    }

                    break;
            case Wechat::MSGTYPE_VOICE:
                   //这里应该写专门的语音分析。
                    // $category="music,weather,search";
                    // $voice_data = $weObj->querySemantic($data['FromUserName'],$data['Recognition'], $category,'','','武汉市');
                    // $back=$weObj->log($voice_data);
                    // if ( $voice_data['type']=='weather'){
                    //     $info['type']='text';
                    //     $info['content']=$voice_data['semantic']['details']['datetime']['date_ori'].'de'.$voice_data['semantic']['details']['location']['loc_ori'].'谁说的清楚呢！';
                    // }else{
                    //    $info = D('News/NewsRob')->getRob($data['Recognition']);  
                    // }
                   
                    break;
            case Wechat::MSGTYPE_MUSIC:
                    $info = D('News/NewsRob')->getRob($type); 
                    break;
            // case Wechat::MSGTYPE_SHORTVIDEO:
            //         // $info = D('News/NewsRob')->getRob($type);
            //         break;
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
                            $info=D('Wechat/WechatMa')->scan($data);
                            $res=M('WechatMember')->where(array('openid'=>$from))->save(array('subscribe'=>1,'subscribe_time'=>time()));
                            
                             break;
                        case Wechat::EVENT_UNSUBSCRIBE:
                            $res=M('WechatMember')->where(array('openid'=>$from))->save(array('subscribe'=>0,'unsubscribe_time'=>time()));
                            
                            break;
                        case Wechat::EVENT_SCAN:
                             $info=D('Wechat/WechatMa')->scan($data);
                             break;
                        case Wechat::EVENT_LOCATION:
                             $info = D('News/NewsRob')->getRob($type);
                            break;
                        case Wechat::EVENT_MENU_CLICK:

                            $rootBack=$this->checkRootKey($data);
                            if ( $rootBack) {
                                if (!is_array( $rootBack)){
                                $info['content']=$rootBack;
                                $info['type']="text";  
                                }else{
                                $info =$rootBack;  
                                } 
                             }else{
                               $info = D('News/NewsRob')->getRob($data['EventKey']);   
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
                        case Wechat::EVENT_KF_SEESION_CREATE:
                             $info = D('News/NewsRob')->getRob($data);
                            break;
                        case Wechat::EVENT_KF_SEESION_CLOSE:
                             $info = D('News/NewsRob')->getRob($data['EventKey']);
                            break;
                        case Wechat::EVENT_KF_SEESION_SWITCH:
                             $info = D('News/NewsRob')->getRob($data['EventKey']);
                            break;
                        case Wechat::EVENT_CARD_PASS:
                             $info = D('News/NewsRob')->getRob($data['EventKey']);
                            break;
                        case Wechat::EVENT_CARD_NOTPASS:
                             $info = D('News/NewsRob')->getRob($data['EventKey']);
                            break;
                        case Wechat::EVENT_CARD_USER_GET:
                             $info = array('type'=>'text','content'=>'恭喜你领取了卡券');
                            break;
                        case Wechat::EVENT_CARD_USER_DEL:
                             $info = array('type'=>'text','content'=>'我们的卡券你不喜欢吗？');
                            break;
                        case Wechat::WifiConnected :
                             $info = D('News/NewsRob')->getRob($options['wifi_rob']);
                            break;
                        case Wechat::ShakearoundUserShake :
                             $info = D('News/NewsRob')->getRob($data['EventKey']);
                            break;
                        default:
                            $info=array('type'=>'text','content'=>'机器人对"'.$data['Content'].$data['Recognition'].'"这个问题无能为力，不过代码和我爱着你！');
            
                    }
                    break;
           
            default:
                    $info=array('type'=>'text','content'=>'机器人对"'.$data['Content'].$data['Recognition'].'"这个问题无能为力，不过代码和我爱着你！');
        }

        if(!$info['type'])$info['type']='text';
        if(!$info['content']){     //机器人无法回复，则转多客服
                if(isset($config['WX_KEFU'])){
                    $type = Wechat::MSG_TYPE_KEFU;
                    $info=array('type'=>$type,'content'=>'');
                } else {
                    $info=array('type'=>'text','content'=>'机器人对"'.$data['Content'].$data['Recognition'].'"这个问题无能为力，不过代码和我爱着你！');
                    if ($options['auto_rob']) $info = D('News/NewsRob')->getRob($options['auto_rob']);  
                }
        }

        switch ($info['type']) {
            case Wechat::MSGTYPE_TEXT:
                if ($options['tail'])$info['content'].="\n/:li".$options['tail'];
                $info['content']=rootKeyReplace($info['content'],$data);
                break;
            case Wechat::MSGTYPE_NEWS:
                
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
                           $krob['appid']=$data['appid'];
                           $krob['aid']=$data['options']['aid'];
                          
                           $back=D($rootkey['content'].'/WechatRob')->qwechatRob($krob);  //D('Takeout/TakeoutOrder')->
                          
                        }
                      }
                    }
                    return $back;



                
    }
   

  

}