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

class NewsGameModel extends Model{

    protected $Model;
   
   
    
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
        
      
        if($data['id']){
            $res=$this->save($data);
         }else{
            $res=$this->add($data);
        }

       
       
        return $res;
    }

    public function getListByPage($map,$page=1,$order='update_time desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }

    public function getList($map,$order='view desc',$limit=5,$field='*')
    {
        $lists = $this->where($map)->order($order)->limit($limit)->field($field)->select();
        return $lists;
    }

     public function info($id,$field='*')
    {
        $game = $this->find($id);
        return $game;
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

    

} 