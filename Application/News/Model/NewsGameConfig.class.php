<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 下午1:22
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace News\Model;


use Think\Model;

class NewsGameConfigModel extends Model{

     function _initialize()
    {
         
    }

    protected $_validate = array(
        array('name', 'require', '游戏名称不能为空'),
        array('url', 'require', '游戏入口不能为空'),
     
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('status', '1', self::MODEL_INSERT),
    );

    public function editData($data)
    {
       $map['gameid']=$gameid;
       $map['aid'] = session('user_auth.aid'); 
       $config=M('NewsGameConfig')->where($map)->find();

        $data=$this->create();
       
        if($config){
            $res=$this->save();
         }else{
            $res=$this->add();
        }
       
        return $res;
    }

    public function haveConfig($gameid)
    {
       $map['gameid']=$gameid;
       $map['aid'] = session('user_auth.aid'); 
       $config=$this->where($map)->find();
       return $config;
    }

    public function getList($map,$order='view desc',$limit=5,$field='*')
    {
        $lists = $this->where($map)->order($order)->limit($limit)->field($field)->select();
        return $lists;
    }

    public function setDead($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->setField('dead_line',time());
        return $res;
    }

    public function getData($id)
    {
        if($id>0){
            $map['id']=$id;
            $data=$this->where($map)->find();
            if($data){
                $data['detail']=D('News/NewsDetail')->getData($id);
            }
            return $data;
        }
        return null;
    }

    /**
     * 获取推荐位数据列表
     * @param $pos 推荐位 1-系统首页，2-推荐阅读，4-本类推荐
     * @param null $category
     * @param $limit
     * @param bool $field
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function position($pos, $category = null, $limit = 5, $field = true,$order='sort desc,view desc'){
        $map = $this->listMap($category, 1, $pos);
        $res=$this->field($field)->where($map)->order($order)->limit($limit)->select();
        /* 读取数据 */
        return $res;
    }

    /**
     * 设置where查询条件
     * @param  number  $category 分类ID
     * @param  number  $pos      推荐位
     * @param  integer $status   状态
     * @return array             查询条件
     */
    private function listMap($category, $status = 1, $pos = null){
        /* 设置状态 */
        $map = array('status' => $status);

        /* 设置分类 */
        if(!is_null($category)){
            $cates=D('News/NewsCategory')->getCategoryList(array('pid'=>$category,'status'=>1));
            $cates=array_column($cates,'id');
            $map['category']=array('in',array_merge(array($category),$cates));
        }
        $map['dead_line'] = array('gt',time());

        /* 设置推荐位 */
        if(is_numeric($pos)){
            $map[] = "position & {$pos} = {$pos}";
        }

        return $map;
    }

} 