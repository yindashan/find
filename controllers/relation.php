<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Relation extends MY_Controller {

    function __construct() {
        parent::__construct(); 
        $this->load->model('Relation_model');
        $this->request_array['pn'] = isset($this->request_array['pn']) ? $this->request_array['pn'] : 0;
        $this->request_array['rn'] = isset($this->request_array['rn']) ? $this->request_array['rn'] : 5;

    }

    function follow() {
        $request = $this->request_array; 
        $response = $this->response_array;
        if (!isset($request['followee_uid'])) {
            log_message('error', __FILE__.':'.__LINE__.' key [followee_uid] not exist!');
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        if (!isset($request['follower_uid'])) {
            log_message('error', __FILE__.':'.__LINE__.' key [follower_uid] not exist!');
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        } 
        $followee_uids = explode('|', $request['followee_uid']);
        $follower_uid = $request['follower_uid'];
        $result_arr = array();
        $arr_sysmsg = array(
            'from_uid'  => $follower_uid,
            'action_type'   => offhub\SysMsgType::FOLLOW,
            'to_uid'    => array(),
        );
        foreach ($followee_uids as $followee_uid) {
            $ret = $this->single_follow($follower_uid, $followee_uid); 
            if (false === $ret) {
                $err_msg = ' add rel error, followee['.$followee_uid.'] follower['.$follower_uid.']';
                log_message('error', __FILE__.':'.__LINE__. $err_msg);
                $this->renderJson(STATUS_ERR_RESPONSE, array('err_msg' => $err_msg));
                return;
            }
            $result_arr[] = array('follow_uid' => $followee_uid, 'type' => $ret);
            $arr_sysmsg['to_uid'][] = $followee_uid;

        }
        $response['data'] = $result_arr;

        $this->offclient->SendSysMsgEvent($arr_sysmsg);

        end:
        $this->renderJson($response['errno'], $response['data']);
    } 

    private function single_follow($follower_uid, $followee_uid) {
        if($followee_uid == $follower_uid) {
            log_message('error', __FILE__.':'.__LINE__
                .' followee_uid == follower_uid, ['.$follower_uid.']');
            return false;
        }
        
        $res = $this->Relation_model->get_relation_info($follower_uid, $followee_uid);
        if (false === $res) {
            log_message('error', __FILE__.':'.__LINE__.' get_relation_info failed, '
                .'follower_uid['.strval($follower_uid).'] followee_uid['.strval($followee_uid).'].');
            return false;
        }
        
        /* is_bigger : follower_uid < followee_uid为true, 反之为false
         *              当is_bigger为true时，follower_uid => a, followee_uid => b
         *              当is_bigger为false时, followee_uid => a, follower_uid = b
         * is_mutual : 是否是相互关注, 看对方是否关注了我即可
         **/
        $is_bigger = $followee_uid > $follower_uid;
        $is_mutual = 0;
        if (!$res) {
            $is_mutual = $this::ONE_WAY_FOLLOW;
        } else {
            $is_a_follow_b = $res['a_follow_b'] != 0;
            $is_b_follow_a = $res['b_follow_a'] != 0;
            $is_mutual = $is_bigger ? $is_b_follow_a : $is_a_follow_b;      // 看对称的情况
            $is_mutual = $is_mutual ? $this::MUTUAL_FOLLOW : $this::ONE_WAY_FOLLOW;
            $cur_followed_status = $is_bigger ? $is_a_follow_b : $is_b_follow_a;    // 当前情况
        }

        if (!$res) {
            // insert relation
            $res = $this->Relation_model->insert($follower_uid, $followee_uid, time(), array(
                'friend_type'   => $this::NO_FRIEND));
            if (false === $res) {
                log_message('error', __FILE__.':'.__LINE__.' insert relation failed, '
                    .'follower_uid='.strval($follower_uid).' followee_uid='.strval($followee_uid));
                return false;
            }
            
        } else {
            // update relation
            if (!$cur_followed_status) {
                $res = $this->Relation_model->update($follower_uid, $followee_uid, time());
                if (false === $res) {
                    log_message('error', __FILE__.':'.__LINE__.' update relation failed, '
                        . 'follower_uid='.strval($follower_uid).' followee_uid='.strval($followee_uid));
                    return false;
                }

                // 如果没修改列, 不更新redis
                if (0 === $res) {
                    return $is_mutual;
                }
            } else {
                // 不需要修改cache, 直接返回
                return $is_mutual;
            }
        }

        $this->load->model('cache_model');
        $this->cache_model->add_follow($follower_uid, $followee_uid);

        $arr_follow_req = array(
            'uid'   => $follower_uid,
            'followee_uid'  => $followee_uid,
        );
        $this->offclient->FollowNewEvent($arr_follow_req);

        return $is_mutual;
    }

    function unfollow() {
        $result_arr = array();
        $request = $this->request_array; 
        $response = $this->response_array;
        if (!isset($request['followee_uid'])) {
            log_message('error', __FILE__.':'.__LINE__.' key [followee_uid] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['follower_uid'])) {
            log_message('error', __FILE__.':'.__LINE__.' key [follower_uid] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        $followee_uid = $request['followee_uid'];
        $follower_uid = $request['follower_uid'];
        $res = $this->Relation_model->get_relation_info($follower_uid, $followee_uid);
        if (false === $res) {
            log_message('error', __FILE__.':'.__LINE__.' get_relation_info error, '
                .'followee_uid='.strval($followee_uid).' follower_uid='.strval($follower_uid));
            $this->renderJson(MYSQL_ERR_SELECT);
            return ;
        }
        if (0 === $res) {
            // 如果没有查询到数据库, 默认完成
            log_message('error', __FILE__.':'.__LINE__.' select error,'
                .'followee_uid='.strval($followee_uid).' follower_uid='.strval($follower_uid));
            goto end;
        }

        $is_bigger = $followee_uid > $follower_uid;
        if ($is_bigger) {
            $cur_type = $res['a_follow_b'] != 0;
        } else {
            $cur_type = $res['b_follow_a'] != 0;
        }

        if (!$cur_type) {
            log_message('error', __FILE__.':'.__LINE__.' cur_type is no follow, do nothing, '
                .'followee_uid='.strval($followee_uid).' follower_uid='.strval($follower_uid));
            goto end;
        }

        $ret = $this->Relation_model->update($follower_uid, $followee_uid, 0);
        if (false === $ret) {
            log_message('error', __FILE__.':'.__LINE__.' update mysql failed, '
                .'followee_uid='.strval($followee_uid).' follower_uid='.strval($follower_uid));
            $this->renderJson(MYSQL_ERR_UPDATE);
            return ;
        }
        if (0 === $ret) {
            log_message('error', __FILE__.':'.__LINE__.' update mysql doing nothing, '
                .'followee_uid=jj'.strval($followee_uid).' follower_uid='.strval($follower_uid));
            goto end;
        }

        $this->load->model('cache_model');
        $this->cache_model->cancel_follow($follower_uid, $followee_uid);

        end:
        $this->renderJson(STATUS_OK, $response['data']);
    }

    // 关注列表
    function followee_list() {
        $result_arr = array(); 
        $request = $this->request_array;
        $response = $this->response_array;
        if (!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.':'.__LINE__.' key [uid] not exist!');
            goto end;
        }
        if (!isset($request['pn'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.':'.__LINE__.' key [pn] not exist!');
            goto end;
        }
        if (!isset($request['rn'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.':'.__LINE__.' key [rn] not exist!');
            goto end;
        }
        $uid = $request['uid'];
        $pn = $request['pn']; 
        $rn = $request['rn'];

        $res = $this->Relation_model->get_followee_list_by_uid($uid, $rn, $rn * $pn);
        if (false === $res) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __FILE__.':'.__LINE__.' get followee_list_by_uid error.');
            goto end;
        }

        $this->load->model('cache_model');
        foreach ($res as $row) {
            $id = $row['uid'];
            $user_info = array();
            $user_detail = $this->get_user_detail_by_uid($id, array('sname', 'avatar'));
            if (false === $user_detail || NULL === $user_detail) {
                continue; 
            }
            $user_ext_info = $this->cache_model->get_user_ext_info($id);
            if (false === $user_ext_info || NULL === $user_ext_info) {
                continue;
            }
            $user_info['uid'] = $id;
            $user_info['follow_type'] = $row['follow_type'] ? $this::MUTUAL_FOLLOW : $this::ONE_WAY_FOLLOW;
            $user_info['name'] =  $user_detail['sname'];
            $user_info['avatar'] =  $user_detail['avatar'];
            $user_info['followee_num'] = intval($user_ext_info['followee_num']);
            $user_info['follower_num'] = intval($user_ext_info['follower_num']);
            $user_info['tweet_num'] = intval($user_ext_info['tweet_num']);

            $result_arr[] = $user_info; 
        }
        $response['errno'] = 0;
        $response['data'] = $result_arr;
    end:
        $this->renderJson($response['errno'], $response['data']);
    } 

    function follower_list() {
        $result_arr = array(); 
        $request = $this->request_array;
        $response = $this->response_array;
        if (!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.':'.__LINE__.' key [uid] no exist!');
            goto end;
        }
        if (!isset($request['pn'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.':'.__LINE__.' key [pn] no exist!');
            goto end;
        }
        if (!isset($request['rn'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.':'.__LINE__.' key [rn] no exist!');
            goto end;
        }
        $uid = $request['uid'];
        $pn = $request['pn']; 
        $rn = $request['rn'];
        $res = $this->Relation_model->get_follower_list_by_uid($uid, $rn, $rn * $pn);
        if (false === $res) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __FILE__.':'.__LINE__.' get_follower_list_by_uid error.');
            goto end;
        }
        $this->load->model('cache_model');
        foreach ($res as $row) {
            $id = $row['uid'];
            $user_info = array();
            $user_detail = $this->get_user_detail_by_uid($id, array('sname', 'avatar'));
            if (false === $user_detail || NULL === $user_detail) {
                continue; 
            }
            $user_ext_info = $this->cache_model->get_user_ext_info($id);
            if (false === $user_ext_info || NULL === $user_ext_info) {
                continue;
            }
            $user_info['uid'] = $id;
            $user_info['follow_type'] = $row['follow_type'] ? $this::MUTUAL_FOLLOW : $this::NO_FOLLOW;
            $user_info['name'] =  $user_detail['sname'];
            $user_info['avatar'] =  $user_detail['avatar'];
            $user_info['followee_num'] = intval($user_ext_info['followee_num']);
            $user_info['follower_num'] = intval($user_ext_info['follower_num']);
            $user_info['tweet_num'] = intval($user_ext_info['tweet_num']);

            $result_arr[] = $user_info; 
        }
        $response['data'] = $result_arr;
    end:
        $this->renderJson($response['errno'], $response['data']);
    } 
}



/* End of file relation.php */
/* Location: ./application/controllers/relation.php */
