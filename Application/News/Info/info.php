<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 上午10:12
 * @author 郑钟良<zzl@ourstu.com>
 */


return array(
    //模块名
    'name' => 'News',
    //别名
    'alias' => '素材库',
    //版本号
    'version' => '2.3.0',
    //是否商业模块,1是，0，否
    'is_com' => 0,
    //是否显示在导航栏内？  1是，0否
    'show_nav' => 1,
    //模块描述
    'summary' => '资讯模块，用户可前台投稿的CMS模块',
    //开发者
    'developer' => '黄冈咸鱼计算机科技有限公司',
    //开发者网站
    'website' => 'http://www.ourstu.com',
    //前台入口，可用U函数
    'entry' => 'News/index/index',

    'admin_entry' => 'Admin/News/index',

    'icon' => 'rss-sign',

    'can_uninstall' => 1,

    'hide' => 1

);