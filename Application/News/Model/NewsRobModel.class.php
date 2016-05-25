<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 下午1:22
 * @author 钱枪枪<8314028@qq.com>
 */

namespace News\Model;

use Think\Model;
use Wechat\Sdk\Wechat;

class NewsRobModel extends Model{

   
    public function getAttention(){
     
        $info = $this->where(array('is_attention' => 1))->find();

        if(!$info){
            $info = array(
                'type' => 'text',
                'content' => '欢迎您,感谢关注本微信公众号。',
            );
           return $info;
        }else{
           return $this->getReturnInfo($info);  
        }
       
    }

    /**
     * 获取所有机器人
     * @param $keywords 关键词
     * @param type 0模糊查询 1完全匹配
     * @param isbyid 使用id准确获取 
     * @return bool
     * @author:dingjia
     */
    public function getRobs($map=array(),$field=true){
        
         $robs = $this->field($field)->where($map)->select();
       
        return $robs;
    }

  /**
     * 获取机器人
     * @param $keywords 关键词
     * @param type 0模糊查询 1完全匹配
     * @param isbyid 使用id准确获取 
     * @return bool
     * @author:dingjia
     */
    public function getRob($keywords,$data=array()){
      
   
     if (is_numeric($keywords)){
        $map['id']=$keywords; 
     }else{
        $map['keywords']=array('like','%' . $keywords . '%');
     }

   
     $info = $this->where($map)->find();

     if (!$info) return;
     return $this->getReturnInfo($info,$data);
    }

    protected function getReturnInfo($info,$data=array()){
        
        $types = array(
            0 => Wechat::MSGTYPE_TEXT,
            1 => Wechat::MSGTYPE_TEXT,
            2 => Wechat::MSGTYPE_NEWS,
        );

         switch($info['type']){
            case 0:
            
            $data['rootkey']=$info['keywords'];
           
            $rootBack=D($info['content'].'/WechatRob')->qwechatRob($data); 
            if ( !is_array($rootBack)) {
                   $info['content']=$rootBack;
                   $info['type']="text";  
            }else{
                $info=$rootBack;
            }

            return $info;

               
                break;
            case 1:
                $content=  $info['content'];
                break;
          
            case 2:
                if(!$info['content']){
                    return array();
                }
                $news = array();

                // $tmp = D('News/News')->order('id ASC')->where(array('id'=> array('in', $info['content'])))->select();
                $sql = M();
                $tmp= $sql->query("select * from `ocenter_news` where  id in (".$info['content'].") order by field (id,".$info['content'].")");
             
             
                foreach($tmp as $key=> $item){
                    $news[] = $this->formartNews($key,$item);
                }
                $content= $news;
        }
       
       

        return array(
            'type' => $types[$info['type']],
            'content' => $content,
        );
    }

  

    protected function formartNews($key,$info){
        $info['description'] = strip_tags($info['description']);
        $description = substr($info['description'], 0, 50);
        $description = str_replace(' ', '', $description);
        $description = str_replace('\r', '', $description);
        $description = str_replace('\n', '', $description);
        $host = $this->getHost();
       
        return array(
            'Title' => $info['title'],
            'Description' => $info['description'],
            'PicUrl' => $host.get_cover(($key==0 ? $info['cover']:$info['cover2']), 'path'),
            'Url' => $info['source'] ? $info['source'] : $host . U('news/index/detail', array('id' => $info['id']))
        );
    }

    protected $host;
    protected function getHost(){
        return 'http://'.$_SERVER['HTTP_HOST'];
        if($this->host){
            return $this->host;
        }
        $url = S('WX_FRONT_SITEURL');
        if(!$url){
        $tmp = D('Config')->where(array('name' => '_WEIXIN_WX_SITEURL'))->find();
            $url = $tmp['value'];
            S('WX_FRONT_SITEURL', $url);
        }
        return $this->host = 'http://' . $url;
    }


  
     

}