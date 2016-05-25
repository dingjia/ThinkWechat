<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class MessageModel extends Model
{


    /**
     * sendMessage   发送消息，屏蔽自己
     * @param $to_uids 接收消息的用户们
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param string $url 消息指向的路径，U函数的第一个参数
     * @param array $url_args 消息链接的参数，U函数的第二个参数
     * @param int $from_uid 发送消息的用户
     * @param int $type 消息类型，0系统，1用户，2应用
     * @return bool
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    public function sendMessage($to_uids, $title = '您有新的消息', $content = '', $url = '', $url_args = array(), $from_uid = -1, $type = 0)
    {
        $from_uid == -1 && $from_uid = is_login();
        $to_uids = is_array($to_uids) ? $to_uids : array($to_uids);
        $k = array_search(is_login(), $to_uids);
        if ($k !== false) {
            unset($to_uids[$k]);
        }

        if (count($to_uids) > 0) {
            return $this->sendMessageWithoutCheckSelf($to_uids, $title, $content, $url, $url_args, $from_uid, $type);
        } else {
            return false;
        }
    }

    /**
     * sendMessageWithoutCheckSelf  发送消息，不屏蔽自己
     * @param $to_uids 接收消息的用户们
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param string $url 消息指向的路径，U函数的第一个参数
     * @param array $url_args 消息链接的参数，U函数的第二个参数
     * @param int $from_uid 发送消息的用户
     * @param int $type 消息类型，0系统，1企业微信，2微信 3.邮件 4.短信
     * @return bool
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    public function sendMessageWithoutCheckSelf($to_uids, $title = '您有新的消息', $content = '', $url = '', $url_args = array(), $from_uid = -1, $type = 0)
    {
        $from_uid == -1 && $from_uid = is_login();
        $message_content_id = $this->addMessageContent($from_uid, $title, $content, $url, $url_args, $type);
        $to_uids = is_array($to_uids) ? $to_uids : array($to_uids);
        foreach ($to_uids as $to_uid) {
            $message['to_uid'] = $to_uid;
            $message['content_id'] = $message_content_id;
            $message['from_uid'] = $from_uid;
            $message['create_time'] = time();
            $message['status'] = 1;
            $this->add($message);
            unset($message);
        }
        return true;
    }

    //通过其他方式发送

      public function sendMessageLog($to, $data,$type=0 )
    {
        
         $from_uid = -1; 

        switch ($type) {
            case '0':
                $message['to_userid'] = $to['userid'];
                $title = $data['title'];
                $content = $data['wechat'];
                break;
            case '1':
                $message['to_userid'] = $to['userid'];
                $title = $data['title'];
                $content = $data['wechat'];
                break;
            case '2':
                $message['to_openid'] = $to['openid'];
                $title = $data['title'];
                $content = $data['wechat'];
                break;
            case '3':
                $message['to_email'] = $to['email'];
                $title = $data['title'];
                $content = $data['email'];
                break;
            case '4':
                $message['to_mobile'] = $to['mobile'];
                $title = $data['title'];
                $content = $data['sns'];
                break;
            
            default:
               $type = 0;
                break;
        }
       
       
        $message_content_id = $this->addMessageContent($from_uid, $title, $content, $url, $url_args, $type);
        
       
            $message['aid'] = $to['aid']?$to['aid']:session('user_auth.aid');
            $message['content_id'] = $message_content_id;
            $message['from_uid'] = $from_uid;
            $message['create_time'] = time();
            $message['status'] = 1;

            $this->add($message);
           
            unset($message);
       
        return true;
    }

    /**
     * addMessageContent  添加消息内容到表
     * @param $from_uid 发送消息的用户
     * @param $title 消息的标题
     * @param $content 消息内容
     * @param $url 消息指向的路径，U函数的第一个参数
     * @param $url_args 消息链接的参数，U函数的第二个参数
     * @param $type 消息类型，0系统，1用户，2应用
     * @return mixed
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    private function addMessageContent($from_uid=-1, $title='您有新的消息', $content, $url='', $url_args='', $type=0)
    {
        $data_content['from_id'] = $from_uid;
        $data_content['title'] = $title;
        $data_content['content'] = $content;
        $data_content['url'] = $url;
        $data_content['args'] = empty($url_args) ? '':json_encode($url_args);
        $data_content['type'] = $type;
        $data_content['create_time'] = time();
        $data_content['status'] = 1;
        $message_id = D('message_content')->add($data_content);
        return $message_id;
    }


    public function getContent($id)
    {
        $content = S('message_content_' . $id);
        if (empty($content)) {
            $content = D('message_content')->find($id);
            if($content){
                $content['args'] = json_decode($content['args'],true);
                $content['args_json'] = json_encode($content['args']) ;
                if($content['url']){
                    $content['web_url'] = is_bool(strpos($content['url'],'http://')) ? U($content['url'],$content['args']):$content['url'];
                }else{
                    $content['web_url'] = U('ucenter/message/message',array('tab'=>'all'));
                }
            }
            S('message_content_' . $id, $content, 60 * 60);
        }

        return $content;
    }


    /**取回全部未读,也没有提示过的信息
     * @param $uid
     * @return mixed
     */
    public function getHaventReadMeassageAndToasted($uid)
    {
        $messages = D('message')->where('to_uid=' . $uid . ' and  is_read=0  and last_toast!=0')->order('id desc')->limit(99999)->select();
        foreach ($messages as &$v) {
            $v['ctime'] = friendlyDate($v['create_time']);
            $v['content'] = $this->getContent($v['content_id']);
        }
        unset($v);
        return $messages;
    }

    public function readMessage($message_id)
    {
        return $this->where(array('id' => $message_id))->setField('is_read', 1);
    }


    public function setAllReaded($uid)
    {
        D('message')->where('to_uid=' . $uid . ' and  is_read=0')->setField('is_read', 1);
    }



    /**获取全部没有提示过的消息
     * @param $uid 用户ID
     * @return mixed
     */
    public function getHaventToastMessage($uid)
    {
        $messages = D('message')->where('to_uid=' . $uid . ' and  is_read=0  and last_toast=0')->order('id desc')->limit(99999)->select();
        foreach ($messages as &$v) {
            $v['ctime'] = friendlyDate($v['create_time']);
            $v['content'] = $this->getContent($v['content_id']);
        }
        unset($v);
        return $messages;
    }

    /**设置全部未提醒过的消息为已提醒
     * @param $uid
     */
    public function setAllToasted($uid)
    {
        $now = time();
        D('message')->where('to_uid=' . $uid . ' and  is_read=0 and last_toast=0')->setField('last_toast', $now);
    }



    /**取回全部未读信息
     * @param $uid
     * @return mixed
     */
    public function getHaventReadMeassage($uid, $is_toast = 0)
    {
        $messages = D('message')->where('to_uid=' . $uid . ' and  is_read=0 ')->order('id desc')->limit(99999)->select();
        foreach ($messages as &$v) {
            $v['ctime'] = friendlyDate($v['create_time']);
            $v['content'] = $this->getContent($v['content_id']);
        }
        unset($v);
        return $messages;
    }

} 