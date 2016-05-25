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

class NewsShowModel extends Model{

    protected $_auto = array(
        array('status', '1', self::MODEL_INSERT),
        array('do', '1', self::MODEL_INSERT),
        array('create_time', NOW_TIME, self::MODEL_INSERT),
    );

    public function editData($data)
    {
        $shower_info=D('Wechat/WechatMember')-> info($data['shower'],'id,openid,nickname');
        if ($shower_info){
        $data['nickname']=$shower_info['nickname'];
        $data['showerid']=$shower_info['id'];
        }



        $map['news_id']=$data['news_id'];
        $map['shower']=$data['shower'];


        $have=$this->where($map)->find();

        if($have){
            $res=$this->where($map)->save($data);
            
        }else{
            $res=$this->add($data);
        }

       

         //单独更新来源
        $this->where($map)->setInc($data['way']);


       
              

        return $res;
    }


    public function info($map, $field = true){
        /* 获取分类信息 */
       
        return $this->field($field)->where($map)->find();
    }

    public function myRank($openid, $news_id){
        /* 获取分类信息 */
        $map['shower']=$openid;
        $map['news_id']=$news_id;
        $my['view']=$this->where($map)->getfield('view');
        $my['rank']=($this->where('news_id='.$news_id.' and view>'.$my['view'])->count('id'))+1;
      
        return  $my;
    }




     

     public function show($data)
    {
       
      
        $data['do']=2;
        $data['create_time']=time();
      
       
        if ($status==1) D('News/News')->where(array('id'=>$news))->setInc('show_um');
            
      
        $res=$this->add($data);
       
        return $res;
    }

   


     public function getRanks($id,$limit=200)
    {
        // $data=S('news_'.$id.'_shows');
        if (!$data){
        $map['news_id']=$id;
        $data=$this->where($map) ->limit($limit)->order('view desc')->select();
            foreach ($data as $key => &$value) {
                     if (!$value['showerid']) {
                        $member=D('Wechat/WechatMember')->info($value['shower']);
                        $value['showerid']=$member['id'];
                    }
            }
        S('news_'.$id.'_shows',$data,600);
        }

        return $data;
    }

     public function getListByPage($map,$page=1,$order='view desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }


   

     

   
}