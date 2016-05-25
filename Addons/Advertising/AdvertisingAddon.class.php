<?php

namespace Addons\Advertising;

use Common\Controller\Addon;
use Think\Db;

/**
 * 广告插件
 * @author quick
 */

class AdvertisingAddon extends Addon
{

    public $info = array(
        'name' => 'Advertising',
        'title' => '广告位置',
        'description' => '广告位插件',
        'status' => 1,
        'author' => '嘉兴想天信息科技有限公司',
        'version' => '3.0.0'
    );

    public $addon_path = './Addons/Advertising/';

    /**
     * 配置列表页面
     * @var unknown_type
     */
    public $admin_list = array(
        'listKey' => array(
            'title' => '广告位名称',
            'typetext' => '广告位类型',
            'width' => '广告位宽度',
            'height' => '广告位高度',
            'statustext' => '位置状态',
            'margin'=>'边缘留白',
            'padding'=>'内部留白',
            'theme' => '所用主题',
        ),
        'model' => 'Advertising',
        'order' => 'id asc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install()
    {
        $prefix = C("DB_PREFIX");
        $model = D();
        $sql = <<<SQL
CREATE TABLE `{$prefix}advertising` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` char(80) NOT NULL DEFAULT '' COMMENT '广告位置名称',
  `type` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '广告位置展示方式  0为默认展示一张',
  `width` char(20) NOT NULL DEFAULT '' COMMENT '广告位置宽度',
  `height` char(20) NOT NULL DEFAULT '' COMMENT '广告位置高度',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态（0：禁用，1：正常）',
  `pos` varchar(50) NOT NULL,
  `style` tinyint(4) NOT NULL,
  `theme` varchar(50) NOT NULL DEFAULT 'all' COMMENT '所用主题，默认为all，通用',
  `margin` varchar(50) NOT NULL COMMENT '边缘',
  `padding` varchar(50) NOT NULL COMMENT '留白',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='广告位置表';
SQL;
        $model->execute($sql);

        $sql_insert = <<<SQL
INSERT INTO `{$prefix}advertising` VALUES ('15', '微博右侧底部广告', '1', '280px', '100px', '1', 'weibo_right_below_all', '0', 'all', '', '');
INSERT INTO `{$prefix}advertising` VALUES ('14', '签到下方广告', '1', '280px', '100px', '1', 'weibo_below_checkrank', '0', 'all', '0 0 10px 0', '');
INSERT INTO `{$prefix}advertising` VALUES ('13', '微博过滤右方', '3', '300px', '30px', '1', 'weibo_filter_right', '0', 'all', '', '');
INSERT INTO `{$prefix}advertising` VALUES ('16', '论坛帖子主题下方广告', '1', '680px', '100px', '1', 'forum_below_post_content', '0', 'all', '', '');
INSERT INTO `{$prefix}advertising` VALUES ('17', '资讯文章内容下方广告', '1', '690px', '100px', '1', 'news_below_article_content', '0', 'all', '', '');
INSERT INTO `{$prefix}advertising` VALUES ('18', '资讯右侧下方广告', '1', '360px', '100px', '1', 'news_right_below_all', '0', 'all', '', '');
INSERT INTO `{$prefix}advertising` VALUES ('24', '资讯首页右侧最底部广告', '1', '360px', '120px', '1', 'news_index_bottom_top', '0', 'all', '10px 0 0 0', '0');
INSERT INTO `{$prefix}advertising` VALUES ('25', '资讯首页顶部广告', '1', '738px', '240px', '1', 'news_index_top', '0', 'all', '0', '0');
INSERT INTO `{$prefix}advertising` VALUES ('23', '资讯首页右侧最顶部广告', '1', '360px', '120px', '1', 'news_index_right_top', '0', 'all', '0 0 10px 0', '0');
SQL;
        $model->execute($sql_insert);

        /* if(count(M()->query("SHOW TABLES LIKE '".$this->table_name()."Advertising'")) != 1){
             session('addons_install_error', ',AdvsType表未创建成功，请手动检查插件中的sql，修复后重新安装');
             return false;
         }*/
        return true;
    }

    /**
     * (non-PHPdoc)
     * 卸载函数
     * @see \Common\Controller\Addons::uninstall()
     */
    public function uninstall()
    {
        $db_prefix = C('DB_PREFIX');
        $sql = "DROP TABLE IF EXISTS `" . $db_prefix . "Advertising`;";
        D()->execute($sql);
        return true;
    }

    //实现的广告钩子
    public function AdminIndex($param)
    {

    }
}