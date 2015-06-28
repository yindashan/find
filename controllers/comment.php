<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Comment extends MY_Controller {

	/**
	 * 评论模块构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        $this->load->model('Comment_model');
        $this->load->model('Tweet_model');
        $this->load->model('Cache_model');

        if (in_array($this->uri->segment(2), array('tweetcmt'))) {
            $this->_set_login_check(false);
            //$this->_set_sign_check(false);
        }
    }

    /**
     * 获取tweet所有评论
     */
	function tweetcmt()
    {
        //后端统一控制展现数量
        $this->request_array['rn'] = COMMENT_LIST_COUNT;

        $request = $this->request_array;
        $response = $this->response_array;

        $content = array();

        if(!isset($request['type']) || !isset($request['tid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $type = $request['type'];
        $tid = $request['tid'];
        $rn = $request['rn'];
        if($type == 'new') {
            //下拉刷新取最新评论
            $result = $this->Comment_model->get_list_by_tid($tid, $rn);

        }elseif($type == 'next') {
            //上拉加载更多评论
            $cid = $request['last_cid'] ? $request['last_cid'] : 0;
            $result = $this->Comment_model->get_list_by_cid_tid($cid, $tid, $rn);
        }
        if(false === $result) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' get comment list error, tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if(empty($result)) {
            log_message('error', __METHOD__ .':'.__LINE__.' get comment list null, tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if($result) {
            $content = $result;
            foreach($content as $idx => $comment) {
                //格式化时间
                $content[$idx]['ctime'] = $this->format_time($comment['ctime']);

                //获取当前评论用户数据
                $uid = $comment['uid'];

                $ret = $this->get_user_detail_by_uid($uid, array('sname', 'avatar'));
                if ($ret) {
                    $content[$idx]['sname'] = $ret['sname'];
                    $content[$idx]['avatar'] = $ret['avatar'];
                } else {
                    $content[$idx]['sname'] = '';
                    $content[$idx]['avatar'] = '';
                }

                //获取原评论信息和用户数据
                $reply_cid = $comment['reply_cid'];
                $reply_uid = $comment['reply_uid'];
                if($reply_cid && $reply_uid) {
                    $ret_reply = $this->get_user_detail_by_uid($reply_uid, array('sname'));
                    if ($ret_reply) {
                        $content[$idx]['reply_sname'] = $ret_reply['sname'];
                    }else {
                        $content[$idx]['reply_sname'] = '';
                    }
                }
            }
        }
        $response['data'] = array(
            'content' => $content,
            'type' => $type,    
        );
        end:
        $this->renderJson($response['errno'], $response['data']);
	}

    /**
     * 发表评论和回复评论
     */
    function newcmt() {
        $this->load->library('offclient');
        $request = $this->request_array;
        $response = $this->response_array;

        if(!isset($request['tid']) || !isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        } 

        $tid = $request['tid'];
        $uid = $request['uid'];
        $content = $request['content'];

        $reply_uid = isset($request['reply_uid']) ? $request['reply_uid'] : 0;
        $reply_cid = isset($request['reply_cid']) ? $request['reply_cid'] : 0;

        if (empty($uid) || empty($tid) || empty($content)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        if (empty($reply_uid)) {
            $reply_uid = 0; 
        }
        if (empty($reply_cid)) {
            $reply_cid = 0; 
        }

        $data = array(
            'uid' => $uid,
            'tid' => $tid,
            'content' => $content,
            'ctime' => time(),    
            'reply_uid' => $reply_uid,
            'reply_cid' => $reply_cid,
        );
        $cid = $this->Comment_model->add($data);
        if (false === $cid) {
            $response['errno'] = MYSQL_ERR_INSERT;
            log_message('error', __METHOD__ .':'.__LINE__ .' new comment error, uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        //推送到消息中心
        $this->offclient->send_event($tid, offhub\EventType::COMMENT);
        $this->Cache_model->comment_add($tid);

        $ret_tweet = $this->Tweet_model->get_tweet_info($tid);
        if((false === $ret_tweet) || empty($ret_tweet)) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' get_tweet_info error, uid['.$uid.'] cid['.$cid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if ($ret_tweet) {
            if(!$reply_uid || ($ret_tweet['uid'] != $reply_uid)) {
                //$this->msclient->send_system_msg($uid, ms\ActionType::COMMENT, $ret['uid'], $cid);
                $params = array();
                $params['from_uid'] = $uid;
                $params['action_type'] = offhub\SysMsgType::COMMENT;
                $params['to_uid'] = array($ret_tweet['uid']);
                $params['content_id'] = $cid;
                $this->offclient->SendSysMsgEvent($params);

            }
        }
        if ($reply_uid) {
            $params = array();
            $params['from_uid'] = $uid;
            $params['action_type'] = offhub\SysMsgType::COMMENT;
            $params['to_uid'] = array($reply_uid);
            $params['content_id'] = $cid;
            $this->offclient->SendSysMsgEvent($params);
        }
        end:
        $this->renderJson($response['errno'], array('cid' => $cid));
    }

    /**
     * 评论删除
     */
    public function delcmt() {
        $request = $this->request_array;
        $response = $this->response_array;
        if (!isset($request['cid']) && !isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $cid = $request['cid'];
        $uid = $request['uid'];

        $comment = $this->Comment_model->get_detail_by_cid($cid);
        if(false === $comment) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' get detail error, cid['.$cid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if(empty($comment)) {
            log_message('error', __METHOD__ .':'.__LINE__.' get detail null, cid['.$cid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if (1 == intval($comment['is_del'])) {
            log_message('error', __METHOD__ .':'.__LINE__.' comment already del, cid['.$cid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        if($this->_uid !== $comment['uid']) {
            $response['errno'] = ERR_USER_ILLEGAL;
            log_message('error', __METHOD__.':'. __LINE__.' user illegal, uid['.$this->_uid.'] cid['.$cid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        $data = array(
            'is_del' => 1,
        );

        //更新库里is_del字段
        $res = $this->Comment_model->update_by_cid_uid($cid, $this->_uid, $data);
        if (false === $res) {
            $response['errno'] = MYSQL_ERR_UPDATE;
            log_message('error', __METHOD__ .':'. __LINE__.' comment delete error, uid['.$this->_uid.'] cid['.$cid.'] errno[' . $response['errno'] .']');
            goto end;
        } else if (0 < $res) {
            //设置redis帖子评论数
            $this->Cache_model->comment_cancel($comment['tid']);
        }
        end:
        $this->renderJson($response['errno']);
           
    }
          
}


/* End of file comment.php */
/* Location: ./application/controllers/comment.php */
