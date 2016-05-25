<?php
namespace Wechat\Controller;

use Think\Controller;
use Common\Builder\AdminConfigBuilder;
use Common\Builder\AdminListBuilder;
use Common\Builder\CommonSiteBuilder;

class IndexController extends Controller
{


     public function rob(){
        $my_rob= myConfig('QWECHAT_ROB',$admin['aid'],'','Daily');
        $yesterday = date("Y-m-d",strtotime("-1 day"));
        $where='DATEDIFF(NOW() , FROM_UNIXTIME(`create_time`)) = 1 ';
        $admins=M('Member')->field('aid,nickname')->where('aid>0 and aid=uid')->select();
        foreach ($admins as $key => $admin) {
          //会员数据
          $notice='';
          $members=M('WechatMember')->field('count(id) as total,appid' )->where(' aid='.$admin['aid'].' and DATEDIFF(NOW() , FROM_UNIXTIME(`subscribe_time`)) = 1' )->group('appid')-> select();
          if ($members ){
            $notice.=$yesterday.'微信粉丝数据';
           foreach ($members as $key => $member) {
            $wechat=M('Wechat')->find($member['appid']);
            $notice.="\n".($wechat['name']?$wechat['name']:'未知微信').':'.$member['total']."人";
            } 
          if ($my_rob)sendQmessage('@all',$my_rob,$notice);  
          }

          //充值报表
          $amount_total_data=M('WechatMember')->where(array('aid'=>$admin['aid']))->sum('amount') ;
         
          $notice='会员储值数据'.$amount_total_data;
          $amounts=M('WechatAmountLog')->field('shopid,sum(CASE WHEN um>0 THEN   um   END   ) AS add_total,sum(CASE WHEN um<0 THEN   um   END   ) AS down_total' )->where('aid='.$admin['aid'].'   and '.$where )->group('shopid')-> select(); 
           if ($amounts ){
               $notice.=$yesterday.'微信储值数据';
               foreach ($amounts as $key => $amount) {
                $shop_info=M('QwechatShop')->find($amount['shopid']);
                $logs=M('WechatAmountLog')->field('sys_id,nickname,um,balance,name,create_time')->where('shopid='.$amount['shopid'].'   and '.$where )-> select(); 
                $notice.="\n".$shop_info['name'].'储值:'.$amount['add_total'].'/'.$amount['down_total'];
                $notice.="\n操作明细";
                    if ($logs){
                        foreach ($logs as $k => $log) {
                           $notice.="\n".$log['name'].'变更:'.$log['nickname'].$log['um'].'结余'.$log['balance'];
                        }
                    }
                } 
          if ($my_rob)sendQmessage('@all',$my_rob,$notice);  
          }

          $scores=M('WechatScoreLog')->field('shopid,sum(CASE WHEN um>0 THEN   um   END   ) AS add_total,sum(CASE WHEN um<0 THEN   um   END   ) AS down_total,sum(is_old) as olds,count(id) as total' )->where('aid='.$admin['aid'].'   and '.$where )->group('shopid')-> select(); 
           if ($scores ){
            $notice=$yesterday.'微信积分数据';
           foreach ($scores as $key => $score) {
            $shop_info=M('QwechatShop')->find($score['shopid']);
            $notice.="\n".($shop_info['name']?$shop_info['name']:'未知分店').'积分:'.$score['total'].$score['down_total'];
            } 
           if ($my_rob)sendQmessage('@all',$my_rob,$notice);  
           }
         
         
         
         
          

        }

         
          
    }


    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
   
    public function index($openid='',$mobile=''){
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $data['mobile']=$mobile;
            if (!$openid || !$mobile || !isMobile($mobile)) $this->error('请输入正确的手机号！');
            $res=D('Wechat/WechatMember')->where(array('openid'=>$openid))->save($data);
            if ($res>0) $this->success('信息完善成功');
             
        } else {
            $data['openid']=I('openid');
            $member=D('Wechat/WechatMember')->info($openid);
            $builder = new AdminConfigBuilder();
            $builder->title( $member['nickname'].'会员信息')
            ->keyText('openid','微信号')->keyText('mobile','手机号')
            ->data($data)->group('手机号','mobile')
            ->buttonSubmit()->display();
         }

        
    }

     public function mySpace($openid='',$mobile=''){
       
        $data['openid']=I('openid');
        $member=D('Wechat/WechatMember')->info($openid);
        $member['print']=D('Weprinter/WeprinterMember')->where(array('openid'=>$openid))->getField('balance');
        $member['games']= D('News/NewsGameScore')->getGift($openid);
        $member['ranks']= D('News/NewsGameScore')->getRank();
       

        $this->assign('member',$member);   
        $this->display();
         
    }
  
    public function game()
    {
        $this->display( T('Application://Wechat@Index/game') );
    }

    public function callback()
    {

        $code = I('get.code', '', 'text');
        $config = D('Weixin/WeixinConfig')->getWeixinConfig();

        $wechat = new WechatAuth($config['APP_ID'], $config['APP_SECRET']);
        /* 获取请求信息 */
        $token = $wechat->getAccessToken('code', $code);
        $userinfo = $wechat->getUserInfo($token);

        $openid = !empty($userinfo['unionid']) ? $userinfo['unionid'] : $userinfo['openid'];
        session('weixin_token',array('access_token'=>$token['access_token'],'openid'=>$openid));

        $map = array('type_uid' => $openid, 'type' => 'weixin');
        $uid = D('sync_login')->where($map)->getField('uid');

        if ($uid) {
            $rs = D('Mob/Member')->mobileLogin($uid); //登陆
            redirect(U('Mob/weibo/index'));
        }else{

            redirect(U('Mob/member/weixin_bind'));

            $user_info = $this->weixin($userinfo);

            $uid = $this->addData($user_info);
        }

    }

    private  function weixin($data){
        if($data['ret'] == 0){
            $userInfo['type'] = 'WEIXIN';
            $userInfo['name'] = $data['nickname'];
            $userInfo['nick'] = $data['nickname'];
            $userInfo['head'] = $data['headimgurl'];
            $userInfo['sex'] = $data['sex']=='1'? 0:1;
            return $userInfo;
        } else {
            return("获取微信用户信息失败：{$data['errmsg']}");
        }
    }


    private function addData($user_info)
    {
        $ucenterModer = UCenterMember();
        $uid = $ucenterModer->addSyncData();
        D('Member')->addSyncData($uid, $user_info);
        $ucenterModer->initRoleUser(1, $uid); //初始化角色用户
        // 记录数据到sync_login表中
        $this->addSyncLoginData($uid);
        $this->saveAvatar($user_info['head'], $uid);
        return $uid;
    }

    /**
     * addSyncLoginData  增加sync_login表中数据
     * @param $uid
     * @return mixed
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    private function addSyncLoginData($uid)
    {
        $session = session('weixin_token');
        $data['uid'] = $uid;
        $data['type_uid'] =$session['openid'];
        $data['oauth_token'] = $session['access_token'];
        $data['oauth_token_secret'] =$session['openid'];
        $data['type'] = 'weixin';
        $syncModel = D('sync_login');

        if (!($syncModel->where($data)->count())) {
            $syncModel->add($data);
        }
        return true;
    }

    private function saveAvatar($url, $uid)
    {
        $driver = modC('PICTURE_UPLOAD_DRIVER', 'local', 'config');

        if ($driver == 'local') {
            mkdir('./Uploads/Avatar/' . $uid, 0777, true);
            $img = file_get_contents($url);
            $filename = './Uploads/Avatar/' . $uid . '/crop.jpg';
            file_put_contents($filename, $img);
            $data['path'] = '/' . $uid . '/crop.jpg';
        } else {
            $name = get_addon_class($driver);
            $class = new $name();
            $path = '/Uploads/Avatar/' . $uid . '/crop.jpg';
            $res = $class->uploadRemote($url, 'Uploads/Avatar/' . $uid . '/crop.jpg');
            if ($res !== false) {
                $data['path'] = $res;
            }
        }
        $data['uid'] = $uid;
        $data['create_time'] = time();
        $data['status'] = 1;
        $data['is_temp'] = 0;
        $data['driver'] = $driver;
        D('avatar')->add($data);
    }


    public function existLogin()
    {
        $aUsername = I('post.username');
        $aPassword = I('post.password');
        $aRemember = I('post.remember');
        $uid = UCenterMember()->login($aUsername, $aPassword, 1);
        if (0 < $uid) { //UC登陆成功
            /* 登陆用户 */
            $Member = D('Member');

            if ( D('Mob/Member')->mobileLogin($uid, $aRemember == 1)) { //登陆用户
                $this->addSyncLoginData($uid);

                redirect(U('Mob/weibo/index'));

                //$this->success('登陆成功！', session('login_http_referer'));
            } else {
                $this->error($Member->getError());
            }

        } else { //登陆失败
            switch ($uid) {
                case -1:
                    $error = '用户不存在或被禁用！';
                    break; //系统级别禁用
                case -2:
                    $error = '密码错误！';
                    break;
                default:
                    $error = '未知错误27！';
                    break; // 0-接口参数错误（调试阶段使用）
            }
            $this->error($error);
        }
    }


    public function newAccount()
    {


        $aUsername = I('post.username');
        $aNickname = I('post.nickname');
        $aPassword = I('post.password');

        // 行为限制
        $return = check_action_limit('reg', 'ucenter_member', 1, 1, true);
        if ($return && !$return['state']) {
            $this->error($return['info'], $return['url']);
        }


        $ucenterModel = UCenterMember();
        $uid = $ucenterModel->register($aUsername, $aNickname, $aPassword);
        if (0 < $uid) { //注册成功
            $this->addSyncLoginData($uid);

            $config =  D('addons')->where(array('name'=>'SyncLogin'))->find();
            $config   =   json_decode($config['config'], true);

            $ucenterModel->initRoleUser($config['role'], $uid); //初始化角色用户

            $uid = $ucenterModel->login($aUsername, $aPassword, 1); //通过账号密码取到uid
            D('Mob/Member')->mobileLogin($uid);
            redirect(U('Mob/weibo/index'));
            //$this->success('绑定成功！', session('login_http_referer'));
        } else { //注册失败，显示错误信息
            $this->error(A('Ucenter/Member')->showRegError($uid));
        }

    }



    public function unBind()
    {

        $token = session('weixin_token');
        $config = D('Weixin/WeixinConfig')->getWeixinConfig();
        $wechat = new WechatAuth($config['APP_ID'], $config['APP_SECRET']);

        $userinfo = $wechat->getUserInfo($token);

        $user_info = $this->weixin($userinfo);
        $uid = $this->addData($user_info);

        D('Mob/Member')->mobileLogin($uid); //登陆
        redirect(U('Mob/weibo/index'));


    }

}