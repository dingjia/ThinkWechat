<?php
/**
 * 中文转拼音类
 * */
namespace Common\Model;

class UserModel
{
    private $member_fields = array('uid', 'nickname', 'sex', 'birthday', 'qq', 'login', 'reg_ip', 'reg_time', 'last_login_ip', 'last_login_time', 'status', 'last_login_role', 'show_role', 'signature', 'pos_province', 'pos_city', 'pos_district', 'pos_community', 'score1', 'score2', 'score3', 'score4', 'con_check', 'total_check');
    private $ucenter_member_fields = array('id', 'username', 'password', 'email', 'mobile', 'reg_time', 'reg_ip', 'last_login_time', 'last_login_ip', 'update_time', 'status', 'type');

    /**获取用户信息
     * @param null $fields
     * @param null $uid
     * @return array|mixed|null
     */
    function query_user($fields = null, $uid = 0)
    {
        //默认赋值
        if ($fields === null) {
            $fields = array('username','nickname', 'space_url', 'space_mob_url', 'avatar32', 'avatar64', 'avatar128', 'uid');
        }
        //如果fields不是数组，直接返回需要的值
        if (!is_array($fields)) {
            $result = query_user(array($fields), $uid);
            return $result[$fields];
        }

        //默认score指代score1
        if (in_array('score', $fields)) {
            $fields[] = 'score1';
        }
        //默认获取自己的资料
        $uid = (intval($uid) != 0 ? $uid : get_uid());

        //获取缓存过的字段
        list($cacheResult, $field, $fields) =$this->getCachedFields($fields, $uid);

        //获取各个表的字段值
        list($avatarFields, $homeResult, $ucenterResult) = $this->getSplittedFieldsValue($fields, $uid);

        //获取头像Avatar数据
        list($result, $e) = $this->getAvatars($uid, $avatarFields);


        //读取等级数据
        if (in_array('title', $fields)) {
            $titleModel = D('Ucenter/Title');
            $title = $titleModel->getTitle($uid);
            $result['title'] = $title;
        }
        //获取昵称 nickname
        $ucenterResult = $this->getNickname($fields, $ucenterResult);
        //获取昵称拼音 pinyin
        $result = $this->getPinyin($fields, $result);
        //获取空间链接 space_url
        list($spaceUrlResult, $result) = $this->getSpaceUrl($fields, $uid, $result);
        //获取手机版空间链接 space_mob_url
        list($spaceMobUrlResult, $result) = $this->getSpaceMobUrl($fields, $uid, $result);
        //获取个人是空间链接 space_link
        list($ucenterResult, $result) = $this->getSpaceLink($fields, $uid, $ucenterResult, $result);
        //获取用户头衔 rank_link
        list($val, $result) = $this->getRankLink($fields, $uid, $val, $result);
        //获取扩展资料 expand_info
        $result = $this->getExpandInfo($fields, $uid, $map, $map_field, $result);

        //粉丝数、关注数、微博数
        if (in_array('fans', $fields)) {
            $result['fans'] = M('Follow')->where('follow_who=' . $uid)->count();
        }
        if (in_array('following', $fields)) {
            $result['following'] = M('Follow')->where('who_follow=' . $uid)->count();
        }
        if (in_array('weibocount', $fields)) {
            $result['weibocount'] = M('Weibo')->where('uid=' . $uid . ' and status >0')->count();
        }

        //是否关注、是否被关注
        if (in_array('is_following', $fields)) {
            $follow = D('Follow')->where(array('who_follow' => get_uid(), 'follow_who' => $uid))->find();
            $result['is_following'] = $follow ? true : false;
        }
        if (in_array('is_followed', $fields)) {
            $follow = D('Follow')->where(array('who_follow' => $uid, 'follow_who' => get_uid()))->find();
            $result['is_followed'] = $follow ? true : false;
        }


        //TODO 在此加入扩展字段的处理钩子
        //↑↑↑ 新增字段应该写在在这行注释以上 ↑↑↑

        //合并结果，不包括缓存
        $result = array_merge($ucenterResult, $homeResult, $spaceUrlResult, $result, $spaceMobUrlResult);
        //写缓存
        $result = $this->writeCache($uid, $result);
        //合并结果，包括缓存
        $result = array_merge($result, $cacheResult);

        //对昵称的额外处理
        $result['real_nickname'] = $result['nickname'];
        if (get_uid() != $uid && is_login()) {//如果已经登陆，并且获取的用户不是自己
            $alias = $this->getAlias($uid);
            if ($alias != '') {//如果设置了备注名
                $result['nickname'] = $alias;
                $result['alias'] = $alias;
            }
        }

        if (in_array('score', $fields)) {
            $result['score'] = $result['score1'];
        }


        //返回结果
        return $result;
    }

    /**获取用户昵称
     * @param $uid
     * @return mixed|string
     */
    private function getAlias($uid)
    {
        //获取缓存的alias
        $tag = 'alias_' . get_uid() . '_' . $uid;
        $alias = S($tag);
        if ($alias === false) {
            //没有缓存
            $alias = '';
            $follow = D('Common/Follow')->getFollow(get_uid(), $uid);//获取关注情况
            if ($follow && $follow['alias'] != '') {//已关注
                $alias = $follow['alias'];
            }
            S($tag, $alias);
        }
        return $alias;
    }

    function read_query_user_cache($uid, $field)
    {
        return S("query_user_{$uid}_{$field}");
    }

    function write_query_user_cache($uid, $field, $value)
    {
        return S("query_user_{$uid}_{$field}", $value);
    }

    /**清理用户数据缓存，即时更新query_user返回结果。
     * @param $uid
     * @param $field
     * @auth 陈一枭
     */
    function clean_query_user_cache($uid, $field)
    {
        if (is_array($field)) {
            foreach ($field as $field_item) {
                S("query_user_{$uid}_{$field_item}", null);
            }
        }else{
            S("query_user_{$uid}_{$field}", null);
        }

    }

    /**
     * @param $fields
     * @param $uid
     * @return array
     */
    public function getCachedFields($fields, $uid)
    {
//查询缓存，过滤掉已缓存的字段
        $cachedFields = array();
        $cacheResult = array();
        foreach ($fields as $field) {
            $cache = $this->read_query_user_cache($uid, $field);
            if ($cache !== false) {
                $cacheResult[$field] = $cache;
                $cachedFields[] = $field;
            }
        }
        //去除已经缓存的字段
        $fields = array_diff($fields, $cachedFields);
        return array($cacheResult, $field, $fields);
    }

    /**
     * @param $fields
     * @param $homeFields
     * @param $ucenterFields
     * @return array
     */
    public function getSplittedFields($fields, $homeFields, $ucenterFields)
    {
        $avatarFields = array('avatar32', 'avatar64', 'avatar128', 'avatar256', 'avatar512');
        $avatarFields = array_intersect($avatarFields, $fields);
        $homeFields = array_intersect($homeFields, $fields);
        $ucenterFields = array_intersect($ucenterFields, $fields);
        return array($avatarFields, $homeFields, $ucenterFields);
    }

    /**
     * @param $fields
     * @param $uid
     * @return array
     */
    public function getSplittedFieldsValue($fields, $uid)
    {
//获取两张用户表格中的所有字段
        $homeFields = M('Member')->getDBFields();
        $ucenterFields = M('UcenterMember')->getDBFields();

        //分析每个表格分别要读取哪些字段
        list($avatarFields, $homeFields, $ucenterFields) = $this->getSplittedFields($fields, $homeFields, $ucenterFields);


        //查询需要的字段
        $homeResult = array();
        $ucenterResult = array();
        if ($homeFields) {
            $homeResult = M('Member')->where(array('uid' => $uid))->field($homeFields)->find();
        }
        if ($ucenterFields) {
            $model = M('UcenterMember');
            $ucenterResult = $model->where(array('id' => $uid))->field($ucenterFields)->find();
            return array($avatarFields, $homeResult, $ucenterResult);
        }
        return array($avatarFields, $homeResult, $ucenterResult);
    }

    /**
     * @param $uid
     * @param $avatarFields
     * @return array
     */
    public function getAvatars($uid, $avatarFields)
    {
//读取头像数据
        $result = array();
        $avatarObject = new \Ucenter\Widget\UploadAvatarWidget();
        foreach ($avatarFields as $e) {
            $avatarSize = intval(substr($e, 6));
            $avatarUrl = $avatarObject->getAvatar($uid, $avatarSize);
            $check = file_exists('./api/uc_login.lock');
            if ($check) {
                include_once './api/uc_client/client.php';
                $avatarUrl = UC_API . '/avatar.php?uid=' . $uid . '&size=big';
            }


            $result[$e] = $avatarUrl;
        }
        return array($result, $e);
    }

    /**
     * @param $fields
     * @param $result
     * @return mixed
     */
    public function getPinyin($fields, $result)
    {
//读取用户名拼音
        if (in_array('pinyin', $fields)) {

            $result['pinyin'] = D('Pinyin')->pinYin($result['nickname']);
            return $result;
        }
        return $result;
    }

    /**
     * @param $fields
     * @param $ucenterResult
     * @return mixed
     */
    public function getNickname($fields, $ucenterResult)
    {
        if (in_array('nickname', $fields)) {
            $ucenterResult['nickname'] = text($ucenterResult['nickname']);
            return $ucenterResult;
        }
        return $ucenterResult;
    }

    /**
     * @param $fields
     * @param $uid
     * @param $result
     * @return array
     */
    public function getSpaceUrl($fields, $uid, $result)
    {
//获取个人中心地址
        $spaceUrlResult = array();
        if (in_array('space_url', $fields)) {
            $result['space_url'] = U('Ucenter/Index/index', array('uid' => $uid));
            return array($spaceUrlResult, $result);
        }
        return array($spaceUrlResult, $result);
    }

    public function getSpaceMobUrl($fields, $uid, $result)
    {
//获取个人中心地址
        $spaceMobUrlResult = array();
        if (in_array('space_mob_url', $fields)) {
            $result['space_mob_url'] = U('Mob/User/index', array('uid' => $uid));
            return array($spaceMobUrlResult, $result);
        }
        return array($spaceMobUrlResult, $result);
    }

    /**
     * @param $fields
     * @param $uid
     * @param $ucenterResult
     * @param $result
     * @return array
     */
    public function getSpaceLink($fields, $uid, $ucenterResult, $result)
    {
//获取昵称链接
        if (in_array('space_link', $fields)) {
            if (!$ucenterResult['nickname']) {
                $res = query_user(array('nickname'), $uid);
                $ucenterResult['nickname'] = $res['nickname'];
            }
            $result['space_link'] = '<a ucard="' . $uid . '" target="_blank" href="' . U('Ucenter/Index/index', array('uid' => $uid)) . '">' . $ucenterResult['nickname'] . '</a>';
            return array($ucenterResult, $result);
        }
        return array($ucenterResult, $result);
    }

    /**
     * @param $fields
     * @param $uid
     * @param $val
     * @param $result
     * @return array
     */
    public function getRankLink($fields, $uid, $val, $result)
    {
//获取用户头衔链接
        if (in_array('rank_link', $fields)) {
            $rank_List = D('rank_user')->where(array('uid' => $uid, 'status' => 1))->select();
            $num = 0;
            foreach ($rank_List as &$val) {
                $rank = M('rank')->where('id=' . $val['rank_id'])->find();
                $val['title'] = $rank['title'];
                $val['logo_url'] = get_pic_src(M('picture')->where('id=' . $rank['logo'])->field('path')->getField('path'));
                $val['label_content'] = $rank['label_content'];
                $val['label_bg'] = $rank['label_bg'];
                $val['label_color'] = $rank['label_color'];
                if ($val['is_show']) {
                    $num = 1;
                }
            }
            if ($rank_List) {
                $rank_List[0]['num'] = $num;
                $result['rank_link'] = $rank_List;
                return array($val, $result);
            } else {
                $result['rank_link'] = array();
                return array($val, $result);
            }
        }
        return array($val, $result);
    }

    /**
     * @param $fields
     * @param $uid
     * @param $map
     * @param $map_field
     * @param $result
     * @return mixed
     */
    public function getExpandInfo($fields, $uid, $map, $map_field, $result)
    {
        if (in_array('expand_info', $fields)) {
            $map['status'] = 1;
            $field_group = D('field_group')->where($map)->select();
            $field_group_ids = array_column($field_group, 'id');
            $map['profile_group_id'] = array('in', $field_group_ids);
            $fields_list = D('field_setting')->where($map)->getField('id,field_name,form_type,visiable');
            $fields_list = array_combine(array_column($fields_list, 'field_name'), $fields_list);
            $map_field['uid'] = $uid;
            foreach ($fields_list as $key => $val) {
                $map_field['field_id'] = $val['id'];
                $field_data = D('field')->where($map_field)->getField('field_data');
                if ($field_data == null || $field_data == '') {
                    unset($fields_list[$key]);
                } else {
                    if ($val['form_type'] == "checkbox") {
                        $field_data = explode('|', $field_data);
                    }
                    $fields_list[$key]['data'] = $field_data;
                }
            }
            $result['expand_info'] = $fields_list;
            return $result;
        }
        return $result;
    }

    /**
     * @param $uid
     * @param $result
     * @return mixed
     */
    public function writeCache($uid, $result)
    {
//写入缓存
        foreach ($result as $field => $value) {
            if (!in_array($field, array('rank_link', 'space_link', 'expand_info'))) {
                $value = str_replace('"', '', text($value));
            }

            $result[$field] = $value;
            write_query_user_cache($uid, $field, str_replace('"', '', $value));
        }
        return $result;
    }

}