<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.thinkphp.cn>
// +----------------------------------------------------------------------

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */
return array(

    // 预先加载的标签库
    'TAGLIB_PRE_LOAD'     =>    'OT\\TagLib\\Article,OT\\TagLib\\Think',
        
    /* 主题设置 */
    'DEFAULT_THEME' =>  'default',  // 默认模板主题名称



    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__' => __ROOT__ . '/Application/'.MODULE_NAME   . '/Static/images',
        '__CSS__' => __ROOT__ . '/Application/'.MODULE_NAME .'/Static/css',
        '__JS__' => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/js',
        '__ZUI__' => __ROOT__ . '/Public/zui',

        '__NOTE_IMAGE__'=>__ROOT__.'/Public/note/images',
        '__NOTE_CSS__'=>__ROOT__.'/Public/note/css',
        '__NOTE_JS__'=>__ROOT__.'/Public/note/js',

        '__WECHAT_IMAGE__'=>__ROOT__.'/Public/weui/dist/example/images',
        '__WECHAT_CSS__'=>__ROOT__.'/Public/weui/dist/style',
        '__WECHAT_EXAMPLE__'=>__ROOT__.'/Public/weui/dist/example',

        '__J_WEUI_DIST__'=>__ROOT__.'/Public/jquery-weui/dist',
        '__J_WEUI_DOMOS_CSS__'=>__ROOT__.'/Public/jquery-weui/demos/css',
        '__J_WEUI_DOMOS_IMAGES__'=>__ROOT__.'/Public/jquery-weui/demos/images',


        

       
    ),
);