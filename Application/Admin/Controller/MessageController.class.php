<?php
namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;

/**
 * Class MessageController  消息控制器
 * @package Admin\Controller
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
class MessageController extends AdminController
{

    protected $Model;

    function _initialize()
    {
       
        $this->Model = D('Message');
        $this->ContentModel= D('MessageContent');
       
        $this->type=array(0=>'系统',1=>'企业号',2=>'服务号',3=>'邮件',4=>'短信');
        $this->status=array(1=>"未处理",2=>"已处理");

    }
    
    public function userList($page=1,$r=20)
    {
        $aSearch1 = I('get.user_search1','');
        $aSearch2 = I('get.user_search2',0,'intval');
        $map = array();

        if (empty($aSearch1) && empty($aSearch2)) {


            $aUserGroup = I('get.user_group', 0, 'intval');
            $aRole = I('get.role', 0, 'intval');


            if (!empty($aRole) || !empty($aUserGroup)) {
                $uids = $this->getUids($aUserGroup, $aRole);
                $map['uid'] = array('in', $uids);
            }


            $user = D('member')->where($map)->page($page, $r)->field('uid,nickname')->select();
            foreach ($user as &$v) {
                $v['id'] = $v['uid'];
            }
            unset($v);
            $totalCount = D('member')->where($map)->count();

        } else {

            $uids = $this->getUids_sc($aSearch1, $aSearch2);
            $map['uid'] = array('in', $uids);

            $user = D('member')->where($map)->page($page, $r)->field('uid,nickname')->select();
            foreach ($user as &$v) {
                $v['id'] = $v['uid'];
            }
            unset($v);
            $totalCount = D('member')->where($map)->count();


        }
        $r = 20;

        $role = D('Role')->selectByMap(array('status' => 1));
        $user_role = array(array('id' => 0, 'value' => L('_ALL_')));
        foreach ($role as $key => $v) {
            array_push($user_role, array('id' => $v['id'], 'value' => $v['title']));
        }

        $group = D('AuthGroup')->getGroups();

        $user_group = array(array('id' => 0, 'value' => L('_ALL_')));
        foreach ($group as $key => $v) {
            array_push($user_group, array('id' => $v['id'], 'value' => $v['title']));
        }


        $builder = new AdminListBuilder();
        $builder->title(L('_"MASS_USER_LIST"_'));
        $builder->meta_title = L('_"MASS_USER_LIST"_');

        $builder->setSelectPostUrl(U('Message/userList'))
            ->setSearchPostUrl(U('Message/userList'))
            ->select(L('_USER_GROUP:_'), 'user_group', 'select', L('_FILTER_ACCORDING_TO_USER_GROUP_'), '', '', $user_group)
            ->select(L('_IDENTITY_'), 'role', 'select', L('_FILTER_ACCORDING_TO_USER_IDENTITY_'), '', '', $user_role)
            ->search('','user_search1','',L('_SEARCH_ACCORDING_TO_USERS_NICKNAME_'),'','','')
            ->search('','user_search2','',L('_SEARCH_ACCORDING_TO_USER_ID_'),'','','');
        $builder->buttonModalPopup(U('Message/sendMessage'), array('user_group' => $aUserGroup, 'role' => $aRole), L('_SEND_A_MESSAGE_'), array('data-title' => L('_MASS_MESSAGE_'), 'target-form' => 'ids', 'can_null' => 'true'));
        $builder->keyText('uid', '用户ID')->keyText('nickname', L('_"NICKNAME"_'));

        $builder->data($user);
        $builder->pagination($totalCount, $r);
        $builder->display();


    }

    private function getUids($user_group = 0, $role = 0)
    {
        $uids = array();
        if (!empty($user_group)) {
            $users = D('auth_group_access')->where(array('group_id' => $user_group))->field('uid')->select();
            $group_uids = getSubByKey($users, 'uid');
            if ($group_uids) {
                $uids = $group_uids;
            }
        }
        if (!empty($role)) {
            $users = D('user_role')->where(array('role_id' => $role))->field('uid')->select();
            $role_uids = getSubByKey($users, 'uid');
            if ($role_uids) {
                $uids = $role_uids;
            }
        }
        if (!empty($role) && !empty($user_group)) {
            $uids = array_intersect($group_uids, $role_uids);
        }
        return $uids;


    }
    private function getUids_sc($search_nn = "", $search_id = 0)
    {
        $uids = array();
        if (!empty($search_nn)) {
            $users = D('member')->where(array('nickname' => $search_nn))->field('uid')->select();
            $uids_nn = getSubByKey($users, 'uid');
            if ($uids_nn) {
                $uids = $uids_nn;
            }
        }
        if (!empty($search_id)) {
            $users = D('member')->where(array('uid' => $search_id))->field('uid')->select();
            $uids_id = getSubByKey($users, 'uid');
            if ($uids_id) {
                $uids = $uids_id;
            }
        }
        if (!empty($search_id) && !empty($search_nn)) {
            $uids = array_intersect($search_id, $search_nn);
        }
        return $uids;
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
            if(empty($aUids)){
                if (empty($aUserGroup) && empty($aUserRole)) {
                    $this->error(L('_PLEASE_SELECT_A_USER_GROUP_OR_AN_IDENTITY_GROUP_OR_USER_'));
                }

                $role_count = D('Role')->where(array('status' => 1))->count();
                $group_count = D('AuthGroup')->where(array('status' => 1))->count();
                if ($role_count == count($aUserRole)) {
                    $aUserRole = 0;
                }
                if ($group_count == count($aUserGroup)) {
                    $aUserGroup = 0;
                }
                if (!empty($aUserRole)) {
                    $uids = D('user_role')->where(array('role_id' => array('in', $aUserRole)))->field('uid')->select();
                }
                if (!empty($aUserGroup)) {
                    $uids = D('auth_group_access')->where(array('group_id' => array('in', $aUserGroup)))->field('uid')->select();
                }
                if (empty($aUserRole) && empty($aUserGroup)) {
                    $uids = D('Member')->where(array('status' => 1))->field('uid')->select();
                }
                $to_uids = getSubByKey($uids, 'uid');
            }else{
                // 用uid发送消息
                $to_uids = explode(',',$aUids);
            }
            D('Message')->sendMessageWithoutCheckSelf($to_uids, $aTitle, $aContent, $aUrl, $args);
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
                $users = D('Member')->where(array('uid'=>array('in',$aUids)))->field('uid,nickname')->select();
                $this->assign('users', $users);
                $this->assign('uids', $uids);
            }
            $this->display('sendmessage');
        }
    }


     public function Message($page = 1,  $r = 20,$shopid='',$service='',$product='',$nickname='',$start_time='',$end_time='')
    {
       
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname|openid|id'] = array('like', '%' . $nickname . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($start_time != '' and $end_time != '') {
            $map['create_time'] = array('between', array($start_time,$end_time));
        }


        if ($shopid) $map['shopid'] = $shopid;
        if ($service) $map['service'] = $service;
        if ($product) $map['product'] = $product;
        
     
        $list = $this->Model->where($map)->page($page, $r)->order('id desc')->select();
        mymysql($this->Model);
        $totalCount = $this->Model->where($map)->count();
    
        foreach ($list as &$v) {
         $content=D('Message/MessageContent')->find($v['content_id']);
        
         $v['type'] = $this->type[ $content['type']];
         $v['content'] =  $content['content'];
          
        }
        unset($map);
       
        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('主动消息' . $WechatTitle)

            ->setStatusUrl(U('Feedback/setListStatus'))->buttonDelete()->buttonNew(U('Feedback/index/sendRob'),'发送今日邀请')
            ->setSelectPostUrl(U('Admin/Feedback/FeedbackList'))
            ->select('','shopid','select','','','',array_merge(array(array('appid'=>0,'value'=>'全部分店')),$shops))
            ->select('','product','select','','','',array(array('id'=>0,'value'=>'产品评价'),array('id'=>1,'value'=>'好评'),array('id'=>2,'value'=>'中评'),array('id'=>3,'value'=>'差评')))
             ->select('','service','select','','','',array(array('id'=>0,'value'=>'服务评价'),array('id'=>1,'value'=>'好评'),array('id'=>2,'value'=>'中评'),array('id'=>3,'value'=>'差评')))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'选择状态'),array('id'=>1,'value'=>'未处理'),array('id'=>2,'value'=>'已处理')))
            ->setSearchPostUrl(U('Admin/Feedback/orders'))->search('顾客', 'nickname')->search('手机', 'mobile')->search('开始时间', 'start_time','date')->search('结束时间', 'end_time','date')
            ->keyID()->keyLink('content','内容', 'orderWay?oid=###')->keyText('to_uid', '系统')
            ->keyText('to_userid', '企业号')->keyText('to_openid', '服务号')
            ->keyText('to_email', '邮箱')->keyText('to_mobile', '手机')->keyText('type', '类型')
            ->keyCreateTime()->keyDoActionEdit('editList?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

}
