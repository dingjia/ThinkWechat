<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */

namespace Admin\Controller;
use Qwechat\Sdk\TPWechat;
use Qwechat\Sdk\errCode;

use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminSortBuilder;
use Admin\Builder\AdminTreeListBuilder;

class QwechatController extends AdminController
{
    protected $QwechatModel;
  
    function _initialize()
    {
        $this->QwechatModel = D('Qwechat/Qwechat');
        $this->status =array('-1'=>'离职','1'=>'实习','2'=>'转正','3'=>'股东');
        $this->leave =array('1'=>'辞退','2'=>'干得不爽','3'=>'工资不够','4'=>'无理由自离');
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
    }


    public function index()
    {
        redirect(U('Wechat'));
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

        

        $admin_config->title('企业号基本配置')
            ->data($data)
            ->keyText('APPID','CorpID', '来自企业号微信后台')
            ->keyText('APPSECRET', 'Secret', '来自企业号微信后台')
            ->group('权限配置', 'APPID,APPSECRET');
           
        $admin_config->buttonSubmit('', L('_SAVE_'))->display();
    }

    public function editSite()
    {
        
        $model = M('QwechatSite');
        $map['aid']=session('user_auth.aid');
        $site=M('QwechatSite')->where($map)->find();
        $isEdit = $site ? true : false;
        if (IS_POST) {
          
              //写入数据库
                if ($isEdit) {
                    $_POST['aid']=session('user_auth.aid');
                    $data = $model->create();
                    //获取用户到用户库
                  
                    $result = $model->where($map)->save();
                    
                    if ($result === false) {
                        $this->error(L('_FAIL_EDIT_'));
                    }
                } else {
                    //生成订单
                   
                    $_POST['aid']=session('user_auth.aid');
                    $data = $model->create();
                    $result = $model->add();
                  
                    if (!$result) {
                        $this->error(L('_ERROR_CREATE_FAIL_'));
                    }
                }
           
         
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_SUCCESS_SAVE_'),U('editSite'));
           } else {
            //判断是否为编辑模式
            $isEdit = $site ? true : false;
            if ($isEdit) {
                
            } else {
                $site = array('create_time' => time());
            }
            
           
           
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '修改集团信息' : '添加集团信息')
                ->keyText('name','集团名称','','',61)->keyText('motto','企业口号','','',62)
                ->keySingleImage('logo','品牌logo')
                ->keyText('mobile','品牌电话','','',61)->keyText('qq','品牌QQ','','',62)
                ->keyText('shopowner','老总姓名')->keyText('mobile_shopowner','老总电话','','',61)->keyText('qq_shopowner','老总QQ','','',62)->keyText('address','门店地址')->keyLoctionMap('coordinate','门店坐标')
                ->keyEditor('about','关于品牌')->keyEditor('hr','招聘信息')->keyMultiImage('pics','图册')
                ->group('基本信息','name,motto,logo,mobile,qq,address,coordinate')
                ->group('网站信息','about,hr,pics')
                ->group('老板信息','shopowner,mobile_shopowner,qq_shopowner')
                ->data($site)
                ->buttonSubmit(U('editSite'))->buttonBack()
                ->display();
             }

    }

    public function Wechat($page = 1, $r = 20)
    {
        //读取数据
        $map['aid'] = array('eq',session('user_auth.aid'));
        $map['status'] = array('GT', -1);
        $model = M('qwechat');
        $list = $model->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = $model->where($map)->count();
      
        //显示页面
        $builder = new AdminListBuilder();
        $builder
            ->title('应用管理')
            ->buttonNew(U('Qwechat/editWechat'))
            ->setStatusUrl(U('Qwechat/setWechatStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->ajaxButton(U('Qwechat/getAgents'),array('status' => $status),'下拉企业应用')
           
            ->keyId()->keyLink('name','应用', 'Qwechat/post?Wechat_id=###')
            ->keyText('agentid', '应用ID')
            ->keyCreateTime()->keyText('post_count', L('_THEME_COUNT_'))->keyStatus()
            ->keyDoActionEdit('menu?id=###','菜单')
            ->keyDoActionEdit('editWechat?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

     public function getAgents($ids=array())
    {
        $aids = I('post.ids');
        ignore_user_abort (true);
        set_time_limit(0);
      
        $options =D('Qwechat/Qwechat')->GetOptions($aids[0]);

        $weObj = new TPWechat( $options['base']);
        $ret=$weObj->checkAuth();
        if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
        $agents=$weObj->getAgents();
       
       
        if (!$agents)  $this->error(ErrCode::getErrText($weObj->errCode));
        foreach ($agents['agentlist'] as  $agent) {
            $agent_info=$weObj->getAgent($agent['agentid']);
            if (!$agent_info)  $this->error(ErrCode::getErrText($weObj->errCode));
            $back= D('Qwechat/Qwechat')->updateAgent($agent_info);
            $back=="add"?$add_um+=1:$update_um+=1;
        }
        $this->success('拉取'.count($agents['agentlist']).'企业号应用,'.'新增'.$add_um.',更新'.$update_um);
      
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

    public function editWechat($id = null, $name = '', $create_time =0, $close = 0)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
           
            if ($this->QwechatModel->editData()) {
                //缓存
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('wechat'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').$this->QwechatModel->getError());
            }
           
             
        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $Wechat = $this->QwechatModel->info($id);
                $Wechat['url']='http://'.$_SERVER['HTTP_HOST']. U('qwechat/api/index',array('appid'=>$id));
            } else {
                $Wechat = array('create_time' => time());

            }
            
          
            $robs = D('News/NewsRob')->getRobs("aid=0 or aid=".session('user_auth.aid'),'title,id');
            $robs =array_column($robs, 'title', 'id');

            $wechat_rob = D('News/NewsRob')->field('id,title as value')->where('type=0 and status>=0 and (wechat_type=0 or wechat_type=1)')->select();
            $wechat_rob =array_column($wechat_rob, 'value', 'id');
            // $robs= $this->defaut_rob+$robs;
           
            // dump(S('qwechat'.$id)); die;
            
          
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '修改应用' : '添加应用')
                ->data($Wechat)
                ->keyId()
                ->keyReadOnly('agentid','应用ID', '')
                ->keyText('name','应用名称')
                ->keyText('description','应用简介', '建议15字内')
                ->keyReadOnly('url', 'url', '系统自动生成,填写到微信后台')
                ->keyText('token', 'Token', '')
                ->keyText('encodingaeskey','EncodingAESKey', '')
                ->keyText('appid', 'AppID', '')
                ->keyText('appsecret', 'AppSecret', '')
               

                ->keyChosenOne('subscribe_rob', '关注回复', '', $robs)
                ->keyChosenOne('auto_rob', '自动回复', '', $robs)
                ->keyChosenOne('enter_agent_rob', '进入应用', '', $robs)
                ->keyChosenOne('scan_waitmsg_rob', '扫码带提示', '使用微信扫一扫带提示返回的机器人', $robs)
                ->keyText('tail', '小尾巴', '文字回复后面统一的小尾巴')
                ->keyCheckBox('wechat_rob', '应用命令', '可以在此应用中处理的命令',$wechat_rob)

                ->group('基本信息', 'id,agentid,name,description')
                ->group('微信配置', 'url,token,encodingaeskey,appid,appsecret')
                // ->group('支付配置', 'mchid,pay_key,apiclient_cert,apiclient_key')
                ->group('自动回复', 'subscribe_rob,auto_rob,enter_agent_rob,scan_waitmsg_rob,tail')
                ->group('特殊应用', 'wechat_rob')
           
                

                 
                ->buttonSubmit(U('editWechat'))->buttonBack()
                ->display();

               
        }

    }



    public function shops($page = 1, $appid = null, $r = 20, $customer = '', $mobile = '',$platform ='',$status ='')
    {
       
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($customer != '') {
            $map['customer'] = array('like', '%' . $customer . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($appid) $map['appid'] = $appid;
        if ($platform) $map['platform'] = $platform;
        if ($status) $map['status'] = $status;
        $list = D('QwechatShop')->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = D('QwechatShop')->where($map)->count();


        foreach ($list as &$v) {
         $v['platform'] = $this->platform[ $v['platform']];
         $v['status'] = $this->status[ $v['status']];  
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Qwechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('门店管理' . $WechatTitle)

            ->buttonNew(U('Qwechat/editShop'))
            ->setStatusUrl(U('Qwechat/setShopStatus'))->buttonDelete()
           
            ->setSelectPostUrl(U('Admin/Qwechat/shops'))
            ->setSearchPostUrl(U('Admin/Qwechat/shops'))->search('门店名', 'name')->search('手机', 'mobile')
            ->keyId()->keyText('name','门店')
            ->keyText('mobile', '电话')->keyText('address', '地址')
            ->keyCreateTime()->keyDoActionEdit('editShop?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

     public function setShopStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('QwechatShop', $ids, $status);
    }


     public function editShop($id = null, $name = '', $create_time =0, $status = 0,  $logo = 0)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $model = M('QwechatShop');
             // dump($_POST);

                //写入数据库
                if ($isEdit) {
                    $_POST['aid']=session('user_auth.aid');
                    $data = $model->create();
                    //获取用户到用户库
                  
                    $result = $model->where(array('id' => $id))->save();
                    
                    if ($result === false) {
                        $this->error(L('_FAIL_EDIT_'));
                    }
                } else {
                    //生成订单
                   
                    $_POST['aid']=session('user_auth.aid');
                    $data = $model->create();
                    $result = $model->add();
                  
                    if (!$result) {
                        $this->error(L('_ERROR_CREATE_FAIL_'));
                    }
                }
           
         
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_SUCCESS_SAVE_'),U('shops'));
           } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            if ($isEdit) {
                $orders =  M('QwechatShop')->where(array('id' => $id))->find();
            } else {
                $orders = array('create_time' => time());
            }
            $map['aid']=session('user_auth.aid');
            $map['parentid']= 1;
            $departments = M('QwechatDepartment')->field('id,name')->where($map)->select();
            $departments =array_column($departments, 'name', 'id');

            
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '修改门店' : '添加门店')
                ->keyId()
                ->keyText('name','门店名称','','',61)->keySelect('department_id','绑定组织','',$departments,62)->keyText('mobile','联系电话','','',61)->keyText('qq','店里QQ','','',62)
                ->keyText('shopowner','店长姓名')->keyText('mobile_shopowner','店长电话','','',61)->keyText('qq_shopowner','店长QQ','','',62)->keyText('address','门店地址')
                ->keyLoctionMap('coordinate','门店坐标')
                ->data($orders)
                ->buttonSubmit(U('editShop'))->buttonBack()
                ->display();
             }

    }

   

    public function menu($id = 0){
        //显示页面
        $builder = new AdminTreeListBuilder();
        $map['status']=array('gt', -1);
        $map['appid']=$id;

        $tree = D('Qwechat/QwechatMenu')->getTree(0, 'id,title,sort,linkurl,pid,status',$map);
        
       
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

    public function addMenu($id = 0, $pid = 0,$appid=0)
    {

        $title=$id?"编辑":"新增";
        $menuMod = D('Qwechat/QwechatMenu');
        if (IS_POST) {

            if ($menuMod->editData()) {
                $this->success($title.'成功。', U('menu',array('id'=>$_POST['appid'])));
            } else {
                $this->error($title.'失败!'.$menuMod->getError());
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
            if ($pid){
                $menu = $menuMod->find($pid);
                $appid=$data['appid']=$menu['appid'];
            }
          
            $categorys = $menuMod->where(array('pid'=>0,'appid'=>$appid,'status'=>array('egt',0)))->select();
           
            $opt = array();
            foreach ($categorys as $category) {
                $opt[$category['id']] = $category['title'];
            }

            $robs = D('News/NewsRob')->getRobs("aid=0 or aid=".session('user_auth.aid'),'title,id');
            $robs =array_column($robs, 'title', 'id');
            $robs=$this->defaut_rob+$robs;


            $builder->title($title.'微信菜单')
                ->data($data)
                ->keyId()->keyReadOnly('appid','所属微信')->keyText('title', '标题')
                ->keySelect('pid', '父分类', '选择父级分类', array('0' => '顶级菜单') + $opt)->keyDefault('pid',$pid)
                ->keyText('linkurl','链接地址')->keyDefault('sort',0)
                ->keyChosenOne('key', '机器人', '', $robs)
                ->keyInteger('sort','排序')->keyDefault('sort',0)
                ->keyStatus()->keyDefault('status',1)
                ->buttonSubmit(U('addMenu'))->buttonBack()
                ->display();
        }

    }

   
     public function setMenuStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('QwechatMenu', $ids, $status);
    }


    public function sendmenutoWechat($appid=0){
        $options =D('Qwechat/Qwechat')->GetOptions($appid);
        $weObj = new TPWechat( $options['base']);
        $ret=$weObj->checkAuth();
        if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
       
       
        $tree = D('Qwechat/QwechatMenu')->getTree(0, 'id,title,linkurl,key,pid',array('appid'=>$appid));
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
        // dump( $send);die;

       $rs=$weObj->createMenu($send,$options['agentid']);

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

     public function department($page = 1,  $r = 100)
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($name != '') {
            $map['name'] = array('like', '%' . $name . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
       
        // $model = M('QwechatDepartment');
        // $list = $model->where($map)->order('id desc')->page($page, $r)->select();
        // $totalCount = $model->where($map)->count();


       
       

        //显示页面  /
        $builder = new AdminTreeListBuilder();
        $attr['class'] = 'btn ajax-post';
        $attr['target-form'] = 'ids';

        $tree = D('Qwechat/QwechatDepartment')->getTree(1, 'id,name as title,`order` as sort,parentid as pid,status');
        
       

        $builder->title('部门管理')
            ->buttonNew(U('Qwechat/addDepartment'))
            ->buttonNew(U('Qwechat/getDepartment'),'下拉部门')
            ->setModel('Department')
            ->data($tree)
            ->display();
    }

     /**
     * 设置部门分类状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function setDepartmentStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        if($status==-1){
             $id = array_unique((array)$ids);
           
            //链接微信
            $options =D('Qwechat/Qwechat')->GetOptions();
            $weObj = new TPWechat( $options['base']);
            $ret=$weObj->checkAuth();
            if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
            //删除线上部门
            foreach ($id as  $value) {
                $ret=$weObj->deleteDepartment($value);
                if (!$ret)$this->error(ErrCode::getErrText($weObj->errCode));
            }
           $rs=M('QwechatDepartment')->where(array('id' => array('in', $id)))->save(array('status' => $status));
        }
        $builder->doSetStatus('QwechatDepartment', $ids, $status);
    }


     public function getDepartment()
    {
         $builder = new AdminConfigBuilder();
            $builder->title( '下拉部门' )
                    ->keyProgressbar()->keyUpdateTime()
                
                ->buttonSubmit(U('editMember'))->buttonBack()
                ->data($post)
                ->display();
        
        set_time_limit(0);

            //验证，并下拉部门
            
                 $options =D('Qwechat/Qwechat')->GetOptions();
                 $weObj = new TPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getDepartment();
                 if (!$list)$this->error(ErrCode::getErrText($weObj->errCode));
                
                 $departments=$list['department'];
                 $total=count($departments);
             
            
           
            //处理数据。开始循环处理部门数据
            
        foreach ($departments as $key=> $department) {
          
            $back= D('Qwechat/QwechatDepartment')->updateDepartment($department);
            
            $msg=($key+1).'/'.$total.'：'.($back=="add"?"[添加]":"[更新]").' 部门'.$department['name'].'</br>';
            show_msg($msg,'',floor(($key+1)/$total*100));
            ob_flush(); 
            flush(); 

        }
        show_msg('1秒后将跳转到部门列表！');
        echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('department')."'},1000)</script>";
    
    }

      public function addDepartment($id = null, $name = '')
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $model = M('QwechatDepartment');
            //链接微信
            $options =D('Qwechat/Qwechat')->GetOptions();
            $weObj = new TPWechat( $options['base']);
            $ret=$weObj->checkAuth();
            if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
            //处理数据
           
           $_POST['department']=array_filter($_POST['department']);
           $_POST['level']=count($_POST['department'])+1;
            
           if( $_POST['level']==4) array_pop($_POST['department']); 
           $_POST['parentid']=end($_POST['department']);
          
          
                //写入数据库
                if ($isEdit) {
                    $data['id'] = $id;
                    if (!$_POST['parentid'])$this->error('部门必须选择');
                    $data = $model->create();
                    $result = $model->where(array('id' => $id))->save();
                    if ($result === false) {
                        $this->error(L('_FAIL_EDIT_'));
                    }
                    //更新企业微信
                     $ret=$weObj->updateDepartment($data);
                     if (!$ret)$this->error(ErrCode::getErrText($weObj->errCode));
                } else {
                    $_POST['aid']=session('user_auth.aid');
                    if (!$_POST['parentid'])$_POST['parentid']=1;
                    $data = $model->create();
                    //添加一个部门
                    $ret=$weObj->createDepartment($data);
                    if (!$ret)$this->error(ErrCode::getErrText($weObj->errCode));
                    $data['id']=$ret['id'];
                    $result = $model->add($data);
                    if (!$result) {
                        $this->error(L('_ERROR_CREATE_FAIL_'));
                    }
                    
            
                }
            
          
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_SUCCESS_SAVE_'),U('department'));
        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $map['aid']=session('user_auth.aid');
            $map['status']= array('EGT', 0);
            $departments = M('QwechatDepartment')->field('id,name as title,`order` as sort,parentid as pid,status')->where($map)->select();
            // $tree = D('Qwechat/QwechatDepartment')->getTree(1, 'id,name as title,`order` as sort,parentid as pid,status');
            // dump( $tree);die;
           
           //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $department = M('QwechatDepartment')->where(array('id' => $id))->find();
                $departments['id']=$id;
            } else {
                $department = array('create_time' => time());
            }
            
            // $list = $this->field('id,name as title,`order` as sort,parentid as pid,status')->where($map)->order('`order`')->select(); 
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '修改部门' : '添加部门')
                ->data($department)

                ->keyId()
                ->keyText('name','部门名称')
                ->keyText('order','排序')
                ->keyTree('parentid','父部门','',$departments)
                ->buttonSubmit()->buttonBack()
                ->display();
   
        }

    }

     //获取部门信息形成下拉框
    public function ajaxDepartment($pid = 1, $id=''){
            $map['parentid']=$pid;
            $map['aid']=session('user_auth.aid');
            $map['status']= array('EGT', 0);
            $list=  M('QwechatDepartment')->field( 'id,name ')->where($map)->select();
           
            if ($list) {
            $data = "<option value =''>-选择部门-</option>";
            foreach ($list as $k => $vo) {
                $data .= "<option ";
                if( $id == $vo['id'] ){
                    $data .= " selected ";
                }
                $data .= " value ='" . $vo['id'] . "'>" . $vo['name'] . "</option>";
            }
             $this->ajaxReturn($data);
            }
        
    }

    


    public function member($page = 1,  $r = 20, $pid = 0,$shopid = '',$name = '', $mobile = '', $status = '',$gender=0,$birthday='',$start_time='',$end_time='')
    {
        $model = M('QwechatMember');
        //同步旧数据,需要的时候开启
        // $news = $model->where($map)->order('id desc')->select();
        // foreach ($news as $key => $new) {
        //    $old = M('Admin')->where(array('phone'=>$new['mobile']))->find();
        //    $updatedata['idcard']=$old['idcard'];
        //    $updatedata['bankcard']=$old['bankcard'];
        //    $updatedata['amount']=$old['amount'];
        //    $updatedata['idcard']=$old['idcard'];
        //    $updatedata['birthday']=$old['birthday'];
        //    $updatedata['joinday']=$old['joinday'];
        //    $updatedata['calendar']=$old['calendar'];
        //    $updatedata['address']=$old['address'];
        //    $updatedata['position']=$old['career'];
        //    $model->where(array('mobile'=>$new['mobile']))->save($updatedata);
        //    $updatedata=array();

        // }
       
       
        $map['status'] = array('EGT',0);
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($pid != '')  $map['department'] = array('like', '%' . $pid . '%');
        if ($name != '')  $map['name|userid|id'] = array('like', '%' . $name . '%');
        if ($mobile != '')  $map['mobile'] = array('like', '%' . $mobile . '%');
        if ($status != '')   $map['status'] = $status;
        if ($shopid != '') $map['shopid'] = $shopid;
        if ($gender != '') $map['gender'] = $gender;
        if ($birthday ==1)  $map['_string'] = "FROM_UNIXTIME(birthday, '%m-%d') ='". date('m-d')."'";
        if ($birthday ==2)  $map['_string'] = "FROM_UNIXTIME(birthday, '%m') ='". date('m')."'";
        if ($start_time != '' and $end_time != '') {
            $map['joinday'] = array('between', array($start_time,$end_time));
        }

        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
        $totalCount = $model->where($map)->count();

        $members=D('Qwechat/QwechatMember')->getBirthday($day);


        foreach ($list as &$v) {
         $v['gender'] = ($v['gender'] == 1) ? '男' : '女';
         
         if($v['calendar']==1) {
             $nongli = date('Y-m-d',$v['birthday'] );
             $yangli=D('Common/Lunar')->getLar($nongli,1);
             $v['birthday'] = date('Y-m-d',$yangli ) ;
         }else{
             $v['birthday'] = date('Y-m-d',$v['birthday'] );
         }
             $v['birthday'] =$v['birthday'].birthdayReminder( $v['birthday'],7);


         $v['status']=$this->status[$v['status']];
         $v['joinday'] = date('Y-m-d',$v['joinday'] );
         }

        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('员工管理')
            ->buttonNew(U('Qwechat/editMember'))->setStatusUrl(U('Qwechat/setMemberStatus'))->buttonDelete('','离职')
            ->buttonNew(U('Qwechat/getUserList'),'下拉员工')
            ->buttonNew(U('Qwechat/cvs'),'导出')
            
            // ->buttonNew(U('Qwechat/memberHealthy'),'员工体检')
            ->buttonModalPopup(U('Qwechat/send'),array('status' => 0),'发送消息')
            ->setSelectPostUrl(U('Qwechat/member'))
             ->select('','shopid','select','','','',array_merge(array(array('appid'=>0,'value'=>'全部分店')),$shops))
             ->select('','status','select','','','',array(array('id'=>'','value'=>'所有在职'),array('id'=>1,'value'=>'实习'),array('id'=>2,'value'=>'转正'),array('id'=>3,'value'=>'股东')))
             ->select('','gender','select','','','',array(array('id'=>'','value'=>'选择性别'),array('id'=>1,'value'=>'男'),array('id'=>2,'value'=>'女')))
             ->select('','birthday','select','','','',array(array('id'=>'','value'=>'选择生日'),array('id'=>1,'value'=>'今天生日'),array('id'=>2,'value'=>'本月生日')))
           
         
             ->keyLink('name', '姓名', 'Wechat/reply?post_id=###')->keyText('gender','性别')
            ->keyText('mobile','手机')->key('idcard','身份证', 'text')->keyText('bankcard','银行卡')
             ->keyText('amount','余额')->keyText('birthday','生日(已转换)')->keyText('joinday','入职')
            ->keyText('status','状态')->keyDoActionEdit('editMember?id=###')
            ->setSearchPostUrl(U('Admin/Qwechat/member'))->search('姓名', 'name')->search('手机', 'mobile')->search('开始时间', 'start_time','date')->search('结束时间', 'end_time','date')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
      }

       /**员工回收站
     * @param int $page
     * @param int $r
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function memberTrash($page = 1, $r = 20,$shopid='',$name = '', $mobile = '',$leave_type='',$start_time='',$end_time='')
    {
        $model = M('QwechatMember');
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        //读取微博列表
        $map = array('status' => -1);
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($name != '')  $map['name|userid|id'] = array('like', '%' . $name . '%');
        if ($mobile != '')  $map['mobile'] = array('like', '%' . $mobile . '%');

        if ($start_time != '' and $end_time != '') {
            $map['create_time'] = array('between', array($start_time,$end_time));
        }

        if ($leave_type != '')  $map['leave_type'] =$leave_type;
        if ($shopid != '')  $map['shopid'] =$shopid;

        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
        $totalCount = $model->where($map)->count();
        foreach ($list as &$v) {
          $v['gender'] = ($v['gender'] == 1) ? '男' : '女';
          $v['status']=$this->status[$v['status']];
          $v['leave_type']=$this->leave[$v['leave_type']];
         
        }

        //显示页面
        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
        $builder->title('离职员工')->buttonRestore()
            ->setSelectPostUrl(U('Qwechat/memberTrash'))
            ->select('','shopid','select','','','',array_merge(array(array('id'=>'','value'=>'全部分店')),$shops))
            ->select('','leave_type','select','','','',array(array('id'=>0,'value'=>'离职原因'),array('id'=>1,'value'=>'辞退'),array('id'=>2,'value'=>'干得不爽'),array('id'=>3,'value'=>'工资不够'),array('id'=>4,'value'=>'无理由自离')))
            ->keyId()->keyLink('name', '姓名', 'Wechat/reply?post_id=###')->keyText('gender','性别')
            ->keyText('mobile','手机')->keyText('leave_type','类型')->keyText('leave_why','原因')->keyTime('leaveday','离职日期')
           
           
            ->keyText('status','状态')->keyDoActionEdit('editMember?id=###')
            ->setSearchPostUrl(U('Admin/Qwechat/memberTrash'))->search('姓名', 'name')->search('手机', 'mobile')->search('开始时间', 'start_time','date')->search('结束时间', 'end_time','date')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


     public function cvs()
    {

        $aIds=I('ids',array());
        if ($aIds){
            $map['id']=array('in',$aIds);
        }else{
            $map['aid'] = array('eq',session('user_auth.aid'));
            $map['status'] = array('EGT',0);
        }


        $dataList=M('QwechatMember')->where($map)->select();

        if(!$dataList){
            $this->error(L('_NO_DATA_WITH_EXCLAMATION_'));
        }

        $data="id,姓名,性别,电话,QQ,生日,企业,分店,岗位,身份证,银行卡,入职日期,状态\n";
       // dump($dataList['0']);exit;

        foreach ($dataList as $val) {
            $val['gender'] = ($val['gender'] == 1) ? '男' : '女';
            $val['status']=$this->status[$val['status']];
            $val['joinday']=time_format($val['joinday'],'Y-m-d');
            $val['birthday']=time_format($val['birthday'],'Y-m-d');
            $val['create_time']=time_format($val['create_time']);
            $data.=$val['id'].",".$val['name'].",".$val['gender'].",".$val['mobile'].",".$val['qq'].",".$val['birthday'].",".$val['aid'].",".$val['shopid'].",".$val['position'].",".$val['idcard'].",".$val['bankcard'].",".$val['joinday'].",".$val['status']."\n";
        }

        $data=iconv('utf-8','gb2312',$data);
        $filename = date('Ymd').'.csv'; //设置文件名
        $this->export_csv($filename,$data); //导出
    }

    private function export_csv($filename,$data) {
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        header("Content-type:application/vnd.ms-excel;charset=utf-8");
        echo $data;
    }



     public function getUserList()
    {
        
        ignore_user_abort (true);
        set_time_limit(0);

        $builder = new AdminConfigBuilder();
            $builder->title( '下拉员工' )
           ->keyProgressbar()->display();
        
      
        $map['agentid']=0;
        $map['aid']=session('user_auth.aid');
        $agentid = M("Qwechat")->where($map)->getfield('id');
        $options =D('Qwechat/Qwechat')->GetOptions($agentid);
      
 
            //验证，并下拉用户
            
                 $weObj = new TPWechat( $options['base']);
                 $ret=$weObj->checkAuth();
                 if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
                 $list=$weObj->getUserListInfo(1,1,0);
                 if (!$list)$this->error(ErrCode::getErrText($weObj->errCode));
                 $members=$list['userlist'];
                 $total=count($members);
             
            
           
            //处理数据。开始循环处理用户数据
            
        foreach ($members as $key=>  $member) {
            $back= D('Qwechat/QwechatMember')->updateMember($member);
            $msg=($key+1).'/'.$total.'：'.($back=="add"?"[添加]":"[更新]").' 员工'.$member['name'].'</br>';
            show_msg($msg,'',floor(($key+1)/$total*100));
            ob_flush(); 
            flush(); 
           
        }

        show_msg('1秒后将跳转到部门列表！');
        echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('member')."'},1000)</script>";
    
       
    }

     public function memberHealthy()
    {
        
        ignore_user_abort (true);
        set_time_limit(0);

        $builder = new AdminConfigBuilder();
            $builder->title( '员工体检' )
           ->keyProgressbar()->display();
        
      
      
        $map['aid']=session('user_auth.aid');
        
        $members =D('Qwechat/QwechatMember')->where($map)->select();
      
        //处理数据。开始循环处理用户数据
        $total=count($members);    
        foreach ($members as $key=>  $member) {
            // if (!$member['shopid'] || !$member['qq'] || !$member['department'] || !$member['idcard'] ||  !$member['birthday']){
            if (!$member['shopid'] ) {
                $msg=($key+1).'/'.$total.':'.$member['id'].$member['name'].'分店！</br>';
                show_msg($msg,'',floor(($key+1)/$total*100));
            }
            if (!$member['qq'] ) {
                $msg=($key+1).'/'.$total.':'.$member['id'].$member['name'].'QQ！</br>';
                show_msg($msg,'',floor(($key+1)/$total*100));
            }
            if (!$member['idcard'] ) {
                $msg=($key+1).'/'.$total.':'.$member['id'].$member['name'].'身份证！</br>';
                show_msg($msg,'',floor(($key+1)/$total*100));
            }
            if (!$member['bankcard'] ) {
                $msg=($key+1).'/'.$total.':'.$member['id'].$member['name'].'银行卡！</br>';
                show_msg($msg,'',floor(($key+1)/$total*100));
            }
            if (!$member['birthday'] ) {
                $msg=($key+1).'/'.$total.':'.$member['id'].$member['name'].'银行卡！</br>';
                show_msg($msg,'',floor(($key+1)/$total*100));
            }


            ob_flush(); 
            flush(); 
           
        }

        show_msg('体检完毕！请及时修正');
        // echo "<script type=\"text/javascript\">setTimeout(function(){location.href='".U('member')."'},1000)</script>";
    
       
    }

    
    public function editMember($id = null)
    {
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //写入数据库
            $model = M('QwechatMember');

            //链接微信
            $options =D('Qwechat/Qwechat')->GetOptions();
            $weObj = new TPWechat( $options['base']);
            $ret=$weObj->checkAuth();

            if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
           
            if ($isEdit) {
                
                $data = $model->create();  

              
                //更新微信云端用户
                if ($data['status']==-1){  //如果离职，则删除微信
                     // $ret=$weObj->deleteUser($data['userid']);
                }else{
                $ret=$weObj->updateUser($data);
                if (!$ret)$this->error(ErrCode::getErrText($weObj->errCode).$weObj->errCode);
                //格式化部门
                }
                $data['department']=implode(',',$data['department']);
               
                $result = $model->where(array('id' => $id))->save($data);

            } else {
                //更新微信
                if($_POST['department'])$_POST['department']=implode(',',$_POST['department']);
                $data = $model->create();
               
                if (!$data['department']||!$data['name'] || !$data['mobile'] )$this->error('姓名、电话和部门必须填写');
                $data['userid']=$data['mobile'] ;
                $data['aid']=$options['aid']?$options['aid']:session('user_auth.aid');
                // dump($data );die;
                //更新微信云端用户
                
                $data['department']=explode(',',$data['department']);
                $ret=$weObj->createUser($data);
                if (!$ret)$this->error(ErrCode::getErrText($weObj->errCode));
                $result = $model->add($data);

              
            }
            //如果写入不成功，则报错
            if ($result === false) {
                $this->error($isEdit ? L('_FAIL_EDIT_') : L('_TIP_CREATE_SUCCESS_'));
            }
            //返回成功信息
            // $data = $model->where(array('id'=>$result))->save(array('userid'=>$result));
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_TIP_CREATE_SUCCESS_'));
        } else {
            //判断是否在编辑模式
            $isEdit = $id ? true : false;
            if ($isEdit) {
                $post = M('QwechatMember')->where(array('id' => $id))->find();
                $post['department'] = explode(',', $post['department']);

            } else {
                $post = array();
            }
          //获取部门
            $map['aid']=session('user_auth.aid');
            $map['status']= array('EGT', 0);
            $departments = M('QwechatDepartment')->field('id,name')->where($map)->select();

           //获取分点
           //获取部门
           
            $shops = M('QwechatShop')->field('id,name')->where($map)->select();
            $shops =array_column($shops, 'name', 'id');

            //显示页面
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? '修改员工' : '添加员工')->suggest(($post['status']>-1 and $isEdit)?'':'该员工处于离职状态')
                ->keyId()->keyText('name','姓名','','',61)->keyText('nickname','昵称','','',62)->keyReadOnly('weixinid','微信号','',61)->keyReadOnly('userid','唯一ID','',62)->keyText('mobile','手机','','',61)->keyText('qq','QQ','','',62)->keyText('email','邮箱','','',61)
                ->keyText('idcard','身份证','','',62)->keyText('bankcard','银行卡','','',61)->keyText('amount','余额','','',62)->keyRadio('gender','性别','', array(1 => '男性', 2 => '女性'),61)->keyRadio('marriage','婚否','', array(0 => '未婚', 1 => '已婚'),62)
                ->keyTime('joinday','入职日期','','date',61)->keySelect('join_from','来自哪里','',array(1=>'线下招聘',2=>'熟人介绍',3=>'微信',4=>'全程/智联',5=>'58赶集'),62)
                ->keyTime('birthday','用户生日','','date',61) ->keyRadio('calendar','历法','', array(0 => '阳历', 1 => '农历'),62)->keyAddress('address','户籍地址','',array('province'=>$post['province'],'city'=>$post['city'],'district'=>$post['district'],'community'=>$post['community']))
                ->keySelect('shopid','所在门店','',$shops)
                ->keyChosen('department','部门','',$departments)
                ->keyText('position','岗位','','');
            if ($post['status']>-1){
                $builder->keySelect('status','员工状态','',array('1'=>'实习','2'=>'转正','3'=>'股东'));
            }else{
                $builder->keyHidden('status','员工状态');
            }
            $builder->keyTime('leaveday','离职日期','','date',61)->keySelect('leave_type','离职分类','',$this->leave,62)->keyText('leave_why','离职原因')
                ->keyEditor('description','对内备注') ->keyText('motto','座右铭')->keyEditor('out_description','对外描述');
               
            if(is_administrator())$builder->keyText('can_aid','切换集团','用于超级管理员分配企业号数据权限');

            $builder->buttonSubmit(U('editMember'))->buttonBack();

            $builder->group('基本信息','id,name,nickname,mobile,qq,email,gender,birthday,calendar,idcard,bankcard,amount,marriage,address,shopid,department,position,status')
                    ->group('入职信息','joinday,join_from,')
                    ->group('培训信息','description,motto,out_description')
                    ->group('离职信息','leaveday,leave_type,leave_why')
                    ->group('系统信息','weixinid,userid,can_aid');
                    
             
           
                $builder->data($post)
                ->display();
        }

    }

    public function setMemberStatus($ids, $status)
    {
        if ($status==-1){

         //链接微信
            $options =D('Qwechat/Qwechat')->GetOptions();
            $weObj = new TPWechat( $options['base']);
            $ret=$weObj->checkAuth();
             if (!$ret)  $this->error(ErrCode::getErrText($weObj->errCode));
            foreach ($ids as  $id) {
              $member =D('Qwechat/QwechatMember')->info($id);  
              $ret=$weObj->deleteUser($member['userid']);
            }
              if (!$ret)$this->error(ErrCode::getErrText($weObj->errCode));
        }
        $builder = new AdminListBuilder();
        $builder->doSetStatus('QwechatMember', $ids, $status);
    }

//消息管理
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
       
        $model = M('QwechatNotice');
        $list = $model->where($map)->order('id desc')->page($page, $r)->select();
       
       
        $totalCount = $model->where($map)->count();

        $noticetype=array('text'=>'文字','image'=>'图片','voice'=>'声音','music'=>'音乐','video'=>'视屏','shortvideo'=>'小视屏','location'=>'位置','url'=>'链接');
        foreach ($list as &$v) {
         $v['MsgType'] = $noticetype[$v['MsgType']];  
         $v['Content'] =msubstr($v['Content'], $start=0, $length=100, $charset='utf-8',$suffix=true);
        }
        unset($map);
        $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Qwechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('消息管理' . $WechatTitle)
            ->setStatusUrl(U('Qwechat/setMemberStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->buttonModalPopup(U('Qwechat/send'),array('status' => 0),'发送消息')
            ->setSelectPostUrl(U('Admin/Qwechat/notice'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$wechats))
           
            ->keyId()->keyLink('name', '发送者', 'Wechat/editmember?id=###')->key('MsgType','消息类型', 'text')
            ->key('Content','内容', 'text')->key('Event','事件', 'text')->key('EventKey','事件KEY', 'text')
            ->keyCreateTime('CreateTime')->keyStatus()->keyDoActionEdit('editMember?id=###')
            ->setSearchPostUrl(U('Admin/Qwechat/member'))->search('粉丝名', 'name')->search('内容', 'Content')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


//机器人自动回复
    protected function getRobModel(){
        return D('Wechat/WechatRob');
    }

    public function rob($page=1,$r=10){
        $builder = new AdminListBuilder();
        $builder->title("机器人客服");
        $where = $cats = array();
        if(I('get.type')){
            $where['type'] = I('get.type');
        }
        if(I('get.cid')){
            $where['cid'] = I('get.cid');
        }
        $where['is_news'] = 0;

        $reportCount = $this->getRobModel()->where($where)->count();
        $list = $this->getRobModel()->page($page,$r)->where($where)->order('id DESC')->select();
        $types = array(
            1 => '文本回复',
            2 => '图文回复',
        );
        foreach($list as $key => $item){
            if($item['type'] == 2){
               
                if($item['content']){
                    $initHtml = '';
                    $sql = M();
                    $news = $sql->query("select * from `ocenter_news` where  id in (".$item['content'].") order by field (id,".$item['content'].")");
                    foreach($news as $k => $v){
                        $img = "";
                        $v['cover'] = get_cover($v['cover'], 'path');
                        $img = "<img src=\"{$v['cover']}\" width=\"30\" height=\"30\">";
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
            ->keyDoActionEdit('Wechat/edit?id=###')
            ->keyDoActionEdit('Wechat/attention?id=###', '设为关注回复')
            ->buttonNew(U('Wechat/rtext'),"新增文本")->buttonNew(U('Wechat/rtextimgs'),"新增多图文")->buttonDelete(U('del'));

        $builder->data($list);
        $builder->pagination($reportCount, $r);
        $builder->display();
    }

     public function rtext(){
        $id = I('get.id');
        $dataSet = array(
            'type' => 1
        );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();
        $data = $this->getRobModel()->find($id);
        $builder->keyId();
        $builder->title('文本回复');
        $builder->keyText('keywords', '回复关键词');
        $builder->keyTextArea('content', '回复内容');

        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();
    }

    public function rtextimg(){
        $id = I('get.id');
        $dataSet = array(
            'type' => 2
        );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();
        $data = $this->getRobModel()->find($id);
        $builder->keyId();
        $builder->title('图文回复');
        $builder->keyText('keywords', '回复关键词');
        $builder->keyText('title', '标题');
        $builder->keySingleImage('image', '封面图片');
        $builder->keyText('linkurl', '链接', '可不填');
        $builder->keyEditor('content', '回复内容');

        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();
    }

     public function rtextimgs(){
        $id = I('get.id');
        $dataSet = array('type' => 2 );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();

        $data = $this->getRobModel()->find($id);

        $builder->keyId();
        $builder->title('图文回复');
        $builder->keyText('keywords', '回复关键词');
        $builder->keyDataSelect('content', '选择图文', '图文回复作为多图为素材', 'dataset');

        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();

        $initHtml = '';

        if($data){
          
            // $news =  D('News/news')->order('id ASC')->where(array('id'=> array('in', $data['content'])))->select();
            $sql = M();
            $news = $sql->query("select * from `ocenter_news` where  id in (".$data['content'].") order by field (id,".$data['content'].")");
                  
           
            foreach($news as $key => $item){

                if($key == 0){
                    $item['cover'] = get_cover($item['cover'], 'path');
                }
                $initHtml .= "addToMultiCnt({$item['id']},'{$item['title']}','{$item['cover']}');";
            }
        }

        $script = <<<EOF
<style>
#multi_cnt{margin-top:20px;background-color: #ffffff;
    background-image: -moz-linear-gradient(center top , #ffffff 0%, #ffffff 100%);
    border: 1px solid #cdcdcd;
    border-radius: 12px;
    box-shadow: 0 3px 6px #999999;
    width: 285px;
    padding:20px 10px;
    }
#multi_cnt img{width:255px; height:124px;}
#multi_cnt .news_cnt{border-bottom:1px solid #d3d8dc; padding:5px 0; position:relative;}
#multi_cnt .delNews{background:black;color: #fff;
    position: absolute;
    text-align: center;
    top: 0;
    right:0;
    cursor: pointer;
    width: 40px;}

</style>
<script>
function delNews(){
    $(".news_cnt").hover(function(){
        if(!$(this).children('.delNews').length){
            $(this).append('<span class="delNews">删除<span>');
            $(".delNews").click(function(){
                var newsid = $(this).parent().attr('rel');
                var inputidVaule = $('#content').val().split(',');
                var newVaule = '';
                for(var i=0; i<inputidVaule.length; i++){
                    if(inputidVaule[i] != newsid){
                        if(newVaule){
                            newVaule += ',' + inputidVaule[i];
                        } else {
                            newVaule = inputidVaule[i];
                        }
                    }
                }
                $('#content').val(newVaule);
                console.log(newVaule);
                $(this).parent().remove();

            });
        } else {
            $(this).children('.delNews').show();
        }
    }, function(){
        $(this).children('.delNews').hide();
    });
}

function addToMultiCnt(id, title, image){
    if($("#multi_cnt").html().length < 1){
        var html = '<div class="news_cnt" rel="'+ id +'"><img src="'+ image +'"><p>'+ title +'</p></div>';
    } else {
        var html = '<div class="news_cnt" rel="'+ id +'">'+title+'</div>';
    }
    $("#multi_cnt").append(html);

    var inputidVaule = $('#content').val();
    if(inputidVaule){
        inputidVaule += ',' + id;
    } else {
        inputidVaule = id;
    }
    $('#content').val(inputidVaule);
    delNews();
}
$(function(){
    $("#content").hide();
    $("#content").parent().append('<div id="multi_cnt"></div>');
    {$initHtml}
})
</script>
EOF;
        $this->show($script);
    }

     protected function commonSave($dataSet = array()){
        if(IS_POST){
            $id = I('post.id');
            $data = I('post.');
            $data = array_merge($data, $dataSet);
            if($id){
                $data['ctime'] = time();
            }
            $mod = $this->getRobModel();
            $mod->create($data);
            if($id){
                $rs = $mod->save();
            } else {
                $rs = $mod->add();
            }
            if($rs){
                $this->msuccess();
            } else {
                $this->merror();
            }
        }
    }

      public function dataset($page=1,$r=5){
        $inputid = I('get.inputid');
        $totalCount =   D('News/news')->where(array('type'=>2))->count();
        $pager = new \Think\Page($totalCount, $r);
        $pager->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $paginationHtml = $pager->show();

        $list =  D('News/news')->where(array('type'=>2))->page($page,$r)->order('id DESC')->select();

        $this->assign('inputid', $inputid);
        $this->assign('list', $list);
        $this->assign('paginationHtml', $paginationHtml);
        $this->display(T('Application://Wechat@Wechat/dataset'));
    }

    public function attention(){
        $id = I('get.id');
        if(!$id){
            $this->error('信息不存在');
        }
        $this->getRobModel()->where(array('is_attention' => 1))->save(array('is_attention' => 2));
        $rs = $this->getRobModel()->where(array('id' => $id))->save(array('is_attention' => 1));

        if($rs){
            $this->success('设置成功');
        }
        $this->error('设置失败');
    }

     public function edit(){
        $id = I('get.id');
        if(!$id){
            $this->error('信息不存在');
        }
        $info = $this->getRobModel()->find($id);
        switch($info['type']){
            case 1:
                $this->redirect('rtext', array('id' => $id));
                break;
            case 2:
                $this->redirect('rtextimgs', array('id' => $id));
                break;
        }
    }

     public function del(){
        $id = I('post.ids');

        if($id){
            $rs = false;
            foreach($id as $item){
                if(intval($item)){
                    $rs = $this->getRobModel()->delete($item) || $rs;
                }
            }
            if($rs){
                $this->msuccess('删除成功', U('rob'));
            }
        }
        $this->msuccess('删除失败', U('areplay'));
    }


  


    protected function msuccess($msg = '保存成功', $url = ''){
        header('Content-type: application/json');
        $url = $url ? $url : __SELF__;
        exit(json_encode(array('info' => $msg, 'status' => 1, 'url' => $url)));
    }
    protected function merror($msg = '保存失败', $url = ''){
        header('Content-type: application/json');
        $url = $url ? $url : __SELF__;
        exit(json_encode(array('info' => $msg, 'status' => 0, 'url' => $url)));
    }


}
