<?php
/* 梦在想我(QQ345340585)整理*/
namespace Feedback\Model;
use Think\Model;

/**
 * 响应型接口基类
 */
class FeedbackGiftModel extends Model
{
	protected $Model;
	public $data;//接收到的数据，类型为关联数组
	var $returnParameters;//返回参数，类型为关联数组
    
    function _initialize()
    {
         $this->rank=array(1=>'好评',2=>'中评',3=>'差评');

       
     }

    protected $_validate = array(
       
       
    );

    protected $_auto = array(
       
        array('status', '0', self::MODEL_INSERT),
    );

	  /**
     * 获取分类详细信息
     * @param $id
     * @param bool $field
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function info($appid,$field='gift4'){
        /* 获取分类信息 */
        $map['appid'] =$appid;
        $map['status'] = array('EGT', 0);
        return $this->where($map)->getField($field);
    }

     public function getData($map, $field = true){
       
        return $this->field($field)->where($map)->select();
    }

    public function editData()
    {
        
       if (!$data=$this->create()) {
         return false;
       }

        if($data['id']){
            $res=$this->save();
        }else{
            $res=$this->add();
        }
        return $res;
    }

    public function haveToday($openid)
    {
       
        $today = date('Y-m-d', time());
        $today = strtotime($today); 
        $have=M('FeedbackList')->where('openid="'.$openid.'" and create_time>='.$today)->getField('id');

        return $have;
    }



	
}
?>
