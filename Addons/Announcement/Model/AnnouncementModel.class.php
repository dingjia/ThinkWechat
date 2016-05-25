<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-22
 * Time: 下午1:06
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Addons\Announcement\Model;

use Think\Model;

class AnnouncementModel extends Model{


    public function editData($data)
    {
        if($data['id']){
            $result=$this->save($data);
        }else{
            $data['create_time']=time();
            $data['status']=1;
            $result=$this->add($data);
        }
        return $result;
    }


    /**
     * 结束有效期
     * @param $ids
     * @return bool
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function setEnd($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $result=$this->where(array('id'=>array('in',$ids)))->setField('end_time',time());
        return $result;
    }

    /**
     * 分页获取列表
     * @param array $map 查询条件
     * @param int $page
     * @param string $order 排序方式
     * @param int $r
     * @param string $field
     * @return array
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function getListPage($map=array(),$page=1,$order='id desc',$r=20,$field='*')
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }

    /**
     * 获取公告列表
     * @param $map
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function getList($map)
    {
        $list=$this->where($map)->order('id desc')->field('id,title,icon,link,content,end_time')->select();
        return $list;
    }
} 