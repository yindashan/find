<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Zan extends MY_Controller {
    function __construct() {
        parent::__construct();

        $this->load->model('Zan_model');
        $this->load->model('Cache_model');
        $this->load->model('Tweet_model');
        $this->load->library('offclient');
    }

    function add() {
        $request = $this->request_array;
        $response = $this->response_array;

        if(!isset($request['uid']) || !isset($request['tid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $uid = $request['uid'];
        $tid = $request['tid'];

        if (empty($uid) || empty($tid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }

        //判断是否已经赞过
        $is_zan = false;
        $zan_list = $this->Cache_model->get_zan_list($tid);
        if(false === $zan_list) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        
        }
        if(!empty($zan_list)) {
            if(in_array($uid, $zan_list)) {
                $is_zan = true;
            log_message('error', __METHOD__ .':'.__LINE__.' uid['.$uid.'] already zan, tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
            }
        
        }

        $ret_user_info = $this->get_user_detail_by_uid($uid, array('sname'));
        if ($ret_user_info) {
            $sname = $ret_user_info['sname']; 
        } else {
            $sname = '';
        }

        $ret_tweet_info = $this->Tweet_model->get_tweet_info($tid);
        if($ret_tweet_info) {
            $owneruid = $ret_tweet_info['uid'];
        }else {
            $owneruid = 0;
        }

        $data = array(
            'uid' => $uid,
            'tid' => $tid,
            'username' => $sname,
            'owneruid' => $owneruid,
            'ctime' => time(),
        );
        $ret = $this->Zan_model->add($data);
        if (false === $ret) {
            $response['errno'] = MYSQL_ERR_INSERT;
            log_message('error', __METHOD__.':'.__LINE__. ' zan add error, uid['.$uid.'] tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        if ($ret > 0) {
            //判断redis中是否已经赞过
            if(!$is_zan) {
                $this->Cache_model->zan_add($tid, $uid);
            }

            $ret = $this->Tweet_model->get_tweet_info($tid);
            //$this->msclient->send_system_msg($uid, ms\ActionType::PRAISE, $ret['uid'], $tid);
            $params = array();
            $params['from_uid'] = $uid;
            $params['action_type'] = offhub\SysMsgType::PRAISE;
            $params['to_uid'] = array($ret['uid']);
            $params['content_id'] = $tid;
            $this->offclient->SendSysMsgEvent($params);
            $this->offclient->send_event($tid, offhub\EventType::ZAN);
        }
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    function cancel() {
        $request = $this->request_array;
        $response = $this->response_array;
        if(!isset($request['uid']) || !isset($request['tid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $uid = $request['uid'];
        $tid = $request['tid'];
        if (empty($uid) || empty($tid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $ret = $this->Zan_model->remove($uid, $tid);
        if (false === $ret) {
            $response['errno'] = MYSQL_ERR_DELETE;
            log_message('error', __METHOD__ .':'.__LINE__.' zan cancel error, uid['.$uid.'] tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        if ($ret) {
            //redis删除zan_user
            $this->Cache_model->zan_cancel($tid, $uid);
            //$this->Cache_model->zan_user_cancel($tid, $uid);
            $this->offclient->send_event($tid, offhub\EventType::ZAN_CANCEL);
        }
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    function get_praised_list() {
    
        $request = $this->request_array;
        $response = $this->response_array;
        $res_content = array();
        if(!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $owneruid = $request['uid'];
        $pn = isset($request['pn']) ? $request['pn'] : 0;
        $rn = PRAISE_LIST_COUNT;
        $offset = $pn * $rn;

        if (empty($owneruid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }

        $ret_tid = $this->Zan_model->get_praised_list_by_uid($owneruid, $rn, $offset);
        if (false === $ret_tid) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' get praised list error, uid['.$owneruid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if(empty($ret_tid)) {
            goto end;
        }
        if ($ret_tid) {
            foreach($ret_tid as $ret) {
                $tid = $ret['tid'];
                $res = $this->get_tweet_detail($tid);
                if(false === $res || empty($res)) {
                    continue;
                }
                if(isset($res['imgs'])) {
                    $res['imgs'] = $res['imgs'][0];
                }
                $res_content[] = $res;
            }
        }

        $response['data']['content'] = $res_content;

        end:
        $this->renderJson($response['errno'], $response['data']);
    }
}



/* End of file zan.php */
/* Location: ./application/controllers/zan.php */
