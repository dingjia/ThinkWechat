<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 下午3:30
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace News\Model;


use Think\Model;

class NewsViewModel extends Model{

    protected $_auto = array(
        array('status', '1', self::MODEL_INSERT),
        array('do', '1', self::MODEL_INSERT),
        array('create_time', NOW_TIME, self::MODEL_INSERT),
    );

    public function editData($data=array())
    {
      
        if($this->find($data['news_id'])){
            $res=$this->save($data);
        }else{
            $res=$this->add($data);
        }
        return $res;
    }

     //如果没有记录则算数
     public function view($data)
    {
        

        $data['status']=1;
        $data['create_time']=time();
        $data['ip']=get_client_ip();
        //查询是否有分享数据
        $map['news_id']=$data['news_id'];
        $map['shower']=$data['shower'];
        $have_show=D('NewsShow')->info($map,'id');
        if(!$have_show) {
          $data['view']=1;
          $data['nickname']=$data['shower_name'];
          $have_show['show_id']= D('NewsShow')->editData($data);
        }
        unset($map);

        $map['show_id']=$have_show['id'];
        // $map['ip']=$data['ip'];
        $map['viewer']=$data['viewer'];
        $map['create_time'] = array('EGT',time()-3600*12);
        
        $have_view=$this->where($map)->find();
       
        if(!$have_view){
         $data['show_id']=$have_show['id'];  
         $res=$this->add($data);
        
         D('NewsShow')->where(array('id'=>$have_show['id']))->setInc('view');
        }

        return $res;
    }


    

   


    

     

   
}