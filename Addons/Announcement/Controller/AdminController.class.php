<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-22
 * Time: 上午11:12
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Addons\Announcement\Controller;


use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Controller\AddonsController;

class AdminController extends AddonsController{

    protected $announcementModel;

    public function _initialize()
    {
        parent::_initialize();
        $this->announcementModel=D('Addons://Announcement/Announcement');
    }

    /**
     * 公告列表
     * @param int $page
     * @param int $r
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function buildList($page=1,$r=20)
    {
        $map['status']=1;
        $aDown=I('down',0,'intval');
        $this->assign('down',$aDown);
        if($aDown){
            $map['end_time']=array('elt',time());
        }else{
            $map['end_time']=array('gt',time());
        }
        list($list,$totalCount)=$this->announcementModel->getListPage($map,$page,'id desc',$r);
        $builder=new AdminListBuilder();
        $builder->title('公告列表');
        $builder->buttonNew(addons_url('Announcement://admin/edit'));
        $builder->setSelectPostUrl(addons_url('Announcement://admin/buildList'))->select('','down','select','','','',array(array('id'=>0,'value'=>'当前公告'),array('id'=>1,'value'=>'历史公告')));
        $builder->keyId()->keyTitle()->keyIcon()->keyText('link','链接')->keyText('content','内容')->keyCreateTime()->keyTime('end_time','截止日期');
        $builder->keyDoActionEdit('Announcement://admin/edit?id=###|addons_url');
        if(!$aDown){
            $builder->ajaxButton(addons_url('Announcement://admin/setEnd'),'','设为到期')->keyDoAction('Announcement://admin/setEnd?ids=###|addons_url','设为到期');
        }
        $builder->data($list)->pagination($totalCount,$r);
        $builder->display();
    }

    /**
     * 编辑公告
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function edit()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"新增";
        if(IS_POST){
            $aId&&$data['id']=$aId;
            $data['title']=I('post.title','','op_t');
            $data['icon']=I('post.icon','icon-star','op_t');
            $data['link']=I('post.link');
            if(mb_strlen($data['link'],'utf-8')&&!in_array(strtolower(substr($data['link'], 0, 6)), array('http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://'))) {
                $data['link'] = 'http://'.$data['link'];
            }
            $data['content']=I('post.content','','op_t');
            $data['end_time']=intval(I('post.end_time'));
            $result=$this->announcementModel->editData($data);
            if($result){
                S('Announcement_list',null);//清空缓存
                $this->success($title.'公告成功！',addons_url('Announcement://admin/buildList'));
            }else{
                $this->error($title.'公告失败！'.$this->announcementModel->getError());
            }
        }else{
            if($aId){
                $data=$this->announcementModel->where(array('id'=>$aId))->find();
            }
            $builder=new AdminConfigBuilder();
            $builder->title($title.'公告')->data($data);
            $builder->keyId()->keyText('title','标题')->keyIcon('icon','图标')->keyText('link','链接')->keyTextArea('content','内容')->keyTime('end_time','有效期')->keyDefault('end_time',time()+604800);
            $builder->buttonSubmit()->buttonBack()
                ->display();
        }
    }

    /**
     * 设为过期
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function setEnd()
    {
        $ids=I('ids');
        !is_array($ids)&&$ids=explode(',',$ids);
        $result=$this->announcementModel->setEnd($ids);
        if($result){
            S('Announcement_list',null);//清空缓存
            $this->success('操作成功！',addons_url('Announcement://admin/buildList'));
        }else{
            $this->error('操作失败！'.$this->announcementModel->getError());
        }
    }
} 