<?php

namespace Addons\InsertVideo\Controller;
use Home\Controller\AddonsController;

class VideoController extends AddonsController{
    public function getVideoInfo()
    {

        $link = op_t(I('post.url'));
        if(preg_match("/.swf/i", $link, $check)){
            $return['boolen'] = 0;
            $return['message'] = '暂时不支持flash地址';
            exit(json_encode($return));
       }

        if (preg_match("/(youku.com|ku6.com|sohu.com|sina.com.cn|qq.com|tudou.com|yinyuetai.com|iqiyi.com)/i", $link, $hosts)) {
            $return['boolen'] = 1;
            $return['data'] = $link;
        } else {
            $return['boolen'] = 0;
            $return['message'] = '仅支持优酷、酷6、新浪、土豆网、搜狐、音悦台、腾讯、爱奇艺等视频发布';
        }

        $flashinfo =  D('ContentHandler')->getVideoInfo($link);

        if (!$flashinfo['title'] || json_encode($flashinfo['title']) == 'null') {
            $flashinfo['title'] = "未获取标题";
        }
        $return['is_swf'] = 0;
        $return['data'] = $flashinfo;
        exit(json_encode($return));
    }

}