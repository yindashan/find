<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Zan extends MY_Controller {
    function __construct() {
        parent::__construct();

        $this->load->model('Tweet_action_model');
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
        $tid = intval($request['tid']);

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

        $ret_tweet_info = $this->Tweet_model->get_tweet_info($tid);
        if($ret_tweet_info) {
            $owneruid = $ret_tweet_info['uid'];
        }else {
            $owneruid = 0;
        } 

        $data = array(
            'uid' => $uid,
            'tid' => $tid,
            'action_type' => 2,
            'ctime' => time(),
            'owner_id' => $owneruid,
        );
        $ret = $this->Tweet_action_model->add($data);

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

            //$ret = $this->Tweet_model->get_tweet_info($tid);
            $params = array();
            $params['from_uid'] = $uid;
            $params['action_type'] = offhub\SysMsgType::PRAISE;
            $params['to_uid'] = array($owneruid);
            $params['content_id'] = $tid;
            $this->offclient->SendSysMsgEvent($params);
            $this->offclient->send_event($tid, offhub\EventType::ZAN);
            $this->offclient->UpdateFriendQueue(array('uid'=>$uid, 'tid'=>$tid, 'msg_type'=>1));
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
        $ret = $this->Tweet_action_model->remove($uid, $tid, 2);
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

    function user_list() {
        $request = $this->request_array;
        $response = $this->response_array;
        $uid = $request['uid'];
        if (empty($uid)) {
            $uid = 0;
        }
        $tid = $request['tid'];
        $pn = isset($request['pn']) ? $request['pn']: 0;
        $rn = isset($request['rn']) ? $request['rn']: 20;
        if (empty($tid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }

        $ret = $this->Tweet_action_model->get_user_list($tid, 2, $rn, $pn*$rn);
        $data = array();
        foreach($ret as $id) {
            $user_info = $this->get_user_detail_by_uid($id['uid'], array('uid','avatar','intro', 'sname'));
            //$user_info = $this->get_user_detail_by_uid($id['uid'], '*');
            $user_info['follow_type'] = $this->get_relation_type($id['uid'], $uid);
            $user_info['ctime'] = intval($id['ctime']);
            if (!empty($user_info)) {
                $data[] = $user_info;
            }
        }

        $response['data'] = array(
            'content' => $data,
        );
        end:
        $this->renderJson($response['errno'], $response['data']);

    }

   /*
    * 个人中心赞列表接口
    */
    function zan_list() {
        $request = $this->request_array;
        log_message('debug', 'zan_list_request:'.json_encode($request));
        $response = $this->response_array;
        $uid = $request['uid'];
        if (empty($uid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $rn = USER_TWEET_LIST_COUNT;           // 一页返回数量, 默认20条
        $type = isset($request['type']) ? $request['type'] : 'new'; // type = 'new'新页, 'next'翻页
        if(empty($type)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        
        //获取帖子ID和对应的点赞数列表
        if ('new' == $type) {
            // 首页
            $ret = $this->Tweet_action_model->get_tweet_list_by_uid($uid, 2, $rn);
        } else if ('next' == $type) {
            // 翻页
            if (!isset($request['last_tid'])) {
                $response['errno'] = STATUS_ERR_REQUEST;
                log_message('error', __METHOD__ .':'.__LINE__.' request error, key [last_tid] not exist. errno[' . $response['errno'] .']');
                goto end;
            }
            $tid = $request['last_tid'];
            $ret = $this->Tweet_action_model->get_next_tweet_list_by_uid($uid, 2, $tid, $rn);
        } else {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, key type['.$type.'] not valid. errno[' . $response['errno'] .']');
            goto end;
        }
        
        $data = array();
        foreach($ret as $id) {
            $new_user = $this->Tweet_action_model->get_new_user($id['tid']);
            if (!isset($new_user[0])) {
                continue;
            }
            $new_user_id = $new_user[0]['uid'];
            $new_user_ctime = $new_user[0]['ctime'];
            $user_info = $this->get_user_detail_by_uid($new_user_id, array('uid', 'avatar', 'intro', 'sname'));
            $user_info['tid'] = $id['tid'];
            $tweet = $this->Tweet_model->get_tweet_info($id['tid']);
            $img_arr = json_decode($tweet['imgs'], true);
            $user_info['tweet_url'] = $img_arr[0]['n']['url'];
            
            $user_info['user_num'] = $id['user_num'];
            $user_info['ctime'] = $new_user_ctime;
            //$user_info['ctime'] = date("Y-m-d H:i:s", $new_user_ctime);
            //$user_info['ctime'] = $this->format_time($new_user_ctime);
            if (!empty($user_info)) {
                $data[] = $user_info;
            }
        }
        
        $response['data'] = array(
            'content' => $data,
        );
        end:
        $this->renderJson($response['errno'], $response['data']);
    
    } 

    /*function get_praised_list() {
    
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
    }*/
}



/* End of file zan.php */
/* Location: ./application/controllers/zan.php */
