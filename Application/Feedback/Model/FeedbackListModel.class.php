<?php
/* 梦在想我(QQ345340585)整理*/
namespace Feedback\Model;
use Think\Model;

/**
 * 响应型接口基类
 */
class FeedbackListModel extends Model
{
	protected $Model;
	public $data;//接收到的数据，类型为关联数组
	var $returnParameters;//返回参数，类型为关联数组
    
    function _initialize()
    {
         $this->rank=array(1=>'好评',2=>'中评',3=>'差评');

       
     }

    protected $_validate = array(
        array('product', '1,2,3', '产品评价不能为空', self::MUST_VALIDATE , 'in'),
        array('service', '1,2,3', '服务评价不能为空', self::MUST_VALIDATE , 'in'),
        array('content', '1,1000', '评价不能为空', self::MUST_VALIDATE , 'length')
       
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('status', '1', self::MODEL_INSERT),
    );

	  /**
     * 获取分类详细信息
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
        return $this->field($field)->where($map)->find();
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
        $have=M('FeedbackList')->where('openid="'.$openid.'" and create_time>='.$today)->count();

        return $have;
    }



	
}
?>
