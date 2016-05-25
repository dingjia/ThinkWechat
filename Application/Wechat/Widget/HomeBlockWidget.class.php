<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-6-8
 * Time: 下午4:37
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Wechat\Widget;


use Wechat\Model\WechatModel;
use Think\Controller;

class HomeBlockWidget extends Controller{
    public function render()
    {
        $this->assignWechat();
        $this->assignWechatPost();
        $this->display(T('Application://Wechat@Widget/homeblock'));
    }

    private function assignWechat()
    {
        $data = S('Wechat_SHOW_DATA');
        $WechatModel = new WechatModel();
        if (empty($data)) {
            $Wechat_ids = modC('Wechat_SHOW', '', 'Wechat');
            $cache_time = modC('Wechat_SHOW_CACHE_TIME', 600, 'Wechat');
            $Wechat_ids=explode('|', $Wechat_ids);
            $Wechat= $WechatModel->where(array('status' => 1,'id' => array('in',$Wechat_ids)))->select();
            $Wechat=array_combine(array_column($Wechat,'id'),$Wechat);
            $data=array();
            foreach($Wechat_ids as $val){
                if($val!=''&&$Wechat[$val]){
                    $data[]=$Wechat[$val];
                }
            }
            if(!count($data)){
                $data=1;
            }
            S('Wechat_SHOW_DATA', $data,$cache_time);
        }
        if($data==1){
            $data=null;
        }
        foreach ($data as &$v) {
            $v['hasFollowed'] = $WechatModel->checkFollowed($v['id'], is_login());
        }
        unset($v);
        $this->assign('Wechat_show', $data);
    }

    private function assignWechatPost()
    {
        $list = S('Wechat_POST_SHOW_DATA');
        if (empty($list)) {
            $order_key=modC('Wechat_POST_ORDER','last_reply_time', 'Wechat');
            $order_type=modC('Wechat_POST_TYPE','desc', 'Wechat');
            $limit=modC('Wechat_POST_SHOW_NUM',5, 'Wechat');
            $cache_time = modC('Wechat_POST_CACHE_TIME', 600, 'Wechat');

            $map['status']=1;
            $list = M('WechatPost')->where($map)->order($order_key.' '.$order_type)->limit($limit)->select();
            $list = $this->assignWechatInfo($list);
            S('Wechat_POST_SHOW_DATA', $list,$cache_time);
        }
        $this->assign('Wechat_post_list', $list);
    }

    /**关联帖子列表的版块信息
     * @param $list
     * @return mixed
     */
    private function assignWechatInfo($list)
    {
        $WechatModel = new WechatModel();
        $Wechat_key_value = $WechatModel->getWechatKeyValue();
        foreach ($list as &$v) {
            $v['Wechat'] = $Wechat_key_value[$v['Wechat_id']];
        }
        unset($v);
        return $list;
    }
} 