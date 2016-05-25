<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-22
 * Time: 上午10:14
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Addons\Announcement;


use Common\Controller\Addon;

class AnnouncementAddon extends Addon{

    public $info = array(
        'name' => 'Announcement',
        'title' => '全站公告',
        'description' => '管理员设置全站公告',
        'status' => 1,
        'author' => '想天科技-zzl(郑钟良)',
        'version' => '0.1'
    );

    public $admin_list = array(
        '' => '',
    );

    public function install()
    {
        $prefix = C("DB_PREFIX");
        $model = D();
        $sql ="DROP TABLE IF EXISTS `{$prefix}announcement`;";
        $model->execute($sql);
        $sql =<<<SQL
        CREATE TABLE IF NOT EXISTS `{$prefix}announcement` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `icon` varchar(50) NOT NULL COMMENT '图标',
  `content` text NOT NULL,
  `link` varchar(200) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `create_time` int(11) NOT NULL,
  `end_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='公告表' AUTO_INCREMENT=1 ;
SQL;
        $model->execute($sql);
        return true;
    }

    public function uninstall()
    {
        $prefix = C("DB_PREFIX");
        $model = D();
        $sql ="DROP TABLE IF EXISTS `{$prefix}announcement`;";
        $model->execute($sql);
        return true;
    }

    public function afterTop()
    {
        $this->_announcementList();
        $this->_getAddonsConfig();
        $this->display(T('Addons://Announcement@Announcement/show'));
    }

    private function _announcementList()
    {
        $list=S('Announcement_list');
        if($list===false){
            $map['status']=1;
            $map['end_time']=array('gt',time());
            $announcementModel=D('Addons://Announcement/Announcement');
            $list=$announcementModel->getList($map);
            if(!count($list)){
                $list=1;
            }
            S('Announcement_list',$list);
        }
        if($list!=1){
            $my_show_list=array();
            $unShowId=cookie('announcement_cookie_ids');
            if($unShowId){
                $unShowId=explode(',',$unShowId);
            }else{
                $unShowId=array();
            }
            foreach($list as $key=>$val){
                if($val['end_time']<=time()){//去除过期的公告
                    unset($list[$key]);
                }else{
                    if(!in_array($val['id'],$unShowId)){
                        $my_show_list[]=$val;
                    }
                }
            }
            if(!count($list)){
                $list=1;
                cookie('announcement_cookie_ids',null);
            }else{
                $have_ids=array_column($list,'id');
                $unShowId=array_diff($unShowId,$have_ids);
                $unShowId=implode(',',$unShowId);
                cookie('announcement_cookie_ids',$unShowId);
            }
            S('Announcement_list',$list);
        }
        $this->assign('announcement_list',$my_show_list);
        $this->assign('announcement_num',count($my_show_list));
    }

    private function _getAddonsConfig()
    {
        $config=S('ANNOUNCEMENT_COLOR_CONFIG');
        if(!$config){
            $map['name']    =   "Announcement";
            $map['status']  =   1;
            $config  =   M('Addons')->where($map)->getField('config');
            $config=json_decode($config,true);
            $config=$config['color'];
            if(!$config){
                $config=' ';
            }
            S('ANNOUNCEMENT_COLOR_CONFIG',$config,600);
        }
        $this->assign('announcement_alert_type',$config);
    }
} 