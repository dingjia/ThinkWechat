<?php
return array(
    //模块名
    'name' => 'Feedback',
    //别名
    'alias' => '客情反馈',
    //版本号
    'version' => '1.0.0',
    //是否商业模块,1是，0，否
    'is_com' => 1,
    //是否显示在导航栏内？  1是，0否 暂时无效，保留项
    'show_nav' => 1,
    //模块描述
    'summary' => '多平台外卖管理系统',
    //开发者
    'developer' => '黄冈咸鱼计算机科技有限公司',
    //开发者网站
    'website' => 'http://www.0716it.cn',
    //前台入口，可用U函数
    'entry' => 'Feedback/index/index',
    //后台入口
    'admin_entry' => 'Admin/Feedback/index',
    //zui中的icon-xxx的小图标，此处为icon-th
    'icon' => 'book',
    //是否允许卸载，核心模块请设为0
    'can_uninstall' => 1
);