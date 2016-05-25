<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Wechat\Widget;

use Think\Action;

/**
 * 分类widget
 * 用于动态调用分类信息
 */
class HotPostWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($Wechat_id)
    {
        $Wechat_id=intval($Wechat_id);
        $posts = S('Wechat_hot_posts_' . $Wechat_id);

        $map['status']=1;
        $time=time()-604800;//一周以内
        $map['create_time']=array('gt',$time);
        if (empty($posts)) {
            if ($Wechat_id == 0) {
                $posts = D('WechatPost')->where($map)->order('reply_count desc')->limit(9)->select();
            } else {
                $map['Wechat_id']=$Wechat_id;

                $posts = D('WechatPost')->where($map)->order('reply_count desc')->limit(9)->select();
            }
            S('Wechat_hot_posts_' . $Wechat_id, $posts, 300);
        }

        $this->assign('posts', $posts);
        $this->display('Widget/hot');

    }

}
