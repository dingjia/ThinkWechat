<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-6-5
 * Time: 上午10:38
 * @author 郑钟良<zzl@ourstu.com>
 */


/**
 * 获取要排除的uids(版主、自己)
 * @param int $lzl_reply_id
 * @param int $reply_id
 * @param int $post_id
 * @param int $Wechat_id
 * @param int $with_self 是否包含记录的uid
 * @return array|int|mixed
 * @author 郑钟良<zzl@ourstu.com>
 */
function get_expect_ids($lzl_reply_id=0,$reply_id=0,$post_id=0,$Wechat_id=0,$with_self=1)
{
    $uid=0;
    if(!$Wechat_id){
        if(!$post_id){
            if(!$reply_id){
                $lzl_reply=D('WechatLzlReply')->find($lzl_reply_id);
                $uid=$lzl_reply['uid'];
                $post_id=$lzl_reply['post_id'];
            }else{
                $reply = D('WechatPostReply')->find(intval($reply_id));
                $uid=$reply['uid'];
                $post_id=$reply['post_id'];
            }
        }
        $post=D('WechatPost')->where(array('id' => $post_id, 'status' => 1))->find();
        $Wechat_id=$post['Wechat_id'];
        if(!$uid){
            $uid=$post['uid'];
        }
    }
    $Wechat=D('Wechat')->find($Wechat_id);
    if(mb_strlen($Wechat['admin'],'utf-8')){
        $expect_ids=str_replace('[','',$Wechat['admin']);
        $expect_ids=str_replace(']','',$expect_ids);
        $expect_ids=explode(',',$expect_ids);
        if($uid&&$with_self){
            if(!in_array($uid,$expect_ids)){
                $expect_ids=array_merge($expect_ids,array($uid));
            }
        }
    }else{
        if($with_self&&$uid){
            $expect_ids=$uid;
        }else{
            $expect_ids=-1;
        }
    }
    return $expect_ids;
}

/**
 * 微信微信是否允许发帖
 * @param $Wechat_id
 * @return bool
 * @author 郑钟良<zzl@ourstu.com>
 */
function WechatAllowCurrentUserGroup($Wechat_id)
{
    $Wechat_id = intval($Wechat_id);
    //如果是超级管理员，直接允许
    if (is_login() == 1) {
        return true;
    }

    //如果帖子不属于任何微信，则允许发帖
    if (intval($Wechat_id) == 0) {
        return true;
    }

    //读取微信的基本信息
    $Wechat = D('Wechat')->where(array('id' => $Wechat_id))->find();
    $userGroups = explode(',', $Wechat['allow_user_group']);

    //读取用户所在的用户组
    $list = M('AuthGroupAccess')->where(array('uid' => is_login()))->select();
    foreach ($list as &$e) {
        $e = $e['group_id'];
    }


    //判断用户组是否有权限
    $list = array_intersect($list, $userGroups);
    return $list ? true : false;
}