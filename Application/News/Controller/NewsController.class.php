<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 上午10:21
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Admin\Controller;


use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;
use Common\Model\ContentHandlerModel;

class NewsController extends AdminController{

    protected $newsModel;
    protected $newsDetailModel;
    protected $newsCategoryModel;

    function _initialize()
    {
        parent::_initialize();
        $this->newsModel = D('News/News');
        $this->newsDetailModel = D('News/NewsDetail');
        $this->newsCategoryModel = D('News/NewsCategory');
        $this->showModel = D('News/NewsShow');
        $this->types = array(
            0 => '应用回复',
            1 => '文本回复',
            2 => '图文回复',
        );
    }

    public function newsCategory()
    {
        //显示页面
        $builder = new AdminTreeListBuilder();

        $tree = $this->newsCategoryModel->getTree(0, 'id,title,sort,pid,status');

        $builder->title('资讯分类管理')
            ->suggest('禁用、删除分类时会将分类下的文章转移到默认分类下')
            ->buttonNew(U('News/add'))
            ->data($tree)
            ->display();
    }

    /**分类添加
     * @param int $id
     * @param int $pid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function add($id = 0, $pid = 0)
    {
        $title=$id?"编辑":"新增";
        if (IS_POST) {
            if ($this->newsCategoryModel->editData()) {
                S('SHOW_EDIT_BUTTON',null);
                $this->success($title.'成功。', U('News/newsCategory'));
            } else {
                $this->error($title.'失败!'.$this->newsCategoryModel->getError());
            }
        } else {
            $builder = new AdminConfigBuilder();

            if ($id != 0) {
                $data = $this->newsCategoryModel->find($id);
            } else {
                $father_category_pid=$this->newsCategoryModel->where(array('id'=>$pid))->getField('pid');
                if($father_category_pid!=0){
                    $this->error('分类不能超过二级！');
                }
            }
            if($pid!=0){
                $categorys = $this->newsCategoryModel->where(array('pid'=>0,'status'=>array('egt',0)))->select();
            }
            $opt = array();
            foreach ($categorys as $category) {
                $opt[$category['id']] = $category['title'];
            }
            $builder->title($title.'分类')
                ->data($data)
                ->keyId()->keyText('title', '标题')
                ->keySelect('pid', '父分类', '选择父级分类', array('0' => '顶级分类') + $opt)->keyDefault('pid',$pid)
                ->keyRadio('can_post','前台是否可投稿','',array(0=>'否',1=>'是'))->keyDefault('can_post',1)
                ->keyRadio('need_audit','前台投稿是否需要审核','',array(0=>'否',1=>'是'))->keyDefault('need_audit',1)
                ->keyInteger('sort','排序')->keyDefault('sort',0)
                ->keyStatus()->keyDefault('status',1)
                ->buttonSubmit(U('News/add'))->buttonBack()
                ->display();
        }

    }

    /**
     * 设置资讯分类状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function setStatus($ids, $status)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        if(in_array(1,$ids)){
            $this->error('id为 1 的分类是网站基础分类，不能被禁用、删除！');
        }
        if($status==0||$status==-1){
            $map['category']=array('in',$ids);
            $this->newsModel->where($map)->setField('category',1);
        }
        $builder = new AdminListBuilder();
        $builder->doSetStatus('newsCategory', $ids, $status);
    }
//分类管理end

    public function config()
    {
        $builder=new AdminConfigBuilder();
        $data=$builder->handleConfig();
        $default_position=<<<str
1:系统首页
2:推荐阅读
4:本类推荐
str;

        $builder->title('资讯基础设置')
            ->data($data);

        $builder->keyTextArea('NEWS_SHOW_POSITION','展示位配置')->keyDefault('NEWS_SHOW_POSITION',$default_position)
            ->keyRadio('NEWS_ORDER_FIELD','前台列表排序','前台排序第二依据；第一依据是资讯排序sort字段倒序',array('view'=>'阅读量','create_time'=>'创建时间','update_time'=>'更新时间'))->keyDefault('NEWS_ORDER_FIELD','create_time')
            ->keyRadio('NEWS_ORDER_TYPE','列表排序方式','',array('asc'=>'升序','desc'=>'降序'))->keyDefault('NEWS_ORDER_TYPE','desc')
            ->keyInteger('NEWS_PAGE_NUM','列表每页展示数','')->keyDefault('NEWS_PAGE_NUM','20')

            ->keyText('NEWS_SHOW_TITLE', '标题名称', '在首页展示块的标题')->keyDefault('NEWS_SHOW_TITLE','热门资讯')
            ->keyText('NEWS_SHOW_COUNT', '显示资讯的个数', '只有在网站首页模块中启用了资讯块之后才会显示')->keyDefault('NEWS_SHOW_COUNT',4)
            ->keyRadio('NEWS_SHOW_TYPE', '资讯的筛选范围', '', array('1' => '后台推荐', '0' => '全部'))->keyDefault('NEWS_SHOW_TYPE',0)
            ->keyRadio('NEWS_SHOW_ORDER_FIELD', '排序值', '展示模块的数据排序方式', array('view' => '阅读数', 'create_time' => '发表时间', 'update_time' => '更新时间'))->keyDefault('NEWS_SHOW_ORDER_FIELD','view')
            ->keyRadio('NEWS_SHOW_ORDER_TYPE', '排序方式', '展示模块的数据排序方式', array('desc' => '倒序，从大到小', 'asc' => '正序，从小到大'))->keyDefault('NEWS_SHOW_ORDER_TYPE','desc')
            ->keyText('NEWS_SHOW_CACHE_TIME', '缓存时间', '默认600秒，以秒为单位')->keyDefault('NEWS_SHOW_CACHE_TIME','600')

            ->group('基本配置', 'NEWS_SHOW_POSITION,NEWS_ORDER_FIELD,NEWS_ORDER_TYPE,NEWS_PAGE_NUM')->group('首页展示配置', 'NEWS_SHOW_COUNT,NEWS_SHOW_TITLE,NEWS_SHOW_TYPE,NEWS_SHOW_ORDER_TYPE,NEWS_SHOW_ORDER_FIELD,NEWS_SHOW_CACHE_TIME')
            ->groupLocalComment('本地评论配置','index')
            ->buttonSubmit()->buttonBack()
            ->display();
    }


    //资讯列表start
    public function index($page=1,$r=20,$title='')
    {
        $aCate=I('cate',0,'intval');
        // if($aCate){
        //     $cates=$this->newsCategoryModel->getCategoryList(array('pid'=>$aCate));
        //     if(count($cates)){
        //         $cates=array_column($cates,'id');
        //         $cates=array_merge(array($aCate),$cates);
        //         $map['category']=array('in',$cates);
        //     }else{
        //         $map['category']=$aCate;
        //     }
        // }
        // $aDead=I('dead',0,'intval');
        // if($aDead){
        //     $map['dead_line']=array('elt',time());
        // }else{
        //     $map['dead_line']=array('gt',time());
        // }
        // $aPos=I('pos',0,'intval');
        // /* 设置推荐位 */
        // if($aPos>0){
        //     $map[] = "position & {$aPos} = {$aPos}";
        // }

        $map['status']=1;
        $map['aid']= session('user_auth.aid');
        if ($title !='') $map['title']= array('like','%'.$title.'%');
        $positions=$this->_getPositions(1);

        list($list,$totalCount)=$this->newsModel->getListByPage($map,$page,'update_time desc','*',$r);
        $category=$this->newsCategoryModel->getCategoryList(array('status'=>array('egt',0)),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $optCategory=$category;
        foreach($optCategory as &$val){
            $val['value']=$val['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();
        $builder->title('文章列表')  ->setStatusUrl(U('News/setNewsStatus'))
            ->data($list)->buttonEnable()->buttonDisable()->buttonDelete()
            ->setSelectPostUrl(U('Admin/News/index'))
            ->select('','cate','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$optCategory))
            ->select('','dead','select','','','',array(array('id'=>0,'value'=>'当前资讯'),array('id'=>1,'value'=>'历史资讯')))
            ->select('推荐位：','pos','select','','','',array_merge(array(array('id'=>0,'value'=>'全部(含未推荐)')),$positions))
            ->buttonNew(U('News/editNews'))
            ->keyId()->keyText('title','标题')->keyText('category','分类')->keyText('view','阅读')->keyLink('show_um','分享','News/showList?news_id=###')
            ->setSearchPostUrl(U('New/index'))->search('标题', 'title')
            ->keyStatus()->keyCreateTime()
            ->keyDoActionEdit('News/editNews?id=###');
        if(!$aDead){
            $builder->ajaxButton(U('News/setDead'),'','设为到期')->keyDoAction('News/setDead?ids=###','设为到期');
        }
        $builder->pagination($totalCount,$r)
            ->display();
    }

     

     //资讯列表start
    public function showList($page=1,$r=20,$news_id='')
    {
       
      

        $map['news_id']=$news_id;

       
        list($list,$totalCount)=$this->showModel->getListByPage($map,$page,'view desc','*',$r);
       foreach($list as $key=> &$val){
         if (!$val['showerid'])  {
         $member=M('WechatMember')->where(array('openid'=>$val['shower']))->find();
         $val['showerid']=$member['id'];
         $this->showModel->where(array('id'=>$val['id']))->save(array('showerid'=>$member['id']));
         }
       
         
        }
     
       

        $builder=new AdminListBuilder();
        $builder->title('互动数据')
            ->data($list)
            ->setSelectPostUrl(U('Admin/News/index'))
            
            ->buttonNew(U('News/editNews'))
            ->keyText('nickname','分享者')->keyText('showerid','粉丝号')->keyText('appmessage','分享到群')->keyText('timeline','分享到朋友圈')
            ->keyText('qq','分享到QQ')->keyText('weibo','分享到微博')
            ->keyText('view','阅读')
            ->keyDoActionEdit('News/editNews?id=###');
        
        $builder->pagination($totalCount,$r)
            ->display();
    }

    //待审核列表
    public function audit($page=1,$r=20)
    {
        $aAudit=I('audit',0,'intval');
        if($aAudit==3){
            $map['status']=array('in',array(-1,2));
        }elseif($aAudit==2){
            $map['dead_line']=array('elt',time());
            $map['status']=2;
        }elseif($aAudit==1){
            $map['status']=-1;
        }else{
            $map['status']=2;
            $map['dead_line']=array('gt',time());
        }
        list($list,$totalCount)=$this->newsModel->getListByPage($map,$page,'update_time desc','*',$r);
        $cates=array_column($list,'category');
        $category=$this->newsCategoryModel->getCategoryList(array('id'=>array('in',$cates),'status'=>1),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();

        $builder->title('资讯列表（审核通过的不在该列表中）')
            ->data($list)
            ->setStatusUrl(U('News/setNewsStatus'))
            ->buttonEnable(null,'审核通过')
            ->buttonModalPopup(U('News/doAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
            ->setSelectPostUrl(U('Admin/News/audit'))
            ->select('','audit','select','','','',array(array('id'=>0,'value'=>'待审核'),array('id'=>1,'value'=>'审核失败'),array('id'=>2,'value'=>'已过期未审核'),array('id'=>3,'value'=>'全部审核')))
            ->keyId()->keyUid()->keyText('title','标题')->keyText('category','分类')->keyText('description','摘要')->keyText('sort','排序');
        if($aAudit==1){
            $builder->keyText('reason','审核失败原因');
        }
        $builder->keyTime('dead_line','有效期至')->keyCreateTime()->keyUpdateTime()
            ->keyDoActionEdit('News/editNews?id=###')
            ->pagination($totalCount,$r)
            ->display();
    }

    /**
     * 审核失败原因设置
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function doAudit()
    {
        if(IS_POST){
            $ids=I('post.ids','','text');
            $ids=explode(',',$ids);
            $reason=I('post.reason','','text');
            $res=$this->newsModel->where(array('id'=>array('in',$ids)))->setField(array('reason'=>$reason,'status'=>-1));
            if($res){
                $result['status']=1;
                $result['url']=U('Admin/News/audit');
                //发送消息
                $messageModel=D('Common/Message');
                foreach($ids as $val){
                    $news=$this->newsModel->getData($val);
                    $tip = '你的资讯投稿【'.$news['title'].'】审核失败，失败原因：'.$reason;
                    $messageModel->sendMessage($news['uid'], '资讯投稿审核失败！',$tip,  'News/Index/detail',array('id'=>$val), is_login(), 2);
                }
                //发送消息 end
            }else{
                $result['status']=0;
                $result['info']='操作失败！';
            }
            $this->ajaxReturn($result);
        }else{
            $ids=I('ids');
            $ids=implode(',',$ids);
            $this->assign('ids',$ids);
            $this->display(T('News@Admin/audit'));
        }
    }

    public function setNewsStatus($ids,$status=1)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $builder = new AdminListBuilder();
        S('news_home_data',null);
        //发送消息
        $messageModel=D('Common/Message');
        // foreach($ids as $val){
        //     $news=$this->newsModel->getData($val);
        //     $tip = '你的资讯投稿【'.$news['title'].'】审核通过。';
        //     $messageModel->sendMessage($news['uid'],'资讯投稿审核通过！', $tip,  'News/Index/detail',array('id'=>$val), is_login(), 2);
        // }
        //发送消息 end
        $builder->doSetStatus('News', $ids, $status);
    }

    public function editNews()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"新增";

        if(IS_POST){
            $aId&&$_POST['id']=$aId;
            

            $result=$this->newsModel->editData();
            if($result){
                S('news_home_data',null);
                $aId=$aId?$aId:$result;
                S('news_'.$aId,null); 
                $this->success($title.'成功！',U('News/editNews',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->newsModel->getError());
            }
        }else{
            $wechats=M('Wechat')->where(array())->select();
            $position_options=$this->_getPositions();
            if($aId){
                $data=$this->newsModel->find($aId);
                $detail=$this->newsDetailModel->find($aId);
                $data['content']=$detail['content'];
                $data['template']=$detail['template'];
                $position=array();
                foreach($position_options as $key=>$val){
                    if($key&$data['position']){
                        $position[]=$key;
                    }
                }
                
                $data['position']=implode(',',$position);
                $data['show_gift']=explode(',',$data['show_gift']);
                $data['view_gift']=explode(',',$data['view_gift']);
            }
           
            $category=$this->newsCategoryModel->getCategoryList(array('status'=>array('egt',0)),1);
            $options=array();
            foreach($category as $val){
                $options[$val['id']]=$val['title'];
            }

            $map['aid']=session('user_auth.aid');
            $map['wechat_type']=3;
            $wechats = M('Wechat')->field('id,name')->where ($map)->select();
            $wechats =array_column($wechats, 'name', 'id');
            $cards = M('WechatCard')->field('id,concat(brand_name,title)')->select();
           
            $builder=new AdminConfigBuilder();
            $builder->title($title.'资讯')
                ->data($data)
                ->keyId('id','ID','',61)->keySelect('category','分类','',$options,62)
                ->keyText('title','标题')
                ->keyTextArea('digest','摘要')
                ->keyEditor('content','内容','','all',array('width' => '700px', 'height' => '400px'))
                ->keyText('author','作者','','',61)->keyText('follow_url','关注链接','','',62)
                ->keyText('top_title','微信顶部')
                ->keyText('show_title','分享标题') ->keyText('show_description','分享描述')->keyText('show_link','分享链接')
                ->keyText('source','跳转到','')
                ->keyText('show_gift_url','分享后跳转')
                ->keySingleImage('cover','封面','建议900*500像素')
                ->keySingleImage('cover2','分享图片','建议200*200像素')
                ->keySelect('login_appid','登录微信','',$wechats)
                
                
                // ->keyText('show_limit','分享数量','要求分享的数量')
                // ->keyChosen('show_gift','转发奖品','奖品，以卡券的方式发送',$cards)
                // ->keyChosen('view_gift','阅读奖品','奖品，以卡券的方式发送',$cards)
                ->keyInteger('view','阅读量')->keyDefault('view',0)
                ->keyInteger('show_um','转发量')->keyDefault('show_um',0)
                ->keyInteger('comment','评论数')->keyDefault('comment',0)
                ->keyInteger('collection','收藏量')->keyDefault('collection',0)
                ->keyInteger('sort','排序')->keyDefault('sort',0)
                ->keyTime('start_line','开始时间','','datetime',61)
                ->keyTime('dead_line','结束时间','','datetime',62)->keyDefault('dead_line',2147483640)
                ->keyText('template','模板')
                
                ->keyCheckBox('position','推荐位','多个推荐，则将其推荐值相加',$position_options)
                ->keyStatus()->keyDefault('status',1)

                ->group('基础','id,category,digest,title,cover,content,source')
                ->group('营销','author,follow_url,show_title,cover2,show_description,show_link,top_title,login_appid,start_line,dead_line')
                ->group('统计','view,show_um,comment,sort,position,template,status')

                ->buttonSubmit()->buttonBack()
                ->display();
        }
    }

    public function setDead($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $res=$this->newsModel->setDead($ids);
        if($res){
             S('news_home_data',null);
            $this->success('操作成功！',U('News/index'));
        }else{
            $this->error('操作失败！'.$this->newsModel->getError());
        }
    }


    private function _checkOk($data=array()){
        if(!mb_strlen($data['title'],'utf-8')){
            $this->error('标题不能为空！');
        }
        if(mb_strlen($data['content'],'utf-8')<20){
            $this->error('内容不能少于20个字！');
        }
        return true;
    }

    private function _getPositions($type=0)
    {
        $default_position=<<<str
1:系统首页
2:推荐阅读
4:本类推荐
str;
        $positons=modC('NEWS_SHOW_POSITION',$default_position,'News');
        $positons = str_replace("\r", '', $positons);
        $positons = explode("\n", $positons);
        $result=array();
        if($type){
            foreach ($positons as $v) {
                $temp = explode(':', $v);
                $result[] = array('id'=>$temp[0],'value'=>$temp[1]);
            }
        }else{
            foreach ($positons as $v) {
                $temp = explode(':', $v);
                $result[$temp[0]] = $temp[1];
            }
        }

        return $result;
    }


    // 图片素材管理
    public function pictures($page=1,$r=10){
      
    
        $where = $cats = array();
        if(I('get.type')){
            $where['type'] = I('get.type');
        }
        if(I('get.cid')){
            $where['cid'] = I('get.cid');
        }
        $where['is_news'] = 0;

        $reportCount = D('Picture')->where($where)->count();
        $list =  D('Picture')->page($page,$r)->where($where)->order('id DESC')->select();
        $types = array(
            1 => '文本回复',
            2 => '图文回复',
        );
        foreach($list as $key => $item){
          $list[$key]['path'] = "<img src=\"/2016/{$item['path']}\" width=\"50\" height=\"50\">";
        }
        $builder = new AdminListBuilder();
        $builder->title("图片素材") ->buttonNew(U('News/editpicture'))
            ->setStatusUrl(U('News/setPictureStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
        
            ->keyId()
            ->keyText('type', "类型")
            ->keyText('path', "图片")
            ->keyCreateTime();
            // ->keyDoActionEdit('Wechat/edit?id=###')
            // ->keyDoActionEdit('Wechat/attention?id=###', '设为关注回复')
            // ->buttonNew(U('Wechat/rtext'),"新增文本")->buttonNew(U('Wechat/rtextimgs'),"新增多图文")->buttonDelete(U('del'));

        $builder->data($list);
        $builder->pagination($reportCount, $r);
        $builder->display();
    }


  

    // 图片素材管理
    public function game($page=1,$r=10){
        
         //读取数据
        $map['aid']= session('user_auth.aid');
        $map['status'] = array('GT', -1);
        $model = M('NewsGame');
        $list = $model->where($map)->page($page, $r)->select();
        $totalCount = $model->where($map)->count();
        
      

        $builder = new AdminListBuilder();
        $builder->title("游戏素材") ->buttonNew(U('News/editgame'))  ->buttonNew(U('News/gameCloud'),'检测可用游戏')
            ->setStatusUrl(U('News/setWechatStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            
            ->keyId()
            ->keyText('name', "游戏名",'100px')
            ->keyText('description', "描述",'200px')
            ->keyText('play_um', "使用人数")
            ->keyDoActionEdit('gameScore?gameid=###','参与者')
            ->keyDoActionEdit('editgame?id=###');



          
        $builder->data($list);
        $builder->pagination($reportCount, $r);
        $builder->display();
    }

      public function setGameStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('NewsGame', $ids, $status);
    }

      public function gameCloud()
    {
        $games=array(
            array('aid'=>session('user_auth.aid'),'name'=>'2048','token'=>'2048','description'=>'一款非常火的小游戏'),
            array('aid'=>session('user_auth.aid'),'name'=>'大转盘','token'=>'zhuan','description'=>'一款抽奖小游戏')

            );
        foreach ($games as $key => $game) {
            $map['aid']= session('user_auth.aid');
            $map['token']= $game['token'];
            $have=D('News/NewsGame')->where($map) ->find();
            if (!$have){
             $res=D('News/NewsGame') ->add($game);   
            }
        }

        $this->success('成功更新游戏云',U('game'));

    

    }


     public function editgame($id = null)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
         

            if (D('News/NewsGame')->editData($_POST)) {
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('game'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').D('NewsGame')->getError());
            }



        } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $game = M('NewsGame')->where(array('id' => $id))->find();
                $url='http://'.$_SERVER['HTTP_HOST']. U('news/index/game',array('id'=>$id,'openid'=>'{粉丝OPENID}'));
            } else {
                $game = array('create_time' => time());
            }

             $map['aid'] = array('eq',session('user_auth.aid'));
             $map['wechat_type'] = 3;
             $wechats = M('Wechat')->where($map)->field('id,name')->select();
             $wechats =array_column($wechats, 'name', 'id');
             $week=array('Mon'=>'星期一','Tue'=>'星期二','Wed'=>'星期三','Thu'=>'星期四','Fri'=>'星期五','Sat'=>'星期六','Sun'=>'星期天');


            
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title(($isEdit ? '修改游戏' : '添加游戏').':该游戏调用'.$url)
                ->data($game)
                ->keyId()
                ->keyText('name','游戏名称','','',61)->keyText('token','游戏标示','','',62)
                ->keySingleImage('logo', '游戏图标')
                ->keyText('description','游戏简介', '建议15字内')
                ->keySelect('appid', '微信登录', '',  $wechats)
                ->keyText('limit', '限制次数')
                ->keyCheckBox('showtime', '开放时间','',$week)
                ->keyText('play_um', '使用人数')

                ->keyText('gift1', '一等奖')
                ->keyText('gift2', '二等奖')
                ->keyText('gift3', '三等奖')
                ->keyText('gift4', '四等奖')
                ->keyText('gift5', '五等奖')
                ->keyText('gift6', '六等奖')
                
                ->group('基本信息', 'id,name,token,description,logo,token,appid')
                ->group('奖品配置', 'gift1,gift2,gift3,gift4,gift5,gift6')
                ->group('运营数据', 'limit,showtime,play_um')
               
                ->buttonSubmit(U('editgame'))->buttonBack()
                ->display();

              
        }

    }

   


     // 图片素材管理
    public function gameScore($page=1,$r=15,$nickname='',$game_id=0,$status=''){
        
         //读取数据
         $map['aid']= session('user_auth.aid');
         $map['status'] = array('GT', -1);
         if ($nickname != '')  $map['nickname'] = array('like', '%' . $nickname . '%');
         if ($game_id != '')  $map['game_id']=$game_id;
         if ($status != '')  $map['status']=$status;
        
        $model = M('NewsGameScore');
        $list = $model->where($map)->page($page, $r)->select();
        $totalCount = $model->where($map)->count();

        foreach ($list as $key => &$value) {
           $value['status']=$value['status']==0?'未领取':'已领取';
        }

        
     
        $games = M('NewsGame')->where(array('aid'=>session('user_auth.aid')))->field('id,name as value')->select();
      

        $builder = new AdminListBuilder();
        $builder->title("游戏参与者")
        ->setStatusUrl(U('Wechat/setWechatStatus'))->buttonEnable()->buttonDisable()->buttonDelete();
            
        $builder ->setSelectPostUrl(U('Admin/News/gameScore'))
            ->select('','game_id','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$games))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'未领取'),array('id'=>1,'value'=>'已领取'),array('id'=>1,'value'=>'失效')))
            ->setSearchPostUrl(U('Admin/News/gameScore'))->search('游戏者', 'nickname')
            ->keyId()
            ->keyText('nickname', "游戏者")
            ->keyText('game', "游戏")
            ->keyText('title', "关卡")
            ->keyText('score', "分数")
            ->keyText('gift_rank', "奖品等级")
            ->keyText('gift', "奖品")
            ->keyText('status', "状态");
            

          
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }



    //机器人自动回复
    protected function getRobModel(){
        return D('News/NewsRob');
    }


    public function rob($page=1,$r=10,$keywords='',$type=''){

        $builder = new AdminListBuilder();
        $builder->title("机器人客服");
        $map = array('status' => array('EGT', 0));
        $map['aid'] = session('user_auth.aid'); 
        if ($keywords !='') $map['keywords|title'] = $keywords;
        if ($type) $map['type'] = $type;

        
        $reportCount = $this->getRobModel()->where($map)->count();
        $list = $this->getRobModel()->where($map)->order('id DESC')->select();
        $types = array(
            0 => '应用回复',
            1 => '文本回复',
            2 => '图文回复',
        );
        foreach($list as $key => $item){
            if($item['type'] == 2){
               
                if($item['content']){
                    $initHtml = '';
                    $sql = M();
                    $news = $sql->query("select * from `ocenter_news` where  id in (".$item['content'].") order by field (id,".$item['content'].")");
                    foreach($news as $k => $v){
                        $img = "";
                        $v['cover'] = get_cover($v['cover'], 'path');
                        $img = "<img src=\"{$v['cover']}\" width=\"30\" height=\"30\">";
                        $initHtml .= "<p>{$img} {$v['title']}</p>";
                    }
                }
                $list[$key]['content'] = $initHtml;
            }
            $list[$key]['type'] = $this->types[$list[$key]['type']];
            $list[$key]['is_root'] = $list[$key]['is_root'] == 0? '普通命令' : '系统命令';
        }
        $builder
            ->keyId()
            ->setStatusUrl(U('News/setRobStatus'))->buttonEnable()->buttonDisable()->buttonDelete()
            ->setSelectPostUrl(U('News/rob'))
            ->select('','type','select','','','',array(array('id'=>'','value'=>'命令类型'),array('id'=>0,'value'=>'应用回复'),array('id'=>1,'value'=>'文本回复'),array('id'=>2,'value'=>'图文回复')))
            ->setSearchPostUrl(U('Admin/Qwechat/member'))->search('关键词', 'keywords')
            ->keyText('keywords', "关键词") ->keyText('title', "名称")
            ->keyText('type', "类型")
            ->keyText('content', "回复内容")->keyStatus()
             ->keyDoActionEdit('News/edit?id=###')
            // ->keyDoActionEdit('News/attention?id=###', '设为关注回复')
            ->buttonNew(U('News/rtext'),"新增文本")->buttonNew(U('News/rtextimgs'),"新增多图文")->buttonNew(U('News/rfunction'),"新增应用");

        $builder->data($list);
        
        $builder->display();
    }

     public function setRobStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('NewsRob', $ids, $status);
     }

     public function rtext(){
        $id = I('get.id');
        $dataSet = array(
            'type' => 1
        );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();
        $data = $this->getRobModel()->find($id);
        if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        $wechats =array_column($wechats, 'value', 'id');
        $qwechats = M('Qwechat')->where($map)->field('id,name as value')->select();
        $qwechats =array_column($qwechats, 'value', 'id');
       
        $builder->keyId();
        $builder->title('文本回复')->keyText('keywords', '回复关键词') ->keyText('title', '名称')
        ->keyTextArea('content', '回复内容');
        // ->keyCheckBox('appid', '服务微信','',$wechats)
        // ->keyCheckBox('qappid', '企业微信','',$qwechats);
      

        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();
    }

     public function rfunction(){
        $id = I('get.id');
        $dataSet = array(
            'type' => 0
        );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();
        $data = $this->getRobModel()->find($id);
       
        $builder->keyId();
        $builder->title('应用回复')->keyText('keywords', '回复关键词')->keyText('title', "应用名称")
        ->keyText('content', "应用标示")
        ->keyTextArea('disc', '应用描述') ->keyRadio('wechat_type', '应用类型','',array(0=>'通用应用',1=>'企业号应用',2=>'服务号应用'));
       

        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();
    }

    public function rtextimg(){
        $id = I('get.id');
        $dataSet = array(
            'type' => 2
        );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();
        $data = $this->getRobModel()->find($id);
        $builder->keyId();
        $builder->title('图文回复');
        $builder->keyText('keywords', '回复关键词');
        $builder->keyText('title', '标题');
        $builder->keySingleImage('image', '封面图片');
        $builder->keyText('linkurl', '链接', '可不填');
        $builder->keyEditor('content', '回复内容');

        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();
    }

     public function rtextimgs(){
        $id = I('get.id');
        $dataSet = array('type' => 2 );
        $this->commonSave($dataSet);

        $builder = new AdminConfigBuilder();

        $data = $this->getRobModel()->find($id);
        if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Wechat')->where($map)->field('id,name as value')->select();
        $wechats =array_column($wechats, 'value', 'id');
        $qwechats = M('Qwechat')->where($map)->field('id,name as value')->select();
        $qwechats =array_column($qwechats, 'value', 'id');

        $builder->keyId();
        $builder->title('图文回复');
        $builder->keyText('keywords', '回复关键词')->keyText('title', '名称');
        $builder->keyText('content', '文章组合','填写文章ID，使用英文逗号分隔，例如：1,2,3')
        ->keyCheckBox('appid', '服务微信','',$wechats)
        ->keyCheckBox('qappid', '企业微信','',$qwechats);
       


        $builder->data($data);
        $builder->keyDefault('SUCCESS_WAIT_TIME',2);
        $builder->keyDefault('ERROR_WAIT_TIME',3);

        $builder->buttonSubmit();
        $builder->display();

       

       
    }

     protected function commonSave($dataSet = array()){
        if(IS_POST){
            $id = I('post.id');
            $data = I('post.');
            $data['aid']=session('user_auth.aid');
            $data = array_merge($data, $dataSet);
            if($id){
                $data['update_time'] = time();
            }
            $mod = $this->getRobModel();
            $mod->create($data);
            if($id){
                $rs = $mod->save();
            } else {
                $rs = $mod->add();
            }
            if($rs){
                $this->msuccess();
            } else {
                $this->merror();
            }
        }
    }

      public function dataset($page=1,$r=5){
        $inputid = I('get.inputid');
        $totalCount =   D('News/news')->where(array('type'=>2))->count();
        $pager = new \Think\Page($totalCount, $r);
        $pager->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $paginationHtml = $pager->show();

        $list =  D('News/news')->where(array('type'=>2))->page($page,$r)->order('id DESC')->select();

        $this->assign('inputid', $inputid);
        $this->assign('list', $list);
        $this->assign('paginationHtml', $paginationHtml);
        $this->display(T('Application://Wechat@Wechat/dataset'));
    }

    public function attention(){
        $id = I('get.id');
        if(!$id){
            $this->error('信息不存在');
        }
        $this->getRobModel()->where(array('is_attention' => 1))->save(array('is_attention' => 2));
        $rs = $this->getRobModel()->where(array('id' => $id))->save(array('is_attention' => 1));

        if($rs){
            $this->success('设置成功');
        }
        $this->error('设置失败');
    }

     public function edit(){
        $id = I('get.id');

        if(!$id){
            $this->error('信息不存在');
        }
        $info = $this->getRobModel()->find($id);
        if (!is_administrator() and  $info['aid']==0 ) $this->error('禁止编辑系统内置命令!');
        switch($info['type']){
            case 0:
                $this->redirect('rfunction', array('id' => $id));
                break;
            case 1:
                $this->redirect('rtext', array('id' => $id));
                break;
            case 2:
                $this->redirect('rtextimgs', array('id' => $id));
                break;
        }
    }

     public function del(){
        $id = I('post.ids');

        if($id){
            $rs = false;
            foreach($id as $item){
                if(intval($item)){
                    $rs = $this->getRobModel()->delete($item) || $rs;
                }
            }
            if($rs){
                $this->msuccess('删除成功', U('rob'));
            }
        }
        $this->msuccess('删除失败', U('areplay'));
    }


  


    protected function msuccess($msg = '保存成功', $url = ''){
        header('Content-type: application/json');
        $url = $url ? $url : __SELF__;
        exit(json_encode(array('info' => $msg, 'status' => 1, 'url' => $url)));
    }
    protected function merror($msg = '保存失败', $url = ''){
        header('Content-type: application/json');
        $url = $url ? $url : __SELF__;
        exit(json_encode(array('info' => $msg, 'status' => 0, 'url' => $url)));
    }
} 