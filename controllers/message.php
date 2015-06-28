<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Message extends MY_Controller {

	/**
	 * 私信模块构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        $this->load->model('Message_model');
        $this->load->model('talk_model');
        $this->load->model('cache_model');

    }

    function usermsg() {
        $this->request_array['rn'] = isset($this->request_array['rn']) ? $this->request_array['rn'] : 10;
        $request = $this->request_array;    
        $response = $this->response_array;
        if (!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
            goto end;
        } 

        $uid = $request['uid'];
        $ret = $this->talk_model->get_talk_list($uid, TALK_MSG_QUEUE_SIZE);
        if (false === $ret) {
            $response['errno'] = REDIS_ERR_OP;
            log_message('error', __METHOD__ . ' get talk list error, uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if(empty($ret)) {
            log_message('error', __METHOD__ . ' get talk list empty, uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        $result = array();
        $fields = array('sname', 'avatar');
        $current_time = time();
        foreach ($ret as $talk_info) {
            $sname = '';
            $avatar = '';
            $ret = $this->get_user_detail_by_uid($talk_info['other_uid'], $fields);
            if(!$ret) {
                $response['errno'] = REDIS_ERR_OP;
                log_message('error', __METHOD__ . ' get user_info error, uid['.$uid.'] errno[' . $response['errno'] .']');
                goto end;
            }
            if ($ret) {
                $sname = $ret['sname']; 
                $avatar = $ret['avatar'];
            }
            $result[] = array(
                'sname' => $sname,    
                'avatar' => $avatar,
                'from_uid' => $talk_info['from_uid'],
                'to_uid' => $talk_info['to_uid'],
                'ctime' => $this->format_time(intval($talk_info['timestamp'], 
                                              $current_time)),    
                'content' => $talk_info['brief'],
                'has_new_msg' => $talk_info['has_new_msg'],
            );
        }
        $response['data']['content'] = $result;
        $params = array();
        $params['uid'] = $uid;
        $params['mType'] = 8;
        $params['from_uid'] = intval($other_uid);
        $this->offclient->ClearRedEvent($params);
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    function newmsg() {
        $request = $this->request_array;  
        $response = $this->response_array;
        if (!isset($request['uid'])
            || !isset($request['to_uid'])
            || !isset($request['content'])) {
            log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
            $this->renderJson(STATUS_ERR_REQUEST); return;
        }

        $from_uid = $request['uid'];
        $to_uid = $request['to_uid'];
        $content = $request['content'];
        $ctime = time();

        // 1. 写DB
        $data = array(
            'from_uid' => $from_uid,
            'to_uid' => $to_uid,
            'content' => $content,
            'ctime' => $ctime,    
        );
        $mid = $this->Message_model->add($data);
        if(!$mid) {
            $response['errno'] = MYSQL_ERR_INSERT;
            log_message('error', __METHOD__ . ' new message db error, fromuid['.$from_uid.'] touid['.$to_uid.'] errno[' . $response['errno'] .']');
            $this->renderJson($response['errno']); return;
        }

        // 2. 更新redis
        $ret = $this->talk_model->add_msg($from_uid, $to_uid, $mid, $content);
        if (!$ret) {
            $response['errno'] = REDIS_ERR_OP;
            log_message('error', __METHOD__ . ' new message redis error, mid['.$mid.'] errno[' . $response['errno'] .']');
            $this->renderJson($response['errno']);
            return;
        }
        $ret = $this->talk_model->update_talk_list($from_uid, $to_uid);
        if (!$ret) {
            $response['errno'] = REDIS_ERR_OP;
            log_message('error', __METHOD__ . ' update talk list error, fromuid['.$from_uid.'] touid['.$to_uid.'] errno[' . $response['errno'] .']');
            $this->renderJson($response['errno']);
            return;
        }

        // 3. 更新session中的read mid
        $this->talk_model->update_read_mid($from_uid, $to_uid, $mid);

        // 4. 推送到消息中心
        $params = array();
        $params['from_uid'] = $from_uid;
        $params['action_type'] = ACTION_TYPE_MSG;
        $params['to_uid'] = array(intval($to_uid));
        $params['content_id'] = $mid;
        $this->offclient->SendSysMsgEvent($params);

        $this->renderJson(STATUS_OK, array('mid' => $mid));
    }

    private function talk_new($uid, $other_uid, $mid, $rn) {
        //暂时不支持刷新
        $this->renderJson(STATUS_OK, array('content' => array())); 
    }

    private function _fill_user_info($msgs, $uid, $other_uid) {
        $user_info = $this->get_user_detail_by_uid($uid, array('sname', 'avatar'));
        if (!$user_info) {
            $user_info = array('sname' => '', 'avatar' => ''); 
        }
        $other_user_info = $this->get_user_detail_by_uid($other_uid, array('sname', 'avatar'));
        if (!$other_user_info) {
            $other_user_info = array('sname' => '', 'avatar' => ''); 
        }
        $other_user_info = $this->get_user_detail_by_uid($other_uid, array('sname', 'avatar'));
        foreach ($msgs as $idx => $msg) {
            if ($uid == $msg['from_uid']) {
                $msgs[$idx]['sname'] = $user_info['sname'];
                $msgs[$idx]['avatar'] = $user_info['avatar'];
            } else {
                $msgs[$idx]['sname'] = $other_user_info['sname'];
                $msgs[$idx]['avatar'] = $other_user_info['avatar'];
            }
            $msgs[$idx]['time'] = date('Y-m-d H:i', $msg['ctime']);
        }
        return $msgs;
    }

    private function talk_old ($uid, $other_uid, $mid, $rn) {
        $mid_list = $this->talk_model->get_msg_list($uid, $other_uid);
        if (!$mid_list) {
            log_message('error', __METHOD__ . ' get msg list error, uid['.$uid.'] otheruid['.$other_uid.'] errno[' . REDIS_ERR_OP .']');
            $this->renderJson(REDIS_ERR_OP); 
            return;
        }
        $start = -1;
        foreach ($mid_list as $idx => $id) {
            if ($id == $mid) {
                $start = $idx + 1; 
                break;
            } 
        }
        $result = array();
        if ($start >= 0) {
            $mids = array_slice($mid_list, $start, $rn); 
            $result = array_merge($result, $this->Message_model->get_detail_by_mids($mids)); 
            if (count($mids) < $rn) {
                $ret = $this->Message_model->get_previous_msgs($uid, $other_uid, $mid, $rn - count($mids));
                if ($ret) {
                    $result = array_merge($result, $ret);    
                }
            }
        } else {
            $ret = $this->Message_model->get_previous_msgs($uid, $other_uid, $mid, $rn);
            if ($ret) {
                $result = $ret; 
            } 
        }        
        $result = $this->_fill_user_info($result, $uid, $other_uid);
        $read_mid = $this->talk_model->get_read_mid($uid, $other_uid);
        $this->renderJson(STATUS_OK, array('content' => $result, 'last_read_mid' => $read_mid));
    }

    private function talk_load ($uid, $other_uid, $rn) {
        $mid_list = $this->talk_model->get_msg_list($uid, $other_uid);
        if (false === $mid_list) {
            log_message('error', __METHOD__ . ' get msg list error, uid['.$uid.'] otheruid['.$other_uid.'] errno[' . REDIS_ERR_OP .']');
            $this->renderJson(REDIS_ERR_OP); 
            return;
        }
        $mids = array_slice($mid_list, 0, $rn);
        $details = $this->Message_model->get_detail_by_mids($mids);

        $details = $this->_fill_user_info($details, $uid, $other_uid);


        $read_mid = $this->talk_model->get_read_mid($uid, $other_uid);
        //由于暂时不支持上拉刷新，所以就在这插入已读消息更新
        if (count($details) > 0) {
            $this->talk_model->update_read_mid($uid, $other_uid, $details[count($details) - 1]['mid']); 
        }

        $this->renderJson(STATUS_OK, array('content' => $details, 'last_read_mid' => $read_mid));
    }

    function talk() {
        $request = $this->request_array; 
        if (!isset($request['uid']) 
            || !isset($request['msg_uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
            $this->renderJson($response['errno']);
            return;
        }
        $uid = $request['uid'];
        $other_uid = $request['msg_uid'];
        $rn = isset($request['rn']) ? intval($request['rn']) : 10;
        if (isset($request['type'])) {
            if ('new' == $request['type']) {
                if (!isset($request['first_mid'])) {
                    $response['errno'] = STATUS_ERR_REQUEST;
                    log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
                    $this->renderJson($response['errno']);
                    return;
                }
                $this->talk_new($uid, $other_uid, $request['first_mid'], $rn); 
            } else if ('next' == $request['type'])  {
                if (!isset($request['last_mid'])) {
                    $response['errno'] = STATUS_ERR_REQUEST;
                    log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
                    $this->renderJson($response['errno']);
                    return;
                }
                $this->talk_old($uid, $other_uid, $request['last_mid'], $rn); 
            } else {
                $response['errno'] = STATUS_ERR_REQUEST;
                log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
                $this->renderJson($response['errno']);
                return;
            } 
        } else {
            $this->talk_load($uid, $other_uid, $rn); 
        }
        $params = array();
        $params['uid'] = $uid;
        $params['mType'] = 8;
        $params['from_uid'] = intval($other_uid);
        $this->offclient->ClearRedEvent($params);
    }

    /*
     * 根据私信ID删除私信
     */
    function delmsg() {

        $request = $this->request_array;
        $response = $this->response_array;

        if (!isset($request['uid'])
            || !isset($request['msg_uid'])) {
                $response['errno'] = STATUS_ERR_REQUEST;
                log_message('error', __METHOD__ . ' request error, errno[' . $response['errno'] .']');
                goto end;
            }
        $uid = $request['uid'];
        $to_uid = $request['msg_uid'];

        $result = $this->talk_model->remove_talk_list($uid, $to_uid);
        if(!$result) {
            $response['errno'] = REDIS_ERR_OP;
            log_message('error', __METHOD__ . ' remove talk list error, uid['.$uid.'] touid['.$to_uid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        else {
            $params = array();
            $params['uid'] = $uid;
            $params['mType'] = 8;
            $params['from_uid'] = intval($to_uid);
            $this->offclient->ClearRedEvent($params);
        }
        end:
        $this->renderJson($response['errno']);
    }

}


/* End of file message.php */
/* Location: ./application/controllers/message.php */
