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
class LZLReplyWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function  LZLReply($to_f_reply_id,$post_id,$reply_uid,$count)
    {
        isset( $_GET['page']) &&  $var['page']= $_GET['page'];
        $_GET['page'] == null && $var['page']=1;
        $this->assign('p',$var['page']);
        $this->assign('reply_uid',$reply_uid);
        $this->assign('post_id',$post_id);
        $this->assign('to_f_reply_id',$to_f_reply_id);
        $this->assign('count',$count);
        $this->display('Widget/LZLReply');

    }



}
