<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 下午1:28
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace News\Widget;


use Think\Controller;

class HomeBlockWidget extends Controller{
    public function render()
    {
        $this->assignNews();
        $this->display(T('Application://News@Widget/homeblock'));
    }

    private function assignNews()
    {
        $num = modC('NEWS_SHOW_COUNT', 4, 'News');
        $type= modC('NEWS_SHOW_TYPE', 0, 'News');
        $field = modC('NEWS_SHOW_ORDER_FIELD', 'view', 'News');
        $order = modC('NEWS_SHOW_ORDER_TYPE', 'desc', 'News');
        $cache = modC('NEWS_SHOW_CACHE_TIME', 600, 'News');
        $list = S('news_home_data');
        if (!$list) {
            if($type){
                /**
                 * 获取推荐位数据列表
                 * @param  number  $pos      推荐位 1-系统首页，2-推荐阅读，4-本类推荐
                 * @param  number  $category 分类ID
                 * @param  number  $limit    列表行数
                 * @param  boolean $filed    查询字段
                 * @param order 排序
                 * @return array             数据列表
                 */
                $list=D('News/News')->position(1,null,$num,true,$field . ' ' . $order);
            }else{
                $map = array('status' => 1,'dead_line'=>array('gt',time()));
                $list = D('News/News')->getList($map,$field . ' ' . $order,$num);
            }
            foreach ($list as &$v) {
                $v['user']=query_user(array('space_url','nickname'),$v['uid']);
            }
            unset($v);
            if(!$list){
                $list=1;
            }
            S('news_home_data', $list, $cache);
        }
        unset($v);
        if($list==1){
            $list=null;
        }
        $this->assign('news_lists', $list);
    }
} 