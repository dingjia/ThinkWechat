<?php

namespace Addons\CheckIn\Controller;

use Addons\CheckIn\Model\CheckInModel;
use Home\Controller\AddonsController;
use Think\Hook;

class CheckInController extends AddonsController
{

    public function doCheckIn()
    {

        if (!is_login()) {
            $this->error('请先登陆！');
        }


        $name = get_addon_class('CheckIn');
        $class = new $name();
        $config = $class->getConfig();
        if(empty($config['action'])){
            $res = $class->doCheckIn();
            if($res){

                $this->success('签到成功!');}

            else{
                $this->error('已经签到了！');
            }
        }else{
            $action_info = M('Action')->getByName($config['action']);
            $this->error('只支持['.$action_info['title'].']来签到！');
        }

         unset($j);
    }


    public function getRank()
    {
        $aType = I('post.type', 'today', 'op_t');
        $name = get_addon_class('CheckIn');
        $class = new $name();
        $html = $class->rank($aType);
        $this->ajaxReturn(array('status' => 1, 'html' => $html));
    }

    public function ranking()
    {
        $aPage = I('get.page', 1, 'intval');
        $aOrder = I('get.order', 'total_check', 'op_t');
        $checkInfoModel = D('Addons://CheckIn/CheckIn');
        $memberModel = D('Member');
        $limit = 50;
        if ($aOrder == 'today') {
            $user_list = $checkInfoModel->field('uid,create_time')->page($aPage, $limit)->where(array('create_time' => array('egt', get_some_day(0))))->order('create_time asc, uid asc')->select();
            $totalCount = $checkInfoModel->where(array('create_time' => array('egt', get_some_day(0))))->count();
            foreach ($user_list as $key => &$val) {

                $val['ranking'] = ($aPage - 1) * $limit + $key + 1;
                if ($val['ranking'] <= 3) {
                    $val['ranking'] = '<span style="color:#EB7112;">' . $val['ranking'] . '</span>';
                }
                $val['status'] = '<span>已签到 ' . friendlyDate($val['create_time']) . '</span>';
                $user = query_user(array('uid', 'nickname', 'total_check', 'con_check'), $val['uid']);
                $val = array_merge($val, $user);
            }
            unset($key, $val);

        } else {
            $user_list = $memberModel->field('uid,nickname,total_check,con_check')->page($aPage, $limit)->order($aOrder . ' desc,uid asc')->select();
            $totalCount = $memberModel->count();
            foreach ($user_list as $key => &$val) {
                $val['ranking'] = ($aPage - 1) * $limit + $key + 1;
                if ($val['ranking'] <= 3) {
                    $val['ranking'] = '<span style="color:#EB7112;">' . $val['ranking'] . '</span>';
                }
                $check = $checkInfoModel->getCheck($val['uid']);
                if ($check) {
                    $val['status'] = '<span>已签到 ' . friendlyDate($check['create_time']) . '</span>';
                } else {
                    $val['status'] = '<span style="color: #BDBDBD;">未签到</span>';
                }
            }
        }

            foreach($user_list as &$u){
                $temp_user=query_user(array('nickname'),$u['uid']);
                $u['nickname']=$temp_user['nickname'];
            }
            unset($u);

        $this->assign('user_list', $user_list);
        $this->assign('totalCount', $totalCount);
        if (is_login()) {
            //获取用户信息
            $user_info = query_user(array('uid', 'nickname', 'space_url', 'avatar64', 'con_check', 'total_check'), is_login());

            $check = $checkInfoModel->getCheck(is_login());
            if ($check) {
                $user_info['is_sign'] = $check['create_time'];
            } else {
                $user_info['is_sign'] = 0;
            }

            if ($aOrder == 'today') {
                $ranking = $checkInfoModel->field('uid')->where(array('create_time' => array('egt', get_some_day(0))))->order('create_time asc, uid asc')->select();
            } else {
                $ranking = $memberModel->field('uid')->order($aOrder . ' desc,uid asc')->select();
            }


            $ranking = getSubByKey($ranking, 'uid');
            if (array_search(is_login(), $ranking) === false) {
                $user_info['ranking'] = count($ranking) + 1;
            } else {
                $user_info['ranking'] = array_search(is_login(), $ranking) + 1;
            }
            $this->assign('user_info', $user_info);
        }
        $this->assign('order', $aOrder);
        $this->display(T('Addons://CheckIn@CheckIn/ranking'));
    }


}