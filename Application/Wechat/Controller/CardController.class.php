<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */

namespace Wechat\Controller;

class RobController extends AdminController
{

    public function index()
    {
        redirect(U('Wechat'));
    }

    public function getRob($keywords)
    {
        
        // $map['keywords']=array('like',$keywords);
        // $rob = $this->getRobModel()->where()->find();
        return $keywords;

       
      
    }

    public function rob($page=1,$r=10){
        $builder = new AdminListBuilder();
        $builder->title("自动回复列表");
        $where = $cats = array();
        if(I('get.type')){
            $where['type'] = I('get.type');
        }
        if(I('get.cid')){
            $where['cid'] = I('get.cid');
        }
        $where['is_news'] = 0;

        $reportCount = $this->getAreplyModel()->where($where)->count();
        $list = $this->getAreplyModel()->page($page,$r)->where($where)->order('id DESC')->select();
        $types = array(
            1 => '文本回复',
            2 => '图文回复',
            3 => '多图文回复',
        );
        foreach($list as $key => $item){
            if($item['type'] == 2){
                $image = get_cover($list[$key]['image'], 'path');
                $list[$key]['content'] = "<img src='{$image}' width='30' height='30' /> " .$list[$key]['title'];
            } else if($item['type'] == 3){
                if($item['content']){
                    $initHtml = '';
                    $news = $this->getAreplyModel()->order('id ASC')->where(array('id'=> array('in', $item['content'])))->select();
                    foreach($news as $k => $v){
                        $img = "";
                        $v['image'] = get_cover($v['image'], 'path');
                        $img = "<img src=\"{$v['image']}\" width=\"30\" height=\"30\">";
                        $initHtml .= "<p>{$img} {$v['title']}</p>";
                    }
                }
                $list[$key]['content'] = $initHtml;
            }
            $list[$key]['type'] = $types[$list[$key]['type']];
            $list[$key]['is_attention'] = $list[$key]['is_attention'] == 1 ? '是' : ' ';
        }
        $builder
            ->keyId()
            ->keyText('keywords', "关键词")
            ->keyText('type', "类型")
            ->keyText('is_attention', "关注回复")
            ->keyText('content', "回复内容")
            ->keyDoActionEdit('Weixin/edit?id=###')
            ->keyDoActionEdit('Weixin/attention?id=###', '设为关注回复')
            ->buttonDelete(U('del'));

        $builder->data($list);
        $builder->pagination($reportCount, $r);
        $builder->display();
    }

    






    public function GetOptions($weiapp){

        $options = S('options'.$weiapp);
        if($this->debug || !$options){
            $options = array();
            $map['id']=$weiapp;
            $options = D('Wechat')->where($map)->limit(1)->find();
            $options['base']=array('token'=>$options['token'],'encodingaeskey'=>$options['encodingaeskey'],'appid'=>$options['appid'],'appsecret'=>$options['appsecret']);
            S('options'.$weiapp, $options);
        }

        return $options;
    }




    

    public function Wechat($page = 1, $r = 20)
    {
        //读取数据
        if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        $map['status'] = array('GT', -1);
        $model = M('Wechat');
        $list = $model->where($map)->page($page, $r)->order('type desc')->select();
        $totalCount = $model->where($map)->count();
        $groups = M('AuthGroup')->select();
        foreach ($groups as $v) {
            $group_name[$v['id']] = $v['title'];
        }
        foreach ($list as &$v) {
            $v['post_count'] = D('WechatPost')->where(array('Wechat_id' => $v['id']))->count();
            $user_group_ids = explode(',', $v['allow_user_group']);
            foreach($user_group_ids as &$gid){
                $gid= $group_name[$gid];
            }
            $v['allow_group_text'] =implode('、',$user_group_ids);
        }

        

        //显示页面
        $builder = new AdminListBuilder();
        $builder
            ->title(L('_BLOCK_MANAGE_'))
            ->buttonNew(U('Wechat/editWechat'))
            ->setStatusUrl(U('Wechat/setWechatStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->ajaxButton(U('Wechat/CloudMember'),array('status' => $status),'下拉微信粉丝')
            ->buttonSort(U('Wechat/sortWechat'))
            ->keyId()->keyLink('title', L('_TITLE_'), 'Wechat/post?Wechat_id=###')
            ->keyText('allow_group_text', '允许发帖的用户组')
            ->keyCreateTime()->keyText('post_count', L('_THEME_COUNT_'))->keyStatus()->keyDoActionEdit('editWechat?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


    public function CloudMember($ids = array())
    {
        $aids = I('post.ids');
        ignore_user_abort (true);
        set_time_limit(0);
      
        
        foreach ($aids as $key => $value) {
           
            $options =$this->GetOptions($value);

            //验证，并下拉用户
            if ($options['type']==-1){
                 $weObj = new qyTPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(qyErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getUserListInfo(1,1,0);
                 if (!$list)$this->error(qyErrCode::getErrText($weObj->errCode));
                 $members=$list['userlist'];
                 
            }else{
                 $weObj = new TPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getUserList();                                  //先下拉一次
                 if (!$list)$this->error(ErrCode::getErrText($weObj->errCode));
                 if($list['total']>10000){                                         
                    echo  $times= ceil($list['total']/$list['count']);                  //如果粉丝超过1W需要下拉多次，并且雅俗到list 里面
                     for ($i=1 ;$i<$times;$i++){
                         $list_next=$weObj->getUserList($list['next_openid']);
                         $list['data']['openid']=array_merge($list['data']['openid'],$list_next['data']['openid']);
                         $list['next_openid']=$list_next['next_openid'];
                    }
                 }
                 $members=$list['data']['openid'];
            }

             // $weObj->log($members);
            //处理数据。开始循环处理用户数据
            
                    foreach ($members as $v){
                        if ( $options['type']<>-1 ) {
                         $v=$weObj->getUserInfo($v);   //拉取每一个用户的基本信息
                         if (!$v)$this->error("我们在拉取用户信息的时候发送错误：".ErrCode::getErrText($weObj->errCode));
                         }

                         
                        $UpdateDATE=array(
                            'aid'=>$options['aid'],
                            'weiapp'=>$value,
                            'wechat'=>$options['title'],
                            'weixinid'=>$v['weixinid'],
                            'openid'=>$v['openid'],
                            'userid'=>$v['userid'],
                            'unionid'=>$v['unionid'],
                            'nickname'=>$v['nickname']?$v['nickname']:$v['name'],
                            'remark'=>$v['remark']?$v['remark']:$v['name'],
                            
                            'sex'=>$v['sex']?$v['sex']:$v['gender'],
                            'headimgurl'=>$v['headimgurl']?$v['headimgurl']:$v['avatar'],
                            'province'=>$v['province'],
                            'city'=>$v['city'],
                            'language'=>$v['language'],


                            'groupid'=>$v['groupid'],
                            'department'=>implode(",",$v['department']),
                            'position'=>$v['position'],
                            'subscribe'=>$v['status']?$v['status']:$v['status'],
                            'subscribe_time'=>$v['subscribe_time'],
                            'weixinid'=>$v['weixinid'],

                            'email'=>$v['email'],
                            'mobile'=>$v['mobile'],
                            'extattr'=>$v['extattr'],
                            ); 

                        $UpdateDATE=array_filter($UpdateDATE);



                        //检查用户库中是否存在这个用户。
                        if($options['type']==-1){
                            if (!$v['userid']) $this->error("我们遇到来自微信端的错误，原因用户缺少userid");  //组织重复性获取
                            $map['userid']=$v['userid'];
                        }else{

                            if (!$v['openid']){
                                 $weObj->log($v);
                                 $this->error("我们遇到来自微信端的错误，原因用户缺少openid");
                            } 
                            $map['openid']=$v['openid'];
                        }

                        $map['sid']=$options['sid'];
                        
                        $haveMember =M('WechatMember')->where($map)->find();
                        // $weObj->log( M('WechatMember')->getlastsql());

                        if (!$haveMember){    //不存在就注册,然后更新用户信息
                             M('WechatMember')->add($UpdateDATE);
                             $add_um+=1;
                        }else{
                             M('WechatMember')->where($map)->save($UpdateDATE);
                             $update_um+=1;
                        }

                      }

                 $total+=$list['total']?$list['total']:count($list['userlist']); 
                 unset($map); 
           
        } 

        $this->success('从'.count($ids)."个微信下拉".$total."粉丝，新增".($add_um?$add_um:0).",更新".($update_um?$update_um:0).$um);  
     
    }

   


    private function rand_username()
    {
        $username = create_rand(4);
       if (M('ucenter_member')->where(array('username' => $username))->select()) {
            $this->rand_username();
        } else {
            return $username;
        }
    }

    private function checkNickname($nickname)
    {
         $nickname=addslashes($nickname);
         $nickname=str_replace(' ','',$nickname);
         $length=modC('NICKNAME_MAX_LENGTH',32,'USERCONFIG');
         if(mb_strlen($nickname, 'utf8') > $length)$nickname= mb_substr($nickname, 0, $length, 'utf8');
         return $nickname;

    }

    public function rand_openid()
    {
        $openid = create_rand(10);
       if (M('ucenter_member')->where(array('openid' => $openid))->select()) {
            $this->rand_openid();
        } else {
            return $openid;
        }
    }



 


    public function type()
    {
        $list = D('Wechat/WechatType')->getTree();

        $map = array('status' => array('GT', -1), 'type_id' => array('gt', 0));
         if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        $Wechats = M('Wechat')->where($map)->order('sort asc')->field('id as Wechat_id,title,sort,type_id as pid,status')->select();
       
        $list = array_merge($list, $Wechats);

        $list = list_to_tree($list, 'id', 'pid', 'child', 0);
        // dump($list[1]['child'] );
        // die;
        $this->assign('list', $list);
        $this->display(T('Application://Wechat@Wechat/type'));
    }

    public function setTypeStatus($ids = array(), $status = 1)
    {
        if (is_array($ids)) {
            $map['id'] = array('in', implode(',', $ids));
        } else {
            $map['id'] = $ids;
        }
        $result = D('Wechat/WechatType')->where($map)->setField('status', $status);
        $this->success(L('_SUCCESS_SETTING_') . L('_PERIOD_') . L('_SUCCESS_EFFECT_') . $result . L('_SUCCESS_RECORD_') . L('_PERIOD_'));
    }


    public function addType()
    {
        $aId = I('id', 0, 'intval');
        if (IS_POST) {
            $aPid = I('pid', 0, 'intval');
            $aSort = I('sort', 0, 'intval');
            $aStatus = I('status', -2, 'intval');
            $aTitle = I('title', '', 'op_t');
            if ($aId != 0)
                $type['id'] = $aId;

            $type['sort'] = $aSort;
            $type['pid'] = $aPid;
            if ($aStatus != -2)
                $type['status'] = $aStatus;
            $type['title'] = $aTitle;
            if ($aId != 0) {
                $result = M('WechatType')->save($type);
            } else {
                $result = M('WechatType')->add($type);
            }
            if ($result) {
                $this->success(L('_SUCCESS_OPERATE_') . L('_EXCLAMATION_'));
            } else {
                $this->error(L('_FAIL_OPERATE_') . L('_EXCLAMATION_'));
            }


        }


        $type = M('WechatType')->find($aId);
        if (!$type) {
            $type['status'] = 1;
            $type['sort'] = 1;
        }
        $configBuilder = new AdminConfigBuilder();
        $configBuilder->title(L('_CATEGORY_EDIT_'));
        $configBuilder->keyId()
            ->keyText('title', L('_CATEGORY_NAME_'))
            ->keyInteger('sort', L('_SORT_'))
            ->keyStatus()
            ->buttonSubmit()
            ->buttonBack();


        $configBuilder->data($type);
        $configBuilder->display();

    }

    public function WechatTrash($page = 1, $r = 20, $model = '')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        //读取回收站中的数据
        $map = array('status' => '-1');
        $model = M('Wechat');
        $list = $model->where($map)->page($page, $r)->order('sort asc')->select();
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
        D('Wechat/Wechat')->cleanAllWechatsCache();

    }

    public function doSortWechat($ids)
    {
        $builder = new AdminSortBuilder();
        $builder->doSort('Wechat', $ids);
        D('Wechat/Wechat')->cleanAllWechatsCache();
    }

    public function editWechat($id = null, $title = '', $create_time =0, $status = 1, $allow_user_group = 0, $logo = 0, $type_id = 0)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $model = M('Wechat');
            if (I('quick_edit', 0, 'intval')) {
                //生成数据
                $data = array('title' => $title, 'sort' => I('sort', 0, 'intval'));
                //写入数据库
                $result = $model->where(array('id' => $id))->save($data);
                if ($result === false) {
                    $this->error(L('_FAIL_EDIT_'));
                }
            } else {
                //生成数据
                $data = array(
                    
                    'title' => $title,
                    'agentid' => I('agentid', 0, 'intval'), 
                    'logo' => $logo, 
                    'ma_logo' => I('ma_logo', 0, 'intval'), 
                    'description' => I('description', '', 'op_t'),
                    'type' => I('type', -1, 'intval'), 
                    'type_id' => I('type_id', 1, 'intval'),
                    'token' => I('token', 1, 'text'),
                    'encodingaeskey' => I('encodingaeskey', 1, 'text'),
                    'appid' => I('appid', 1, 'text'), 
                    'appsecret' => I('appsecret', 1, 'text'),
                    'mchid' => I('mchid', 1, 'text'),
                    'pay_key' => I('pay_key', 1, 'text'), 
                    'apiclient_cert' => I('apiclient_cert', 1, 'text'),
                    'apiclient_key' => I('apiclient_key', 1, 'text'),
                    'subscribe_reply' => I('subscribe_reply', 1, 'text'), 
                    'noanswer_reply' => I('noanswer_reply', 1, 'text'),
                    'hr_reply' => I('hr_reply', 1, 'text'),
                    'wifi_reply' => I('wifi_reply', 1, 'text'), 
                    'feedback_card' => I('feedback_card', 1, 'text'),
                    'sys_work' => I('sys_work', 1, 'text'), 
                    'default_group' => I('default_group', 0, 'intval'), 
                    'default_role' => I('default_role', 0, 'intval'), 


                    'create_time' => $create_time, 
                    'status' => $status, 
                    'admin' => I('admin', 1, 'text')  
                    );

               
                    
                    

                //写入数据库
                if ($isEdit) {
                    $data['id'] = $id;
                    $data = $model->create($data);
                    $result = $model->where(array('id' => $id))->save($data);
                    if ($result === false) {
                        $this->error(L('_FAIL_EDIT_'));
                    }
                } else {
                    $data['aid']=session('user_auth.aid');
                    $data = $model->create($data);
                    $result = $model->add($data);
                    if (!$result) {
                        $this->error(L('_ERROR_CREATE_FAIL_'));
                    }
                }
            }
            S('Wechat_list', null);
            D('Wechat/Wechat')->cleanAllWechatsCache();
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_SUCCESS_SAVE_'));
        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $Wechat = M('Wechat')->where(array('id' => $id))->find();
            } else {
                $Wechat = array('create_time' => time(), 'post_count' => 0, 'status' => 1, 'type_id' => $type_id);
            }
            $types = M('WechatType')->where(array('status' => 1))->select();
            $type_id_array[0] = L('_NO_CATEGORY_');
            foreach ($types as $t) {
                $type_id_array[$t['id']] = $t['title'];
            }

            $role_list =  D('Admin/Role')->field('id,title')->order('sort asc')->select();
            $role_list=array_column($role_list, 'title', 'id');
          
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '添加微信' : '修改微信')
                ->data($Wechat)
                ->keyId()
                ->keyText('title','微信名称')
                ->keyText('agentid','应用ID', '用于企业号中多个应用的唯一识别')
                ->keySingleImage('logo', '应用图标')
                ->keySingleImage('ma_logo','应用二维码', '请从微信后台生成')
                ->keyText('description','应用简介', '建议15字内')
                ->keySelect('type', '应用类型', '建议15字内', array(-1=>'企业号',0=>'订阅号',1=>'认证订阅号',2=>'服务号',3=>'认证服务号'))
                ->keySelect('type_id', L('_BOARD_CATEGORY_'), L('_BOARD_CATEGORY_VICE_'), $type_id_array)
                ->keyText('token', 'Token', '请从微信后台生成')
                ->keyText('encodingaeskey','EncodingAESKey', '请从微信后台生成')
                ->keyText('appid', 'AppID', '请从微信后台生成')
                ->keyText('appsecret', 'AppSecret', '请从微信后台生成')
                ->keyText('mchid', '微信支付商户号：MCHID', '请从微信后台生成')
                ->keyText('pay_key', '微信支付秘钥：KEY', '请从微信后台生成')
                ->keySingleFile('apiclient_cert', 'apiclient_cert.pem证书', '请从微信后台生成')
                ->keySingleFile('apiclient_key', 'apiclient_key.pem证书', '请从微信后台生成')
                ->keyText('subscribe_reply', '欢迎语：', '请从微信后台生成')
                ->keyText('noanswer_reply', '自动回复', '请从微信后台生成')
                ->keyText('hr_reply', '员工码返回', '请从微信后台生成')
                ->keyText('wifi_reply', '连接wifi回复：', '请从微信后台生成')
                ->keyText('feedback_card', '客情奖励', '请从微信后台生成')
                ->keyText('sys_work', '应用功能', '请从微信后台生成')


                ->keyText('admin','应用管理员' ,'输入UID，使用英文d逗号分隔')
                ->keySingleUserGroup('default_group', '默认组织','下拉粉丝的是还会分配到该组织')
                ->keySingleUserRoles('default_role', '默认角色','下拉粉丝的时候会分配到该角色')
                
                ->keyStatus()
                ->keyCreateTime()
                ->buttonSubmit(U('editWechat'))->buttonBack()
                ->display();
        }

    }


    public function post($page = 1, $weiapp = null, $r = 20, $nickname = '', $mobile = '')
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname'] = array('like', '%' . $nickname . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($weiapp) $map['weiapp'] = $weiapp;
        $model = M('WechatMember');
        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
        echo   M('WechatMember')->getlastsql();
       
        $totalCount = $model->where($map)->count();


        foreach ($list as &$v) {
         $v['sex'] = ($v['sex'] == 1) ? '男' : '女';  
        }
        //读取微信基本信息
        if ($weiapp) {
            $Wechat = M('Wechat')->where(array('id' => $weiapp))->find();
            $WechatTitle = ' - ' . $Wechat['title'];
        } else {
            $WechatTitle = '';
        }

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title(L('_POST_MANAGE_') . $WechatTitle)
            ->setStatusUrl(U('Wechat/setPostStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->keyId()->keyLink('nickname', L('_TITLE_'), 'Wechat/reply?post_id=###')->key('wechat','所属微信', 'text')
            ->key('mobile','手机', 'text')->key('sex','性别', 'text')
            ->keyCreateTime()->keyUpdateTime()->keyTime('last_reply_time', L('_LAST_REPLY_TIME_'))->keyStatus()->keyDoActionEdit('editPost?id=###')
            ->setSearchPostUrl(U('Admin/Wechat/post'))->search('昵称', 'nickname')->search('手机', 'mobile')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function postTrash($page = 1, $r = 20)
    {
        //显示页面
        $builder = new AdminListBuilder();
        $builder->clearTrash('WechatPost');
        //读取帖子数据
        $map = array('status' => -1);
        $model = M('WechatPost');
        $list = $model->where($map)->order('last_reply_time desc')->page($page, $r)->select();
        $totalCount = $model->where($map)->count();


        $builder->title(L('_REPLY_VIEW_MORE_'))
            ->setStatusUrl(U('Wechat/setPostStatus'))->buttonRestore()->buttonClear('WechatPost')
            ->keyId()->keyLink('title', L('_TITLE_'), 'Wechat/reply?post_id=###')
            ->keyCreateTime()->keyUpdateTime()->keyTime('last_reply_time', L('_LAST_REPLY_TIME_'))->keyBool('is_top', L('_STICK_YES_OR_NOT_'))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function editPost($id = null, $id = null, $title = '', $content = '', $create_time = 0, $update_time = 0, $last_reply_time = 0, $is_top = 0)
    {
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //写入数据库
            $model = M('WechatPost');
            $data = array('title' => $title, 'content' => filter_content($content), 'create_time' => $create_time, 'update_time' => $update_time, 'last_reply_time' => $last_reply_time, 'is_top' => $is_top);
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
                $post = M('WechatPost')->where(array('id' => $id))->find();
            } else {
                $post = array();
            }

            //显示页面
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('_POST_EDIT_') : L('_POST_ADD_'))
                ->keyId()->keyTitle()->keyEditor('content', L('_CONTENT_'))->keyRadio('is_top', L('_STICK_'), L('_STICK_STYLE_SELECT_'), array(0 => L('_STICK_NOT_'), 1 => L('_STICK_IN_BLOCK_'), 2 => L('_STICK_GLOBAL_')))->keyCreateTime()->keyUpdateTime()
                ->keyTime('last_reply_time', L('_LAST_REPLY_TIME_'))
                ->buttonSubmit(U('editPost'))->buttonBack()
                ->data($post)
                ->display();
        }

    }

    public function setPostStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('WechatPost', $ids, $status);
    }

    public function reply($page = 1, $post_id = null, $r = 20)
    {
        $builder = new AdminListBuilder();

        //读取回复列表
        $map = array('status' => array('EGT', 0));
        if ($post_id) $map['post_id'] = $post_id;
        $model = M('WechatPostReply');
        $list = $model->where($map)->order('create_time asc')->page($page, $r)->select();
        $totalCount = $model->where($map)->count();

        foreach ($list as &$reply) {
            $reply['content'] = op_t($reply['content']);
        }
        unset($reply);
        //显示页面

        $builder->title(L('_REPLY_MANAGER_'))
            ->setStatusUrl(U('setReplyStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->keyId()->keyTruncText('content', L('_CONTENT_'), 50)->keyCreateTime()->keyUpdateTime()->keyStatus()->keyDoActionEdit('editReply?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function replyTrash($page = 1, $r = 20, $model = '')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        //读取回复列表
        $map = array('status' => -1);
        $model = M('WechatPostReply');
        $list = $model->where($map)->order('create_time asc')->page($page, $r)->select();
        foreach ($list as &$reply) {
            $reply['content'] = op_t($reply['content']);
        }
        unset($reply);
        $totalCount = $model->where($map)->count();

        //显示页面

        $builder->title(L('_REPLY_TRASH_'))
            ->setStatusUrl(U('setReplyStatus'))->buttonRestore()->buttonClear('WechatPostReply')
            ->keyId()->keyTruncText('content', L('_CONTENT_'), 50)->keyCreateTime()->keyUpdateTime()->keyStatus()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function setReplyStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('WechatPostReply', $ids, $status);
    }

    public function editReply($id = null, $content = '', $create_time = 0, $update_time = 0, $status = 1)
    {
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //写入数据库
            $data = array('content' => filter_content($content), 'create_time' => $create_time, 'update_time' => $update_time, 'status' => $status);
            $model = M('WechatPostReply');
            if ($isEdit) {
                $result = $model->where(array('id' => $id))->save($data);
            } else {
                $result = $model->add($data);
            }

            //如果写入出错，则显示错误消息
            if ($result === false) {
                $this->error($isEdit ? L('_FAIL_EDIT_') : L('_TIP_CREATE_SUCCESS_'));
            }
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_TIP_CREATE_SUCCESS_'), U('reply'));

        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //读取回复内容
            if ($isEdit) {
                $model = M('WechatPostReply');
                $reply = $model->where(array('id' => $id))->find();
            } else {
                $reply = array('status' => 1);
            }

            //显示页面
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('_REPLY_EDIT_') : L('_REPLY_CREATE_'))
                ->keyId()->keyEditor('content', L('_CONTENT_'))->keyCreateTime()->keyUpdateTime()->keyStatus()
                ->data($reply)
                ->buttonSubmit(U('editReply'))->buttonBack()
                ->display();
        }

    }


}
