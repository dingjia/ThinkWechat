<?php

namespace Addons\CheckIn;

use Common\Controller\Addon;

/**
 * 签到插件
 * @author 嘉兴想天信息科技有限公司
 */
class CheckInAddon extends Addon
{

    public $info = array(
        'name' => 'CheckIn',
        'title' => '签到',
        'description' => '签到插件',
        'status' => 1,
        'author' => 'xjw129xjt(肖骏涛)',
        'version' => '0.1'
    );


    public function install()
    {
        $prefix = C("DB_PREFIX");
        D()->execute("DROP TABLE IF EXISTS `{$prefix}checkin`");
        D()->execute(<<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}checkin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
SQL
        );

        D()->execute(<<<SQL
        ALTER TABLE  `{$prefix}member` ADD  `con_check` INT NOT NULL DEFAULT  '0',
ADD  `total_check` INT NOT NULL DEFAULT  '0';
SQL
        );


        return true;
    }

    public function uninstall()
    {

        $prefix = C("DB_PREFIX");
        D()->execute("DROP TABLE IF EXISTS `{$prefix}checkin`");

        D()->execute(<<<SQL
ALTER TABLE `{$prefix}member`
  DROP `con_check`,
  DROP `total_check`;
SQL
        );
        return true;
    }


    public function checkIn($param)
    {
        $model = $this->checkInModel();
        $uid = is_login();
        $check = $model->getCheck($uid);
        $this->assign('check', $check);
        $this->assignDate();
        $html = $this->rank('today');
        $this->assign('html', $html);
        $this->display('View/checkin');

    }

    private function checkInModel()
    {
        return D('Addons://CheckIn/CheckIn');
    }

    public function rank($type)
    {
        $time = get_some_day(0);
        $rank = S('check_rank_' . $type . '_' . $time);
        if (empty($rank)) {
            $model = $this->checkInModel();
            $rank = $model->getRank($type);
            S('check_rank_' . $type . '_' . $time, $rank, 300);
        }
        foreach($rank as &$v){
            $v['user'] = query_user(array('avatar32','avatar64','space_url', 'nickname', 'uid',), $v['uid']);
        }
        unset($v);
        $this->assign('rank', $rank);
        $this->assign('type', $type);
        $this->assign('type_ch', $type == 'con' ? '连签' : '累签');
        $html = $this->fetch('View/rank');
        return $html;
    }

    private function assignDate()
    {
        $week = date('w');
        switch ($week) {
            case '0':
                $week = '周日';
                break;
            case '1':
                $week = '周一';
                break;
            case '2':
                $week = '周二';
                break;
            case '3':
                $week = '周三';
                break;
            case '4':
                $week = '周四';
                break;
            case '5':
                $week = '周五';
                break;
            case '6':
                $week = '周六';
                break;
        }
        $this->assign('day', date('Y.m.d'));
        $this->assign('week', $week);

    }


    public function doCheckIn()
    {

        $time = get_some_day(0);
        $uid = is_login();

        $model = $this->checkInModel();
        $memberModel = D('Member');
        $scoreModel= D('Ucenter/Score') ;
        $check = $model->getCheck($uid);
        if (!$check) {
            $model->addCheck($uid);
            $memberModel->where(array('uid' => $uid))->setInc('total_check');
            //签到积分奖励 从addons表获得设置的类型和积分数

                 $arrconf=get_addon_config('CheckIn');
                 array_shift($arrconf);
                  $new=array_filter($arrconf);

            foreach($new as $k=>$v){
                $k1=substr($k,5,strlen($k)-5);
                $scoreModel->setUserScore($uid, $v,$k1,'inc', 'weibo',$uid,'签到['.$k.']类型积分+['.$v.']');
                $scoreModel->addScoreLog($uid, $k1, 'inc',$v, 'weibo',$uid,'签到['.$k.']类型积分+['.$v.']');
            }

            $model->checkYesterday($uid);
            clean_query_user_cache($uid, array('con_check', 'total_check','score1'));
            S('check_rank_today_' . $time, null);
            S('check_rank_con_' . $time, null);
            S('check_rank_total_' . $time, null);

            return true;
        } else {
            return false;
        }
    }


    public function handleAction($param)
    {
        $typedata=M('ucenter_score_type');
        $arrconf=get_addon_config('CheckIn');
        array_shift($arrconf);
        $arrkey=array_keys($arrconf);
        foreach($arrkey as $k){
            $k=substr($k,5,strlen($k)-5);
            $typename[]=$typedata->where('id='.$k)->getfield('title');
        }
        $p=0;
        foreach($arrconf as $f){
            $newarr[$typename[$p]]=$f;
            $p++;
        }
        unset($f);
        $new=array_filter($newarr);//类型名+积分数  除去空元素
        $str='';
        foreach($new as $t=>$v){
            $str.=$t.'+'.$v.'!';
        }
        $config = $this->getConfig();
        if (!empty($config['action'])) {
            $action_info = M('Action')->getByName($config['action']);
            if ($action_info['id'] == $param['action_id']) {
                $res = $this->doCheckIn();
                if($res){
                    $param['log_score'] .= '签到成功!'.$str;
                    return $res;
                }
            }
        }
        return false;

    }


}