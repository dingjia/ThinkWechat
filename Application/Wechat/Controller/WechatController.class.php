<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */

namespace Admin\Controller;
use Wechat\Sdk\TPWechat;
use Wechat\Sdk\Wechat;
use Wechat\Sdk\errCode;

use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminSortBuilder;
use Admin\Builder\AdminTreeListBuilder;


class WechatController extends AdminController
{

    function _initialize()
    {
        parent::_initialize();
        $this->defaut_rob=array(
               'rselfmenu_0_0'=>'扫码带提示',
               'rselfmenu_0_1'=>'扫码推事件',
               'rselfmenu_1_0'=>'系统拍照发图',
               'rselfmenu_1_1'=>'拍照或者相册发图',
               'rselfmenu_1_2'=>'微信相册发图',
               'rselfmenu_2_0'=>'发送位置',
            );
        $this->defaut_rob_type=array(
               'rselfmenu_0_0'=>'scancode_waitmsg',
               'rselfmenu_0_1'=>'scancode_push',
               'rselfmenu_1_0'=>'pic_sysphoto',
               'rselfmenu_1_1'=>'pic_photo_or_album',
               'rselfmenu_1_2'=>'pic_weixin',
               'rselfmenu_2_0'=>'location_select',
            );
        $this->ma_type=array('未知码类型','员工码','营销码','功能码','固定场景码','微信打印码');
        $this->pay_type=array('命令支付','微信支付');
        $this->notice=array(1=>'企业消息',2=>'微信通知',3=>'邮件',4=>'短信');
        
    }


    
    public function index()
    {
         if(IS_POST){
               $count_day=I('post.count_day', S('COUNT_DAY'),'intval',7);
                $count_before_day=I('post.count_before_day', S('COUNT_BEFORE_DAY'),'intval',7);
               $shopid=I('post.shopid', S('SHOP_ID'),'intval',1); 
               
               S('COUNT_DAY',$count_day);
               S('COUNT_BEFORE_DAY',$count_before_day);
               S('SHOP_ID',$shopid);

                if($res===false){
                    $this->error(L('_ERROR_SETTING_').L('_PERIOD_'));
                }else{
                   S('DB_CONFIG_DATA',null);
                   $this->success(L('_SUCCESS_SETTING_').L('_PERIOD_'),'refresh');
                }

            }else{
                $this->meta_title = L('_INDEX_MANAGE_');
                $today = date('Y-m-d', time());
                $today = strtotime($today)- S('COUNT_BEFORE_DAY')*86400; 
               
                $count_day=S('COUNT_DAY');
                $count_before_day=S('COUNT_BEFORE_DAY');
                $shopid=S('SHOP_ID');

                $count['count_day']=$count_day;
                $count['count_before_day']=$count_before_day;
                $count['shopid']=$shopid;

                $map['aid'] = array('eq',session('user_auth.aid'));
                $shops =  D('Qwechat/QwechatShop')->getData('','id,name');
                $this->assign('shops', $shops);
               

                for ($i = $count_day; $i--; $i >= 0) {
                    $day = $today - $i * 86400;
                    $day_after = $today - ($i - 1) * 86400;
                    //派送者
                   
                    $week_map=array('Mon'=>L('_MON_'),'Tue'=>L('_TUES_'),'Wed'=>L('_WEDNES_'),'Thu'=>L('_THURS_'),'Fri'=>L('_FRI_'),'Sat'=>'<strong>'.L('_SATUR_').'</strong>','Sun'=>'<strong>'.L('_SUN_').'</strong>');
                    $week[] = date('m月d日 ', $day). $week_map[date('D',$day)];
                    // $thisDay = M('PayNote')->field('sum(price) as price,count(id) as total,sum(is_old) as olds,sum(wast)/count(id)/60 as wast,sum(distance)/count(id) as distance' )->where()-> find();
                    
                    $member=M('WechatMember')->field('count(id) as total' )->where('aid= '.session('user_auth.aid').' and subscribe_time >=' . $day . ' and subscribe_time < ' . $day_after)->find();
                    $thisDay['member']=$member['total'];

                    $amount=M('WechatAmountLog')->field('sum(CASE WHEN um>0 THEN   um   END   ) AS add_total,sum(CASE WHEN um<0 THEN   um   END   ) AS down_total' )->where('shopid='.$shopid.' and aid= '.session('user_auth.aid').'   and  create_time >=' . $day . ' and create_time < ' . $day_after)->find();
                    $thisDay['amount']=$amount['add_total'].$amount['down_total'].'='.($amount['add_total']+$amount['down_total']);

                    $score=M('WechatScoreLog')->field('sum(CASE WHEN um>0 THEN   um   END   ) AS add_total,sum(CASE WHEN um<0 THEN   um   END   ) AS down_total,sum(is_old) as olds,count(id) as total' )->where('shopid='.$shopid.' and aid= '.session('user_auth.aid').'  and  create_time >=' . $day . ' and create_time < ' . $day_after)->find();
                    $thisDay['score']=$score['add_total'].$score['down_total'].'='.($score['add_total']+$score['down_total']);

                    $thisDay['olds']=$score['olds'].'('.floor($score['olds']/$score['total']*100).'%)';
                    
                    $eachDayData[] = floatval($member['total']);
                    $eachDay[] = $thisDay;
                    
                }

                $eachDay=array_combine($week,$eachDay);
                $this->assign('week', $week);
                $this->assign('eachDay', $eachDay);


                $begin = $today -( $count_day-1) * 86400;
                $end = $today+86400 ;
                $member=M('WechatMember')->field('count(id) as total' )->where('aid= '.session('user_auth.aid').' and subscribe_time >=' . $begin . ' and subscribe_time < ' . $end)->find();
               

                $total=M('WechatScoreLog')->field('sum(CASE WHEN um>0 THEN   um   END   ) AS add_total,sum(CASE WHEN um<0 THEN   um   END   ) AS down_total,sum(is_old) as olds,count(id) as total' )->where('shopid='.$shopid.' and aid= '.session('user_auth.aid').'  and create_time >=' . $begin . ' and create_time < ' . $end)->find();
                $total['member']=$member['total'];
                $total['score']=$total['add_total'].$total['down_total'].'='.($total['add_total']+$total['down_total']);
                $total['olds']=$total['olds'].'('.floor($total['olds']/$total['total']*100).'%)';
              
               
                //派送者
                $count['senders'] = M('WechatScoreLog')->field('count(id) as total,sum(is_old) as olds,name,userid,weixinid')->where('shopid='.$shopid.' and aid= '.session('user_auth.aid').'   and create_time >=' . $begin . ' and create_time < ' . $end)->group('userid')->order('olds desc')->select();
                  
                foreach ($count['senders'] as $key => $sender) {
                $my_old= M('WechatScoreLog')->where('last_sender='.$sender['userid'].' and create_time >=' . $begin . ' and create_time < ' . $end)->count();
                $my_today_score= M('WechatScoreLog')->where('um>0 and userid='.$sender['userid'].' and create_time >=' . $today )->count();
                $my_today_old= M('WechatScoreLog')->where('um>0  and last_sender='.$sender['userid'].' and create_time >=' . $today )->count();
               
                $count['senders'][$key]['olds']=$my_old;
                $count['senders'][$key]['my_today_score']=$my_today_score;
                $count['senders'][$key]['my_today_old']=$my_today_old;
                }
               
                $count['last_day']['days'] = json_encode($week);
                $count['last_day']['data'] = json_encode($eachDayData);
                // dump($count);exit;

               $this->assign('count', $count);
               $this->assign('total', $total);
               $this->display( T('Application://Wechat@Wechat/index') );
            }
    }

   

    public function config()
    {
        $admin_config = new AdminConfigBuilder();
        if (IS_POST) {
            S('Wechat_recommand_Wechat', null);
            S('Wechat_hot_Wechat', null);
            S('Wechat_suggestion_posts', null);
        }
        $data = $admin_config->handleConfig();

        if (!$data) {
            $data['LIMIT_IMAGE'] = 10;
            $data['Wechat_BLOCK_SIZE'] = 4;
            $data['CACHE_TIME'] = 300;
        }

        $admin_config->title(L('_Wechat_SETTINGS_'))
            ->data($data)
            ->keyInteger('LIMIT_IMAGE', L('_POST_PARSE_NUMBER_'), L('_POST_PARSE_NUMBER_VICE_'))
            //->keyInteger('Wechat_BLOCK_SIZE', '微信微信列表微信所占尺寸', '默认为4,，值可填1到12,共12块，数值代表每个微信所占块数，一行放3个微信则为4，一行放4个微信则为3')
            ->keyInteger('CACHE_TIME', L('_BLOCK_DATA_CACHE_TIME_'), L('_BLOCK_DATA_CACHE_TIME_DEFAULT_'))
            ->keyText('SUGGESTION_POSTS', L('_HOME_RECOMMEND_POST_'))
            ->keyText('HOT_Wechat', L('_BLOCK_HOT_'), L('_DIVIDE_COMMA_'))->keyDefault('HOT_Wechat', '1,2,3')
            ->keyText('RECOMMAND_Wechat', L('_BLOCK_RECOMMEND_'), L('_DIVIDE_COMMA_'))->keyDefault('RECOMMAND_Wechat', '1,2,3')
            ->keyInteger('FORM_POST_SHOW_NUM_INDEX', L('_Wechat_HOME_PER_PAGE_COUNT_'), '')->keyDefault('FORM_POST_SHOW_NUM_INDEX', '5')
            ->keyInteger('FORM_POST_SHOW_NUM_PAGE', L('_PER_PAGE_COUNT_'), L('_PER_PAGE_COUNT_VICE_') . L('_COMMA_'))->keyDefault('FORM_POST_SHOW_NUM_PAGE', '10')
            ->keyText('Wechat_SHOW_TITLE', L('_TITLE_NAME_'), L('_HOME_BLOCK_TITLE_'))->keyDefault('Wechat_SHOW_TITLE', L('_BLOCK_Wechat_'))
            ->keyText('Wechat_SHOW', L('_BLOCK_SHOW_'), L('_BLOCK_SHOW_TIP_'))
            ->keyText('Wechat_SHOW_CACHE_TIME', L('_CACHE_TIME_'), L('_BLOCK_DATA_CACHE_TIME_DEFAULT_'))->keyDefault('Wechat_SHOW_CACHE_TIME', '600')
            ->keyText('Wechat_POST_SHOW_TITLE', L('_TITLE_NAME_'), L('_HOME_BLOCK_TITLE_'))->keyDefault('Wechat_POST_SHOW_TITLE', L('_POST_HOT_'))
            ->keyText('Wechat_POST_SHOW_NUM', L('_POST_SHOWS_'))->keyDefault('Wechat_POST_SHOW_NUM', 5)
            ->keyRadio('Wechat_POST_ORDER', L('_POST_SORT_FIELD_'), '', array('update_time' => L('_UPDATE_TIME_'), 'last_reply_time' => L('_LAST_REPLY_TIME_'), 'view_count' => L('_VIEWS_'), 'reply_count' => L('_REPLIES_')))->keyDefault('Wechat_POST_ORDER', 'last_reply_time')
            ->keyRadio('Wechat_POST_TYPE', L('_POST_SORT_MODE_'), '', array('asc' => L('_ASC_'), 'desc' => L('_DESC_')))->keyDefault('Wechat_POST_TYPE', 'desc')
            ->keyText('Wechat_POST_CACHE_TIME', L('_BLOCK_SHOW_'), L('_BLOCK_SHOW_TIP_'))->keyDefault('Wechat_POST_CACHE_TIME', '600')
            ->group(L('_SETTINGS_BASIC_'), 'LIMIT_IMAGE,Wechat_BLOCK_SIZE,CACHE_TIME,SUGGESTION_POSTS,HOT_Wechat,RECOMMAND_Wechat,FORM_POST_SHOW_NUM_INDEX,FORM_POST_SHOW_NUM_PAGE')
            ->group(L('_HOME_DISPLAY_BOARD_SETTING_'), 'Wechat_SHOW_TITLE,Wechat_SHOW,Wechat_SHOW_CACHE_TIME')
            ->group(L('_HOME_DISPLAY_POST_SETTINGS_'), 'Wechat_POST_SHOW_TITLE,Wechat_POST_SHOW_NUM,Wechat_POST_ORDER,Wechat_POST_TYPE,NEWS_SHOW_CACHE_TIME');

        $admin_config->buttonSubmit('', L('_SAVE_'))->display();
    }

    public function Wechat($page = 1, $r = 20)
    {
        //读取数据
        $map['aid']= session('user_auth.aid');
        $map['status'] = array('GT', -1);
        $model = M('Wechat');
        $list = $model->where($map)->page($page, $r)->order('wechat_type desc')->select();
        $totalCount = $model->where($map)->count();
        $type=array(0=>'订阅号',1=>'认证订阅号',2=>'服务号',3=>'认证服务号');
        foreach ($list as &$v) {
            $v['members'] = D('WechatMember')->where(array('appid' => $v['id']))->count();
            $v['wechat_type'] =$type[$v['wechat_type']];
            $v['url']=U('Wechat/Wechat/api',array('appid'=>$v['id']));
        }

        //显示页面
        $builder = new AdminListBuilder();
        $builder
            ->title(L('_BLOCK_MANAGE_'))
            ->buttonNew(U('Wechat/editwechat'))
            ->setStatusUrl(U('Wechat/setWechatStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            
            ->buttonSort(U('Wechat/sortWechat'))
            ->keyId()->keyLink('name', L('_TITLE_'), 'editwechat?id=###')
            ->keyText('wechat_type', '类型') 
            ->keyText('members', '粉丝')->keyStatus()
            ->keyDoActionEdit('menu?id=###','菜单')
            ->keyDoActionEdit('getUserList?id=###','拉粉丝')
            ->keyDoActionEdit('getCards?id=###','拉门店卡券')
            ->keyDoActionEdit('editwechat?id=###')

            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


    public function getUserList($id)
    {
       
        ignore_user_abort (true);
        set_time_limit(0);
        $builder = new AdminConfigBuilder();
            $builder->title( '与微信同步粉丝' )
           ->keyProgressbar()->display();

      
            $options =D('Wechat/Wechat')->GetOptions($id);

                 $weObj = new TPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getUserList();                                  //先下拉一次
                 if (!$list){
                     $cachename='wechat_access_token'.$options["appid"];
                     $ret=$weObj->removeCache( $cachename);
                      $ret=$weObj->checkAuth();
                      $ret=$weObj->log($ret);
                     $list=$weObj->getUserList();  
                     // if(!$list)$this->error(ErrCode::getErrText($weObj->errCode));
                 }
                show_msg('初始化成功，即将下拉'.$list['total'].'粉丝，请不要中断');
                 if($list['total']>10000){                                         
                    $times= ceil($list['total']/$list['count']);                  //如果粉丝超过1W需要下拉多次，并且雅俗到list 里面
                     for ($i=1 ;$i<$times;$i++){
                         $list_next=$weObj->getUserList($list['next_openid']);
                         $list['data']['openid']=array_merge($list['data']['openid'],$list_next['data']['openid']);
                         $list['next_openid']=$list_next['next_openid'];
                    }
                 }
                 $members=array_chunk($list['data']['openid'],100);
                 $total=$list['total'];
              
             // $weObj->log($members);
            //处理数据。开始循环处理用户数据
         
                 $i=0;
                 foreach ($members as $k=> $v){
                
                     
                      $member=$weObj->batchgetUserInfo($v);   //拉取每一个用户的基本信息
                     
                      if (!$member) {   //如果获取失败重新登录
                        
                          $cachename='wechat_access_token'.$options["appid"];
                          $ret=$weObj->removeCache( $cachename);
                          $ret=$weObj->checkAuth();
                          $member=$weObj->batchgetUserInfo($v);   //再次获取这个用户的基本信息，重试一次后还是失败则报错
                          $ret=$weObj->log($ret);
                          // if (!$member)$this->error("我们在拉取用户信息的时候发送错误：".ErrCode::getErrText($weObj->errCode). $ret);
                         }
                        foreach ($member['user_info_list'] as $key=>  $value) {
                            $i+=1;  

                            $value["appid"]=$options['id'];
                            $value["wechat"]=$options['name'];
                            $back= D('Wechat/WechatMember')->updateMember($value,$options['aid']);
                            $msg=$i.'/'.$total.'：'.($back=="add"?"[添加]":"[更新]").' 粉丝'.$value['nickname'].'</br>';
                            show_msg($msg,'',floor($i/$total*100));
                            ob_flush(); 
                            flush(); 
                           
                        }
                        
                } 

             
              ob_flush(); 
              flush();   
        

        show_msg('1秒后将跳转到部门列表！');
        echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('member')."'},1000)</script>";
    

    }

   

    public function WechatTrash($page = 1, $r = 20, $model = '')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        //读取回收站中的数据
        $map = array('status' => '-1');
        $model = M('Wechat');
        $list = $model->where($map)->page($page, $r)->order('id asc')->select();
        $totalCount = $model->where($map)->count();

        //显示页面

        $builder
            ->title(L('_BLOCK_TRASH_'))
            ->setStatusUrl(U('Wechat/setWechatStatus'))->buttonRestore()->buttonClear('Wechat')
            ->keyId()->keyLink('title', L('_TITLE_'), 'Wechat/post?Wechat_id=###')
            ->keyCreateTime()->keyText('post_count', L('_POST_NUMBER_'))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


    public function sortWechat()
    {
        //读取贴吧列表
        $list = M('Wechat')->where(array('status' => array('EGT', 0)))->order('sort asc')->select();

        //显示页面
        $builder = new AdminSortBuilder();
        $builder->title(L('_POST_BAR_SORT_'))
            ->data($list)
            ->buttonSubmit(U('doSortWechat'))->buttonBack()
            ->display();
    }

    public function setWechatStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('Wechat', $ids, $status);
    }

    public function doSortWechat($ids)
    {
        $builder = new AdminSortBuilder();
        $builder->doSort('Wechat', $ids);
        D('Wechat/Wechat')->cleanAllWechatsCache();
    }

    public function editwechat($id = null, $name = '', $create_time =0, $status = 1,  $logo = 0)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            if (D('Wechat/Wechat')->editData()) {
                //缓存
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('wechat'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').D('Wechat/Wechat')->getError());
            }
        
        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $Wechat = M('Wechat')->where(array('id' => $id))->find();
                $Wechat['url']='http://'.$_SERVER['HTTP_HOST']. U('wechat/api/index',array('appid'=>$id));
            } else {
                $Wechat = array('create_time' => time());
            }

            // $wechat_rob = D('News/NewsRob')->field('id,title as value')->where('type=0 and wechat_type=2')->select();
            // $wechat_rob =array_column($wechat_rob, 'value', 'id');

            $robs = D('News/NewsRob')->getRobs("aid=0 or aid=".session('user_auth.aid'),'title,id');
            $robs =array_column($robs, 'title', 'id');
            $robs=$this->defaut_rob+$robs;

            $wechat_rob = D('News/NewsRob')->field('id,title as value')->where('type=0 and status>=0 and (wechat_type=2 or wechat_type=0) ')->select();
            $wechat_rob =array_column($wechat_rob, 'value', 'id');
            
            $cards = D('Wechat/WechatCard')->getCards("appid=".$id." and  aid=".session('user_auth.aid'),'title,id');
            $cards =array_column($cards, 'title', 'id');
           
            
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '添加微信' : '修改微信')
                ->data($Wechat)
                ->keyId()
                ->keyText('name','微信名称','','',61)->keySelect('wechat_type', '应用类型', '', array(0=>'订阅号',1=>'认证订阅号',2=>'服务号',3=>'认证服务号'),62)
                ->keyText('description','应用简介', '')
                ->keySingleImage('logo', '应用图标','',61)
                ->keySingleImage('ma','二维码', '',62)
                ->keyText('url', 'url', '系统自动生成，填写到微信后台')
                ->keyText('token', 'Token', '')
                ->keyText('encodingaeskey','EncodingAESKey', '')
                ->keyText('appid', 'AppID', '')
                ->keyText('appsecret', 'AppSecret', '')
                ->keyText('mchid', '商户号MCHID', '')
                ->keyText('pay_key', '支付秘钥KEY', '')
                ->keySingleFile('apiclient_cert', 'apiclient_cert.pem', '',61)
                ->keySingleFile('apiclient_key', 'apiclient_key.pem', '',62)
                
                ->keyChosenOne('subscribe_rob', '关注回复', '', $robs)
                ->keyChosenOne('auto_rob', '自动回复', '', $robs)
                ->keyChosenOne('wifi_rob', 'WIFI回复', '', $robs)
                ->keyChosenOne('ma_rob', '二维码回复', '', $robs)
                ->keyChosenOne('scan_waitmsg_rob', '扫码带提示', '使用微信扫一扫带提示返回的机器人', $robs)
                ->keyText('feedback_card_hand', '客情手动发券','使用英文,号分隔')
                ->keySingleImage('business_card', '员工码背景')
                ->keyBool('id_show', '显示粉丝号','当企业有多个微信的时候，建议以一个微信为主')

                
                

                ->keyText('tail', '小尾巴', '文字回复后面统一的小尾巴')
                ->keyCheckBox('wechat_rob', '应用命令', '可以在此应用中处理的命令',$wechat_rob)
                ->keyStatus()

                ->group('基本信息', 'id,name,wechat_type,logo,ma,description')
                ->group('微信配置', 'url,token,encodingaeskey,appid,appsecret')
                ->group('支付配置', 'mchid,pay_key,apiclient_cert,apiclient_key')
                ->group('自动回复', 'subscribe_rob,auto_rob,wifi_rob,ma_rob,scan_waitmsg_rob,tail')
                ->group('营销相关', 'id_show,business_card,feedback_card_hand')
                ->group('特殊应用', 'wechat_rob')
           
                ->buttonSubmit(U('editwechat'))->buttonBack()
                ->display();

              
        }

    }


      public function menu($id = 0){
        //显示页面
        $builder = new AdminTreeListBuilder();
        $map['status']=array('gt', -1);
        $map['appid']=$id;

        $tree = D('Wechat/WechatMenu')->getTree(0, 'id,title,sort,linkurl,pid,status',$map);
        $builder->title('微信菜单')
            ->suggest('菜单增加/修改后需要点击“发布到微信”按钮才能生效')
            ->buttonNew(U('addMenu',array('appid'=>$id)))
            ->button('发布到微信', array('href'=>U('sendMenuToWechat',array('appid'=>$id))))
            ->setModel('Menu')
            ->setLevel(1)
            ->data($tree)
            ->display();

//        $this->display('Wechat@Admin/menu');
    }

    public function addMenu($id = 0, $pid ='',$appid=0)
    {

        $title=$id?"编辑":"新增";
        $menuMod = D('Wechat/WechatMenu');
        if (IS_POST) {
            //数据处理
            // if($_POST['linkurl'] && (strpos($_POST['linkurl'], 'http://') == false) )  $this->error('网址必须包含http:// ,或者不填网址，使用机器人回答');
            if ($menuMod->editData()) {
                $this->success($title.'成功。', U('menu',array('id'=>$_POST['appid'])));
            } else {
                $this->error($title.'失败,或者没有做任何修改!'.$menuMod->getError());
            }
        } else {
            $builder = new AdminConfigBuilder();

            if ($id != 0) {
                $data = $menuMod->find($id);
                $appid=$data['appid'];
            } else {
                $father_category_pid=$menuMod->where(array('id'=>$pid))->getField('pid');
                if($father_category_pid!=0){
                    $this->error('菜单不能超过二级！');
                }
                $data['appid']=$appid;
            }
            if ($pid>0){
                $menu = $menuMod->find($pid);
                $appid=$data['appid']=$menu['appid'];
            }
          
            $categorys = $menuMod->where(array('pid'=>0,'appid'=>$appid,'status'=>array('egt',0)))->select();
//机器人
          
          
            $robs = D('News/NewsRob')->getRobs("(aid=0 or aid=".session('user_auth.aid').") ",'title,id');
           
            $robs =array_column($robs, 'title', 'id');
            $robs=$this->defaut_rob+$robs;
           
            $opt = array();
            foreach ($categorys as $category) {
                $opt[$category['id']] = $category['title'];
            }
            $builder->title($title.'微信菜单')
                ->data($data)
                ->keyId()->keyReadOnly('appid','所属微信')->keyText('title', '标题','','',61)
                ->keySelect('pid', '父分类', '', array('0' => '顶级菜单') + $opt,62)->keyDefault('pid',$pid)
                ->keyText('linkurl','网址')->keyChosenOne('key', '机器人', '', $robs)
                ->keyStatus()->keyDefault('status',1)
                ->buttonSubmit(U('addMenu'))->buttonBack()
                ->display();
        }

    }

    
    public function setMenuStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('WechatMenu', $ids, $status);
    }


    public function sendmenutoWechat($appid=0){
        $options =D('Wechat/Wechat')->GetOptions($appid);
        $weObj = new TPWechat( $options['base']);
        $ret=$weObj->checkAuth();
        if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
       
       
        $tree = D('Wechat/WechatMenu')->getTree(0, 'id,title,linkurl,key,pid',array('appid'=>$appid));
       
        $menu = null;
        foreach($tree as $k => $v){
            if(isset($v['_']) && is_array($v['_'])){
                $menu[$k] = array(
                    'name' => $v['title'],
                    'sub_button' => array()
                );
                foreach($v['_'] as $k2 => $v2){
                    $menu[$k]['sub_button'][$k2] = $this->formatMenu($v2);
                }
            } else {
                $menu[$k] = $this->formatMenu($v);
            }
        }
        $send['button']=$menu;
        $rs=$weObj->createMenu($send);
        if($rs) $this->success('发布成功');
        $this->error(ErrCode::getErrText($weObj->errCode));
    }

    protected function formatMenu($menu){
        if($menu['linkurl']){
            return array(
                'type' => 'view',
                'name' => $menu['title'],
                'url' => $menu['linkurl'],
            );
        } else {
            if (array_key_exists($menu['key'],$this->defaut_rob_type)){
                $type= $this->defaut_rob_type[$menu['key']];
            }else{
                 $type= 'click';
            }
            return array(
                'type' => $type,
                'name' => $menu['title'],
                'key' => $menu['key'] ? $menu['key'] : $menu['title'],
            );
        }
    }


    public function member($page = 1, $appid = null, $r = 20, $nickname = '', $mobile = '',$sex ='')
    {
        
        //充值
        if (I('action')=='cfo'){
        $amount=I('amount');
        $score=I('score');
        $disc=I('disc');
        $id=I('id');
        $member  =  M('WechatMember')->where(array('id' => $id))->find();

        if ($amount){
           $res=M('WechatMember')->where(array('id'=>$member['id']))->setInc('amount',$amount);
           $log=array(
                'aid'=>$member['aid'],
                'shopid'=>session('user_auth.shopid'),
                'appid'=>$member['appid'],
                'sys_id'=>$member['id'],
                'openid'=>$member['openid'],
                'mobile'=>$member['mobile'],
                'nickname'=>$member['nickname'],
                'name'=>session('user_auth.username'),
                'disc'=>$disc,
                'balance'=>$member['amount']+$amount,
                'um'=>$amount,
                'create_time'=>time()
                 );
            //构造通知
                $notice=$member['nickname']."您好，".session('user_auth.username').'变更你的储值'.$log['um'].',结余'.$log['balance'].'请知晓';

                if($member['openid'])$log['notice']=D('Common/SendNotice')->send(array('openid'=>$member['openid'],'mobile'=>$member['mobile'],'aid'=>$member['aid']),$notice,$member['aid']);
      
                
                $note_res=M('WechatAmountLog')->add($log); 
               
        }

        if ($score){
           $res=M('WechatMember')->where(array('id'=>$member['id']))->setInc('score',$score);
            $log=array(
                'aid'=>$member['aid'],
                'shopid'=>session('user_auth.shopid'),
                'appid'=>$member['appid'],
                'sys_id'=>$member['id'],
                'openid'=>$member['openid'],
                'mobile'=>$member['mobile'],
                'nickname'=>$member['nickname'],
                'name'=>session('user_auth.username'),
                'um'=>$score,
                'disc'=>$disc,
                'balance'=>$member['score']+$score,
                'create_time'=>time()
                 );
            //构造通知
                $notice=$member['nickname']."您好，".session('user_auth.username').'变更你的积分'.$log['um'].',结余'.$log['balance'].'请知晓';

                if($member['openid'])$log['notice']=D('Common/SendNotice')->send(array('openid'=>$member['openid'],'mobile'=>$member['mobile'],'aid'=>$member['aid']),$notice,$member['aid']);
      
                $note_res=M('WechatScoreLog')->add($log); 
               
        }
      
        
       
      }



       
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname|openid|id'] = array('like', '%' . $nickname . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($appid) $map['appid'] = $appid;
        if ($sex) $map['sex'] = $sex;
        $model = M('WechatMember');
        $order=I('order') ?I('order').' desc':'score desc';
        $list = $model->where($map)->order($order)->page($page, $r)->select();
       
       
        $totalCount = $model->where($map)->count();
       

        foreach ($list as &$v) {
         $v['sex'] = ($v['sex'] == 1) ? '男' : '女';  
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        if(I('action')=='cfo')$builder->tip('操作成功');
        $builder->title('粉丝管理' . $WechatTitle)
            ->setStatusUrl(U('Wechat/setMemberStatus'))->buttonEnable()->buttonDisable()->buttonDelete()->buttonNew(U('Wechat/editmember'))
            ->buttonModalPopup(U('Wechat/sendMessage'), array('user_group' => $aUserGroup, 'role' => $aRole), L('_SEND_A_MESSAGE_'), array('data-title' => L('_MASS_MESSAGE_'), 'target-form' => 'ids', 'can_null' => 'true'))
            // ->buttonNew(U('getoldsys'),'老顾客转移')
            ->setSelectPostUrl(U('Admin/Wechat/member'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$wechats))
            ->select('','sex','select','','','',array(array('id'=>0,'value'=>'选择性别'),array('id'=>1,'value'=>'男'),array('id'=>2,'value'=>'女')))
            ->keyId()->keyLink('nickname', L('_TITLE_'), 'Wechat/editmember?id=###')->key('wechat','所属微信', 'text')
            ->key('mobile','手机', 'text')->key('sex','性别', 'text')->keyLink('amount','余额', 'Wechat/amountlog?sys_id=###')->keyLink('score','积分', 'Wechat/scoreLog?sys_id=###')
            ->keyCreateTime()->keyTime('subscribe_time', '关注')->keyStatus()->keyDoActionEdit('editMember?id=###')
            ->keyDoActionModalPopup('cfo?id=###','财务','财务',array('data-title'=>'财务信息'))
            ->setSearchPostUrl(U('Admin/Wechat/member'))->search('昵称', 'nickname')->search('手机', 'mobile')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function cfo($id='')
    {
        $member  =  M('WechatMember')->where(array('id' => $id))->find();
        if (!$member)$this->error('没有找到顾客');
        $this->assign('member',$member);
       

        $this->display( T('Application://Wechat@Admin/cfo') );
     
        
    }

     public function getoldsys()
    {
        
        ignore_user_abort (true);
        set_time_limit(0);

        $builder = new AdminConfigBuilder();
            $builder->title( '转移老系统顾客' )
           ->keyProgressbar()->display();
        
      
        //同步旧数据,需要的时候开启
         //可以先删除DELETE from `ocenter_old_order` where phone is null;
        
        

        $olds = M('WechatOldAdmin')->field('open_id,phone,amount,star,nickname,remark')->where('(open_id<>"" or phone<>"" ) and  (phone<>""  or star>0 or amount>0) and sid=263')->limit(50000,20000)->select();
        
        $total=count($olds);
    
        foreach ($olds as $key => $old) {
            $member['nickname']=$old['nickname']?$old['nickname']:$old['remark'];
            $member['openid']=$old['open_id'];
            $member['mobile']=$old['phone'];
            $member['amount']=$old['amount'];
            $member['score']=$old['star'];
            

            
            $back=D('Wechat/WechatMember')->updateMember($member,2);

            $msg=($key+1).'/'.$total.'：'.($back=="add"?"[添加]":"[更新]").' 老系统顾客'.$old['nickname'].$old['open_id'].$old['phone'].'***'.$old['amount'].'****'.$old['star'].'</br>';
            show_msg($msg,'',floor(($key+1)/$total));
            ob_flush(); 
            flush(); 

        }
             
            
           
           
          
        show_msg('1秒后将跳转到顾客列表！');
        echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('member')."'},1000)</script>";
    
       
    }


     


     public function sendMessage()
    {

        if (IS_POST) {
            $aUids = I('post.uids');
            $aUserGroup = I('post.user_group');
            $aUserRole = I('post.user_role');
            $aTitle = I('post.title', '', 'text');
            $aContent = I('post.content', '', 'html');
            $aUrl = I('post.url', '', 'text');
            $aArgs = I('post.args', '', 'text');
            $args = array();
            // 转换成数组
            if ($aArgs) {
                $array = explode('/', $aArgs);
                while (count($array) > 0) {
                    $args[array_shift($array)] = array_shift($array);
                }
            }

            if (empty($aTitle)) {
                $this->error(L('_PLEASE_ENTER_THE_MESSAGE_HEADER_'));
            }
            if (empty($aContent)) {
                $this->error(L('_PLEASE_ENTER_THE_MESSAGE_CONTENT_'));
            }
            // 以用户组或身份发送消息
        
           D('Wechat/WechatMessage')->sendCustomMessage($aUids, $aContent, $rob);
            $result['status'] = 1;
            $result['info'] = L('_SEND_');
            $this->ajaxReturn($result);
        } else {
            $aUids = I('get.ids');
            $aUserGroup = I('get.user_group', 0, 'intval');
            $aRole = I('get.role', 0, 'intval');
            if (empty($aUids)) {
                $role = D('Role')->selectByMap(array('status' => 1));
                $roles = array();
                foreach ($role as $key => $v) {
                    array_push($roles, array('id' => $v['id'], 'value' => $v['title']));
                }
                $group = D('AuthGroup')->getGroups();
                $groups = array();
                foreach ($group as $key => $v) {
                    array_push($groups, array('id' => $v['id'], 'value' => $v['title']));
                }
                $this->assign('groups', $groups);
                $this->assign('roles', $roles);
                $this->assign('aUserGroup', $aUserGroup);
                $this->assign('aRole', $aRole);
            } else {
                $uids = implode(',',$aUids);
                $users = D('WechatMember')->where(array('id'=>array('in',$aUids)))->field('openid,nickname')->select();
                $this->assign('users', $users);
                $this->assign('uids', $uids);
            }
         
            $this->display(T('Application://Wechat@Wechat/sendmessage'));
        }
    }

     public function WechatPay($page = 1, $r = 20,$shopid='',$appid='',$out_trade_no='',$status='')
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname|sys_id|openid'] = array('like', '%' . $nickname . '%');
        }
        if ($shopid) $map['shopid'] = $shopid;
        if ($appid) $map['appid'] = $appid;
       
        if ($out_trade_no)   $map['out_trade_no'] = array('like', '%' . $out_trade_no . '%');
        if ($status) $map['status'] = $status;
      


       
        $list = M('WechatPay')->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = M('WechatPay')->where($map)->count();


        foreach ($list as &$v) {
        $v['status'] = ($v['status']==1?'支付成功':'未支付');   
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('充值查询')

            ->buttonNew(U('Wechat/editOrder'))
            ->setStatusUrl(U('Wechat/setWayStatus'))->buttonDelete()
            ->setSelectPostUrl(U('Admin/Wechat/WechatPay'))
            ->select('','shopid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部分店')),$shops))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部微信')),$wechats))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'未支付'),array('id'=>1,'value'=>'支付成功')))
            
            ->setSearchPostUrl(U('Admin/Wechat/WechatPay'))->search('顾客', 'nickname')->search('单号', 'out_trade_no')
            ->keyId()->keyText('nickname', '顾客')->keyText('out_trade_no', '内部流水')->keyText('product', '产品')->keyText('um', '数量')->keyText('price', '价格')
            ->keyText('status','状态')->keyText('disc', '备注')
            ->keyCreateTime()->keyDoActionEdit('editWay?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


    public function amountLog($page = 1, $r = 20,$nickname='',$shopid='',$appid='',$sys_id='',$pay_type=0,$update_type=0)
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname|openid'] = array('like', '%' . $nickname . '%');
        }
        if ($shopid) $map['shopid'] = $shopid;
        if ($appid) $map['appid'] = $appid;
        if ($sys_id) $map['sys_id'] = $sys_id;
       
        if ($pay_type) $map['pay_type'] = $pay_type;
        if ($field) $map['field'] = $field;
        if ($update_type==1) $map['um'] = array('EGT', 0);
        if ($update_type==2) $map['um'] = array('ELT', 0);


       
        $list = M('WechatAmountLog')->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = M('WechatAmountLog')->where($map)->count();


        foreach ($list as &$v) {
         $v['notice'] = $this->notice[ $v['notice']];
         $v['pay_type'] = $this->pay_type[ $v['pay_type']];
         $v['field'] = $this->field[ $v['field']]; 
         $v['is_error'] = ($v['is_error']==2?'出错':'正常');   
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('充值查询')

            ->buttonNew(U('Wechat/editOrder'))
            ->setStatusUrl(U('Wechat/setWayStatus'))->buttonDelete()
            ->setSelectPostUrl(U('Admin/Wechat/amountlog'))
            ->select('','shopid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部分店')),$shops))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部微信')),$wechats))
            ->select('','update_type','select','','','',array(array('id'=>0,'value'=>'变更类型'),array('id'=>1,'value'=>'充值'),array('id'=>2,'value'=>'消费')))
            ->select('','pay_type','select','','','',array(array('id'=>'','value'=>'支付方式'),array('id'=>0,'value'=>'命令支付'),array('id'=>1,'value'=>'微信支付')))
           
            ->setSearchPostUrl(U('Admin/Wechat/amountLog'))->search('顾客', 'nickname')->search('粉丝号', 'sys_id')
            ->keyText('nickname', '顾客')->keyText('sys_id', '粉丝号')->keyText('um', '数量')->keyText('balance', '结余')
            ->keyText('disc', '备注')->keyText('notice', '通知') ->keyText('name', '操作者')
            ->keyCreateTime()->keyDoActionEdit('editWay?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

     public function scoreLog($page = 1, $r = 20,$nickname='',$shopid='',$appid='',$sys_id='',$pay_type=0,$update_type=0)
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
       
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname|openid'] = array('like', '%' . $nickname . '%');
        }
        if ($shopid) $map['shopid'] = $shopid;
        if ($appid) $map['appid'] = $appid;
        if ($sys_id) $map['sys_id'] = $sys_id;
        
        if ($pay_type) $map['pay_type'] = $pay_type;
        if ($field) $map['field'] = $field;
        if ($update_type==1) $map['um'] = array('EGT', 0);
        if ($update_type==2) $map['um'] = array('ELT', 0);


       
        $list = M('WechatScoreLog')->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = M('WechatScoreLog')->where($map)->count();


        foreach ($list as &$v) {
         $v['notice'] = $this->notice[ $v['notice']];
         $v['pay_type'] = $this->pay_type[ $v['pay_type']];
         $v['field'] = $this->field[ $v['field']]; 
         $v['is_error'] = ($v['is_error']==2?'出错':'正常');   
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('充值查询')

            ->buttonNew(U('Wechat/editOrder'))
            ->setStatusUrl(U('Wechat/setWayStatus'))->buttonDelete()
            ->setSelectPostUrl(U('Admin/Wechat/scoreLog'))
            ->select('','shopid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部分店')),$shops))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部微信')),$wechats))
            ->select('','update_type','select','','','',array(array('id'=>0,'value'=>'变更类型'),array('id'=>1,'value'=>'充值'),array('id'=>2,'value'=>'消费')))
            ->select('','pay_type','select','','','',array(array('id'=>'','value'=>'支付方式'),array('id'=>0,'value'=>'命令支付'),array('id'=>1,'value'=>'微信支付')))
           
            ->setSearchPostUrl(U('Admin/Wechat/scoreLog'))->search('顾客', 'nickname')->search('粉丝号', 'sys_id')
            ->keyText('nickname', '顾客')->keyText('sys_id', '粉丝号')->keyText('um', '数量')->keyText('balance', '结余')
            ->keyText('name', '操作者')->keyText('disc', '备注')->keyText('notice', '通知')->keyText('name', '操作者')->keyText('last_sender_name', '上次操作')
            ->keyCreateTime()->keyDoActionEdit('editWay?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function memberTrash($page = 1, $r = 20)
    {
        //显示页面
        $builder = new AdminListBuilder();
        $builder->clearTrash('WechatMember');
        //读取帖子数据
        $map = array('status' => -1);
        $model = M('WechatMember');
        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
        $totalCount = $model->where($map)->count();


        $builder->title(L('_REPLY_VIEW_MORE_'))
            ->setStatusUrl(U('Wechat/setMemberStatus'))->buttonRestore()->buttonClear('WechatMember')
            ->keyId()->keyLink('nickname', '昵称', 'Wechat/reply?post_id=###')
            ->keyCreateTime()->keyUpdateTime()->keyTime('last_reply_time', L('_LAST_REPLY_TIME_'))->keyBool('is_top', L('_STICK_YES_OR_NOT_'))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function editmember($id = null)
    {
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //写入数据库
            $model = M('WechatMember');
            $data = array(
                'nickname' => I('nickname'),
                'describe' => filter_content(I('describe')), 
                'remark' => I("remark"),  
                'sex' => I("sex"),
                'email' => I("email"),
                'mobile' => I("mobile")
                );

            if ($isEdit) {
                $result = $model->where(array('id' => $id))->save($data);
            } else {
                $result = $model->keyDoActionEdit($data);
            }
            //如果写入不成功，则报错
            if ($result === false) {
                $this->error($isEdit ? L('_FAIL_EDIT_') : L('_TIP_CREATE_SUCCESS_'));
            }
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_TIP_CREATE_SUCCESS_'));
        } else {
            //判断是否在编辑模式
            $isEdit = $id ? true : false;

            //读取帖子内容
            if ($isEdit) {
                $member = M('WechatMember')->where(array('id' => $id))->find();
                //拉取卡券
                $options =D('Wechat/Wechat')->GetOptions($member['appid']);
                $weObj = new Wechat($options['base']);
                $ret=$weObj->checkAuth();
                if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                $cards=$weObj->userCardlist($member['openid']);
                foreach ($cards['card_list'] as $key => $card) {
                $card_info=D('Wechat/WechatCard')->info($card['card_id']);
                $member['cards'].="<p>".$card_info['title'].' CODE:'.$card['code']."</p>";

                }
          
            } else {
                $member = array();
            }

            //显示页面
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? "编辑粉丝" : '新增粉丝')
                ->keyId()->keyReadOnly('openid','OPENID','',61)->keyReadOnly('unionid','unionid','',62)->keyText('nickname','昵称','','',61)->keyText('remark','备注名','','',62)->keyRadio('sex', '性别', '', array(0 =>'未知', 1 => '男', 2 => '女'))
                ->keyText('mobile','手机','','',61)->keyText('email','邮件','','',62)
                ->keyReadOnly('amount','余额','',61)->keyReadOnly('score','积分','',62)
                ->keyEditor('describe', '备注')->keyEditor('cards', '动态卡券')
                ->buttonSubmit(U('editMember'))->buttonBack()
                ->data($member)
                ->display();
        }

    }

    public function setMemberStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('WechatMember', $ids, $status);
    }

    public function notice($page = 1, $appid = null, $r = 20, $nickname = '', $Content = '')
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname'] = array('like', '%' . $nickname . '%');
        }
        if ($Content != '') {
            $map['Content'] = array('like', '%' . $Content . '%');
        }
        if ($appid) $map['appid'] = $appid;
       
        $model = M('WechatNotice');
        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
       
       
        $totalCount = $model->where($map)->count();

        $noticetype=array('text'=>'文字','image'=>'图片','voice'=>'声音','music'=>'音乐','video'=>'视屏','shortvideo'=>'小视屏','location'=>'位置','url'=>'链接');
        foreach ($list as &$v) {
         $v['MsgType'] = $noticetype[$v['MsgType']];  
         $v['Content'] =msubstr($v['Content'], $start=0, $length=100, $charset='utf-8',$suffix=true);
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('消息管理' . $WechatTitle)
            ->setStatusUrl(U('Wechat/setMemberStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->buttonModalPopup(U('Wechat/send'),array('status' => 0),'发送消息')
            ->setSelectPostUrl(U('Admin/Wechat/notice'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$wechats))
           
            ->keyId()->keyLink('nickname', '发送者', 'Wechat/editmember?id=###')->key('MsgType','消息类型', 'text')
            ->key('Content','内容', 'text')->key('Event','事件', 'text')->key('EventKey','事件KEY', 'text')
            ->keyCreateTime('CreateTime')->keyStatus()->keyDoActionEdit('editMember?id=###')
            ->setSearchPostUrl(U('Admin/Wechat/member'))->search('粉丝名', 'nickname')->search('内容', 'Content')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

     public function ma($page = 1, $appid = null, $r = 20, $name = '',$ma_type=0,$order='id desc')
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($name != '') {
            $map['name'] = array('like', '%' . $name . '%');
        }
       
        if ($appid) $map['appid'] = $appid;
        if ($ma_type) $map['ma_type'] = $ma_type;

        $model = M('WechatMa');
        $list = $model->where($map)->order($order)->page($page, $r)->select();
       
        $totalCount = $model->where($map)->count();


        foreach ($list as &$v) {
         $v['ma_type'] =  $this->ma_type[$v['ma_type'] ];  
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $map['wechat_type']=3;
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('二维码管理' )->buttonNew(U('Wechat/editMa'))
            ->setStatusUrl(U('Wechat/setMaStatus'))->buttonEnable()->buttonDisable()
            ->setSelectPostUrl(U('Admin/Wechat/ma'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$wechats))
            ->select('','ma_type','select','','','',array(array('id'=>0,'value'=>'选择类型'),array('id'=>1,'value'=>'员工码'),array('id'=>2,'value'=>'营销码'),array('id'=>3,'value'=>'功能码'),array('id'=>4,'value'=>'固定场景码'),array('id'=>5,'value'=>'微信打印机码')))
            ->keyId()->keyText('scene_id','唯一码')->keyText('ma_type','类型')->keyLink('name','名称', 'Wechat/ShowMa?id=###')->keyText('description','描述')
            ->keyText('scan_times','扫描')->keyText('members','粉丝')
            ->keyCreateTime()->keyUpdateTime()->keyStatus()->keyDoActionEdit('editMa?id=###')
            ->setSearchPostUrl(U('Admin/Wechat/ma'))->search('名称', 'name')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
       }


    public function editMa($id = null, $name = '', $create_time =0, $status = 1)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $model = M('WechatMa');

            if (!$_POST['ma_type'] || !$_POST['appid'])  $this->error('所属微信和二维码分类必须选择，亲爱的');
                //写入数据库
                if ($isEdit) {
                    $data['id'] = $id;
                    $data = $model->create();
                    $result = $model->where(array('id' => $id))->save();
                    if ($result === false)  $this->error(L('_FAIL_EDIT_'));
                   
                } else {
                     //生成一个二维码
                $map["appid"]=I('appid', 0, 'intval');
                $scene_id = $model->where($map)->max('scene_id');
                $ma_info=$this->getMa( $scene_id+1,I('appid'));
                if ( $ma_info['error']) $this->error( $ma_info['error']);
                $_POST['scene_id'] =$ma_info['scene_id'];
                $_POST['ticket'] =$ma_info['ticket'];
                $_POST['url'] =$ma_info['url'];
                $_POST['aid']=session('user_auth.aid');
                $data = $model->create();
                $result = $model->add();

                if (!$result)$this->error(L('_ERROR_CREATE_FAIL_'));
                }
           $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_SUCCESS_SAVE_'));
        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

           unset($map);
           $map['aid'] = array('eq',session('user_auth.aid'));
           $map['wechat_type']=3;
           $wechats = M('Wechat')->where($map)->field('id,name')->select();
           
            foreach ($wechats as $w) {
                $wechats_arr[$w['id']] = $w['name'];
            }

           unset($map);
           $map['aid'] = array('eq',session('user_auth.aid'));
           $qmembers = M('QwechatMember')->where($map)->field('userid,name')->select();
          
            foreach ($qmembers as $w) {
                $qmembers_arr[$w['userid']] = $w['name'];
            }

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $ma = M('WechatMa')->where(array('id' => $id))->find();
            } else {
                $ma = array('create_time' => time());
            }

            $robs = D('News/NewsRob')->getRobs("aid=0 or aid=".session('user_auth.aid'),'title,id');
            $robs =array_column($robs, 'title', 'id');
            $robs=$this->defaut_rob+$robs;
            
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '修改二维码' : '添加二维码')
                ->data($ma)
                ->keyId()
                ->keyText('name','二维码名称')
                ->keyText('description','简介', '建议15字内')
                ->keySelect('appid', '归属微信', '',  $wechats_arr)
                ->keySelect('ma_type', '分类', '',   array('1'=>'员工码','2'=>'营销码','3'=>'功能码','4'=>'固定场景码','5'=>'微信打印机码'))
                ->keyChosenOne('userid', '负责人', '',  $qmembers_arr)
                 ->keyChosenOne('subscribe_rob', '关注回复', '', $robs)
                ->keyChosenOne('scan_rob', '扫描回复', '', $robs)
               
                ->keyStatus()->keyCreateTime()
                ->buttonSubmit(U('editMa'))->buttonBack()
                ->display();

               
        }

    }
//选择机器人
     public function robset($page=1,$r=5){
        $inputid = I('get.inputid');
        $totalCount =   D('NewsRob')->where($map)->count();
        $pager = new \Think\Page($totalCount, $r);
        $pager->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $paginationHtml = $pager->show();

        $list =  D('NewsRob')->where($map)->page($page,$r)->order('id DESC')->select();

        $this->assign('inputid', $inputid);
        $this->assign('list', $list);
        $this->assign('paginationHtml', $paginationHtml);
        $this->display(T('Application://Wechat@Wechat/robset'));
    }



    public function getMa($scene_id,$appid)
    {
                if (!$scene_id)  return $back['eror']="缺少scene_id!!";
                $options =D('Wechat/Wechat')->GetOptions($appid);
                $weObj = new TPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret) {
                   return $back['eror']=ErrCode::getErrText($weObj->errCode).S($authname);
                }  
                 

                 $back=$weObj->getQRCode($scene_id,$type=1);

                 if (!$back)return $back['eror']=ErrCode::getErrText($weObj->errCode);
                 $back['url']=$weObj->getQRUrl($back['ticket']);
                 $back['scene_id']=$scene_id;

                 return $back;


    }

    public function showMa($id=0)
    {
        $ma = M('WechatMa')->where(array('id'=>$id))->find();
        if($ma['ma_type']==1){
        $member=D('Qwechat/QwechatMember')->infoByUserid($ma['userid']);
        $bg=M('Wechat')->where(array('id'=>$ma['appid']))->getField('business_card');
        $this->assign('ma', $ma);
        $this->assign('bg', get_cover($bg, 'path'));
        $this->assign('member', $member);
        $this->assign('show', $show);
        $this->setTitle('生产员工名片');
        $this->display(T('Application://Qwechat@Qwechat/showma') );
        }else{
        // $url = M('WechatMa')->where(array('id'=>$id))->getField('url');
        redirect($ma['url']); 
         }

             

    }

    public function setMaStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('WechatMa', $ids, $status);
    }
    

//门店管理
    public function poiList($page = 1, $r = 20,$appid=0)
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname'] = array('like', '%' . $nickname . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($appid) $map['appid'] = $appid;
        if ($sex) $map['sex'] = $sex;
        $model = M('WechatPoilist');
        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
       
       
        $totalCount = $model->where($map)->count();


        foreach ($list as &$v) {
         switch ($v['status']) {
             case 'CARD_STATUS_NOT_VERIFY':
                $v['status']='待审核';
                 break;
            case 'CARD_STATUS_VERIFY_FAIL':
                $v['status']='审核失败';
                 break;
            case 'CARD_STATUS_VERIFY_OK':
                $v['status']='通过审核';
                 break;
            case 'CARD_STATUS_USER_DELETE':
                $v['status']='卡券被商户删除';
                 break;
            case 'CARD_STATUS_DISPATCH':
                $v['status']='在公众平台投放过的卡券';
                 break;
          }
       
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('门店管理' . $WechatTitle)
            ->setStatusUrl(U('Wechat/setCardStatus'))->buttonEnable()->buttonDisable()->buttonDelete();
            if ($appid>0)$builder->buttonNew(U('Wechat/getPoiList',array('appid'=>$appid)),'下拉门店');
            $builder->setSelectPostUrl(U('Admin/Wechat/PoiList'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$wechats))
            ->select('','sex','select','','','',array(array('id'=>0,'value'=>'选择性别'),array('id'=>1,'value'=>'男'),array('id'=>2,'value'=>'女')))
            ->keyId()->keyLink('business_name', L('_TITLE_'), 'Wechat/editCard?id=###')->key('brand_name','副标题', 'text')
            ->key('quantity','库存', 'text')->key('total_quantity','制卡量', 'text')->key('status','状态', 'text')
             ->keyDoActionEdit('editCard?id=###')
            ->setSearchPostUrl(U('Admin/Wechat/PoiList'))->search('昵称', 'nickname')->search('手机', 'mobile')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


    
      public function getPoiList($appid)
    {
         $builder = new AdminConfigBuilder();
            $builder->title( '下拉门店' )
                    ->keyProgressbar()->keyUpdateTime()
                
                ->buttonSubmit(U('editMember'))->buttonBack()
                ->data($post)
                ->display();
        
        set_time_limit(0);

            //验证，并下拉部门
            
                 $options =D('Wechat/Wechat')->GetOptions($appid);
                 $weObj = new TPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getCardLocations();
                 if (!$list)$this->error(ErrCode::getErrText($weObj->errCode));
                
                 $shops=$list['location_list'];
            
            //处理数据。开始循环处理部门数据
            
        foreach ($shops as $key=> $shop) {
          
            // $card_info=$weObj->getCardInfo($card_id);
            $shop['appid']=$appid;
            // dump($shop);die;

           $back= D('Wechat/WechatPoilist')->updatePoilist($shop);

           
            
            $msg=($key+1).'/'.$total.'：'.($back=="add"?"[添加]":"[更新]").' 门店'.$shop['name'].'</br>';
            show_msg($msg,'',floor(($key+1)/$total*100));
            ob_flush(); 
            flush(); 

        }
        show_msg('1秒后将跳转到部门列表！');
        echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('poilist')."'},1000)</script>";
    
    }


    public function card($page = 1, $r = 20,$appid=0)
    {
        //读取帖子数据
        // $map = array('status' => array('NEQ', 'CARD_STATUS_DELETE'));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname'] = array('like', '%' . $nickname . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($appid) $map['appid'] = $appid;
        if ($sex) $map['sex'] = $sex;
        $model = M('WechatCard');
        $list = $model->where($map)->order('card_id desc')->page($page, $r)->select();
       
       
        $totalCount = $model->where($map)->count();


        foreach ($list as &$v) {
         switch ($v['status']) {
             case 'CARD_STATUS_NOT_VERIFY':
                $v['status']='待审核';
                 break;
            case 'CARD_STATUS_VERIFY_FAIL':
                $v['status']='审核失败';
                 break;
            case 'CARD_STATUS_VERIFY_OK':
                $v['status']='通过审核';
                 break;
            case 'CARD_STATUS_DELETE':     
            case 'CARD_STATUS_USER_DELETE':
                $v['status']='卡券被商户删除';
                 break;
            case 'CARD_STATUS_DISPATCH':
                $v['status']='在公众平台投放过的卡券';
                 break;
          }
       
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('卡券管理' . $WechatTitle)
            ->setStatusUrl(U('Wechat/setCardStatus'))->buttonEnable()->buttonDisable()->buttonDelete();
            if ($appid>0)$builder->buttonNew(U('Wechat/getCards',array('appid'=>$appid)),'下拉卡券');
            $builder->setSelectPostUrl(U('Admin/Wechat/card'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$wechats))
           
            ->keyId('card_id')->keyLink('title', L('_TITLE_'), 'Wechat/editCard?id=###')
            ->keyText('quantity','库存')->keyText('total_quantity','制卡量')->keyText('status','状态')
             ->keyDoActionEdit('editCard?id=###')
            ->setSearchPostUrl(U('Admin/Wechat/card'))->search('昵称', 'nickname')->search('手机', 'mobile')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


      public function getCards($appid=null)
    {
         $builder = new AdminConfigBuilder();
            $builder->title( '下拉卡券' )
                    ->keyProgressbar()->keyUpdateTime()
                
                ->buttonSubmit(U('editMember'))->buttonBack()
                ->data($post)
                ->display();
        
        set_time_limit(0);

            //验证，并下拉
            
                 $options =D('Wechat/Wechat')->GetOptions($appid);
                 $weObj = new Wechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getCardIdList();
                 if (!$list)$this->error(ErrCode::getErrText($weObj->errCode));
                
                 $cards=$list['card_id_list'];
            
            //处理数据。开始循环处理卡券数据
            
        foreach ($cards as $key=> $card_id) {
          
            $card_info=$weObj->getCardInfo($card_id);
            $type=$card_info['card']['card_type'];
            $card=$card_info['card'][strtolower($type)];
            $card['card_type']=$type;
            $card=$this->arrToOne($card);
            $card['appid']=$appid;

          
            $back= D('Wechat/WechatCard')->updateCard($card);
           
            
            $msg=($key+1).'/'.$total.'：'.($back=="add"?"[添加]":"[更新]").' 卡券'.$card['name'].'</br>';
            show_msg($msg,'',floor(($key+1)/$total*100));
            ob_flush(); 
            flush(); 

        }
        show_msg('1秒后将跳转到部门列表！');
        echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('card',array('appid'=>$apppid))."'},1000)</script>";
    
    }

     public function arrToOne ($arr) 
   {
      static $tmp=array(); 

      if (!is_array ($arr)) 
      {
         return false;
      }
      foreach ($arr as $key=>$val ) 
      {
         if (is_array ($val)) 
         {
            $this->arrToOne ($val);
         } 
         else 
         {
            $tmp[$key]=$val;
         }
      }
      return $tmp;

   }


}
