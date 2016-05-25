<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-29
 * Time: 上午9:16
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace News\Widget;


use Think\Controller;

class HotWidget extends Controller{
    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($category=0, $timespan = 604800, $limit = 5)
    {
        if ($category != 0) {
            $cates=D('News/NewsCategory')->getCategoryList(array('pid'=>$category,'status'=>1));
            $cates=array_column($cates,'id');
            $map['category']=array('in',array_merge(array($category),$cates));
        }
        $map['status']=1;
        $map['dead_line']=array('gt',time());
        $map['update_time']=array('gt',time()-$timespan);//一周以内
        $lists = D('News/News')->getList($map,'view desc',5,'id,title,cover,uid,create_time,view');
        foreach($lists as &$val){
            $val['user']=query_user(array('space_url','nickname'),$val['uid']);
        }
        unset($val);
        $this->assign('lists', $lists);
        $this->assign('category',$category);
        $this->display('Widget/hot');
    }
} 