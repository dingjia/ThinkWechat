<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-10
 * Time: PM9:01
 */

namespace Core\Model;

use Think\Model;

class ExpressionModel extends Model
{
    protected $ROOT_PATH = '';
    public $pkg = '';
    public  function _initialize()
    {
        parent:: _initialize();
        $this->pkg = modC('EXPRESSION','miniblog','EXPRESSION');
        $this->ROOT_PATH = str_replace('/Application/Core/Model/ExpressionModel.class.php', '', str_replace('\\', '/', __FILE__));
    }

    /**
     * 获取当前主题包下所有的表情
     * @param boolean $flush 是否更新缓存，默认为false
     * @return array 返回表情数据
     */
    public function getAllExpression()
    {
        $pkg = $this->pkg;
        if($pkg =='all'){
            return $this->getAll();
        }else{
            return $this->getExpression($pkg);
        }
    }

    public function getExpression($pkg){
        if($pkg == 'miniblog'){
            $filepath =  "/Application/Core/Static/images/expression/" . $pkg;
        }else{
            $filepath =  "/Uploads/Expression/" . $pkg;
        }
        $list = $this->myreaddir($this->ROOT_PATH .$filepath);
        $res = array();
        foreach ($list as $value) {
            $file = explode(".", $value);
            $temp['title'] = $file[0];
            $temp['emotion'] = $pkg=='miniblog'?'['.$file[0].']': '[' . $file[0] . ':' . $pkg . ']';
            $temp['filename'] = $value;
            $temp['type'] = $pkg;
            $temp['src'] = __ROOT__ . $filepath . '/' . $value;
            $res[$temp['emotion']] = $temp;
        }
        return $res;
    }

    /**
     * getAll 获取所有主题的所有表情
     * @return array
     * @author:xjw129xjt xjt@ourstu.com
     */
    public function getAll()
    {

        $res = $this->getExpression('miniblog');
        $ExpressionPkg = $this->ROOT_PATH  . "/Uploads/Expression";
        $pkgList = $this->myreaddir($ExpressionPkg);
        foreach ($pkgList as $v) {
            $res =array_merge($res,$this->getExpression($v));
        }
        return $res;
    }

    public function myreaddir($dir)
    {
        $file = scandir($dir, 0);
        $i = 0;
        foreach ($file as $v) {
            if (($v != ".") and ($v != "..") and ($v != "info.txt")) {
                $list[$i] = $v;
                $i = $i + 1;
            }
        }
        return $list;
    }


    /**
     * 将表情格式化成HTML形式
     * @param string $data 内容数据
     * @return string 转换为表情链接的内容
     */
    public function parse($data)
    {
        $data = preg_replace("/img{data=([^}]*)}/", "<img src='$1'  data='$1' >", $data);
        return $data;
    }


    public function getCount($dir){
        $list = $this->myreaddir($dir);
        return count($list);
    }


    public function getPkgList($checkStatus = 1){
        $ExpressionPkg = $this->ROOT_PATH . "/Uploads/Expression";
        $pkgList = $this->myreaddir($ExpressionPkg);
        $config = get_kanban_config('PKGLIST','enable',array('miniblog'),'EXPRESSION');
        if(!$checkStatus || in_array('miniblog',$config)){
            $pkg['miniblog']['status'] =in_array('miniblog',$config)?1:0;
            $pkg['miniblog']['title'] = L('_DEFAULT_');
            $pkg['miniblog']['name'] = 'miniblog';
            $pkg['miniblog']['count'] = $this->getCount($this->ROOT_PATH . '/Application/Core/Static/images/Expression/miniblog');
        }
        foreach ($pkgList as $v) {
           $file =  file_get_contents($ExpressionPkg.'/'.$v.'/info.txt');
            $file = json_decode($file,true);
            if(!$checkStatus || in_array($v,$config)){
               $pkg[$v]['title'] = $file['title'];
               $pkg[$v]['name'] = $v;
               $pkg[$v]['status'] = in_array($v,$config)?1:0;
               $pkg[$v]['count'] =$this->getCount($ExpressionPkg.'/'.$v);
            }
        }
        return $pkg;
    }









    public function getPkgInfo($pkg ='miniblog'){
        $ExpressionPkg = $this->ROOT_PATH . "/Uploads/Expression";
        if($pkg =='miniblog'){
            $result['title'] = L('_DEFAULT_');
            $result['name'] = 'miniblog';
            $result['count'] = $this->getCount($this->ROOT_PATH . '/Application/Core/Static/images/Expression/miniblog');
        }else{
            $file =  file_get_contents($ExpressionPkg.'/'.$pkg.'/info.txt');
            $file = json_decode($file,true);
            $result['title'] = $file['title'];
            $result['name'] = $pkg;
            $result['count'] =$this->getCount($ExpressionPkg.'/'.$pkg);
        }

        return $result;

    }
}















