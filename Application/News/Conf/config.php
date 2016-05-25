<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 上午10:02
 * @author 郑钟良<zzl@ourstu.com>
 */

return array(
    // 预先加载的标签库
    'TAGLIB_PRE_LOAD' => 'OT\\TagLib\\Article,OT\\TagLib\\Think',

    /* 主题设置 */
    'DEFAULT_THEME' => 'default', // 默认模板主题名称

    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__' => __ROOT__ . '/Application/' . MODULE_NAME . '/Static/images',
        '__CSS__' => __ROOT__ . '/Application/' . MODULE_NAME . '/Static/css',
        '__JS__' => __ROOT__ . '/Application/' . MODULE_NAME . '/Static/js',
        '__ZUI__' => __ROOT__ . '/Public/zui',
        
        '__CORE_IMAGE__'=>__ROOT__.'/Application/Core/Static/images',
        '__CORE_CSS__'=>__ROOT__.'/Application/Core/Static/css',
        '__CORE_JS__'=>__ROOT__.'/Application/Core/Static/js',
        '__APPLICATION__'=>__ROOT__.'/Application/',

        '__NOTE_IMAGE__'=>__ROOT__.'/Public/note/images',
        '__NOTE_CSS__'=>__ROOT__.'/Public/note/css',
        '__NOTE_JS__'=>__ROOT__.'/Public/note/js',

       

        '__WECHAT_IMAGE__'=>__ROOT__.'/Public/weui/dist/example/images',
        '__WECHAT_CSS__'=>__ROOT__.'/Public/weui/dist/style',
        '__WECHAT_EXAMPLE__'=>__ROOT__.'/Public/weui/dist/example',

         '__GAME__'=>__ROOT__.'/Public/game'





    ),

    'NEED_VERIFY'=>0,//此处控制默认是否需要审核，该配置项为了便于部署起见，暂时通过在此修改来设定。
);