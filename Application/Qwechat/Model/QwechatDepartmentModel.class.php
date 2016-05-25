<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:14
 */

namespace Qwechat\Model;

use Think\Model;

class QwechatDepartmentModel extends Model
{
    protected $_validate = array(
        array('name', '1,99999', '应用名称不能为空', self::EXISTS_VALIDATE, 'length'),
        array('name', '0,100', '应用名称太长', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('close', '0', self::MODEL_INSERT)
       
    );

    public function updateDepartment($department=array())
    {
       
        $map['id']=$department['id'];
        $map['aid']=session('user_auth.aid');
        $department['aid']=session('user_auth.aid');

      $have = M("QwechatDepartment")->where($map)->find();
      $data = $this->create($department);
      
        if ($have){
            $this->where($map)->save();
            return "update";
        }else{
            $this->add();
            return "add";
        }
    }

     /**获得分类树
     * @param int  $id
     * @param bool $field
     * @return array
     * @auth 陈一枭
     */
    public function getTree($id = 1, $field = true){
        /* 获取当前分类信息 */
        

        /* 获取所有分类 */
        $map['aid']=session('user_auth.aid');
        $map['status']= array('EGT', 0);
       
        $list = $this->field($field)->where($map)->order('`order`')->select(); 
         foreach ($list as $key => $value) {
            $map['department'] = array('like', '%' . $value['id'] . '%');
            $list[$key]['total']=D('QwechatMember')->where($map)->count('id');     
        }
       

        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
      
        /* 获取返回数据 */
        if(isset($info)){ //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }

      /**
     * 获取部门详细信息
     * @param $id
     * @param bool $field
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function info($id, $field = true){
        /* 获取分类信息 */
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        $department=$this->field($field)->where($map)->find();
       
        
        return $department;
    }


     //传id 获取 子id
    public  function getData($map , $field='*' ){
      
    
      $map['status']= array('EGT', 0);
      $list = $this->where($map)->select(); 
      
      return $list;

    }

     public  function getChilds($map ,$father ,$ge=''){
      
    
      $list=$this->getData($map);
      $childs=$this->Childs($list,$father);
      array_push( $childs,$father) ;
      if ($ge) $childs=implode('|',$childs);

      return $childs;

    }



    //传id 获取 子id
    public  function Childs($list , $father ){
      
      
      $arr = array();
      foreach($list as $val){   
    
        if($val['parentid'] == $father){
          
          $arr[] = $val['id'];
          
          $arr = array_merge($arr , $this-> Childs($list , $val['id']));
          
        }
    
      }
      return $arr;

    }


   

   
}
