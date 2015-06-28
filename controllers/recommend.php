<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Recommend extends MY_Controller {

    function __construct() {
        parent::__construct(); 
        $this->load->model('relation_model');
        $this->load->model('hot_user_model');

    }

    function friend_recommend() {
        $request = $this->request_array;
        if (!isset($request['uid'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }
        $uid = $request['uid'];
        $pn = isset($this->request_array['pn']) ? intval($this->request_array['pn']) : 0;
        $rn = isset($this->request_array['rn']) ? intval($this->request_array['rn']) : 10;
        $start = $pn * $rn;
        
        $result_arr = array();
        $rec_uids = $this->relation_model->get_rec_friends($uid, $start, $rn); 
        if ($rec_uids) {
            $this->load->model('cache_model');
            foreach ($rec_uids as $rec_uid) {
                $ret = $this->cache_model->get_user_info($rec_uid, 
                    array('sname', 'avatar', 'company', 'company_job', 'ukind', 'ukind_verify')
                );
                if (!$ret) {
                    continue; 
                } 
                $ret['uid'] = $rec_uid;
                $ret['is_verify'] = $ret['ukind_verify'];
                unset($ret['ukind_verify']);
                $result_arr[] = $ret;
            }
        }

        $this->renderJson(STATUS_OK, array('recommend_list' => $result_arr));
        $this->msclient->clear_red_by_uid(intval($uid), 7, 0); 
    }

    /**
     * 删除推荐记录
     * 
     */
    function delete_recommend() {
        $request = $this->request_array;
        if (!isset($request['from_uid']) || !isset($request['to_uid'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }
        $uid = $request['from_uid'];
        $rec_uid = $request['to_uid'];
        $ret = $this->relation_model->cancel_rec($uid, $rec_uid);
        if (false === $ret) {
            $this->renderJson(MYSQL_ERR_UPDATE); 
            return;
        }
        $this->renderJson(STATUS_OK);
    }

    /**
     * 获取在线好友推荐的接口
     *
     */
    function new_user_recommend() {
        $request = $this->request_array;
        if (!isset($request['uid'])) {
            $this->renderJson(STATUS_ERR_REQUEST);
            return;
        }
        $pn = isset($this->request_array['pn']) ? intval($this->request_array['pn']) : 0;
        $rn = isset($this->request_array['rn']) ? intval($this->request_array['rn']) : 10;
        $start = $pn * $rn;

        $uid = $request['uid'];

        $rec_uids = array();
        $hot_user = $this->hot_user_model->get_hot_user(); 
        $off = $start + $rn - count($hot_user);
        if ($off <= 0) {
        //直接拿最热的用户推荐即可 
            $rec_uids = array_slice($hot_user, $start, $rn);
        } else {
        //需要获取通讯录推荐好友
            $this->load->model('cache_model');
            $ret = $this->cache_model->get_online_rec($uid);
            $online_rec_uids = array();
            if ($ret) {
                $online_rec_uids = json_decode($ret, true); 
            } else {
                $this->load->model('user_phonebook_model');
                $tele_list = $this->user_phonebook_model->get_mobile_list_by_user($uid); 
                if ($tele_list) {
                    $this->load->model('user_model');
                    $uids = $this->user_model->get_uids_by_phone($tele_list);
                    foreach ($uids as $rec_uid) {
                        if (!in_array($rec_uid, $hot_user) && $uid != $rec_uid) {
                            $online_rec_uids[] = $rec_uid; 
                        } 
                    }
                    $this->cache_model->set_online_rec($uid, $online_rec_uids); 
                }
            }
            if ($start < count($hot_user)) {
                $rec_uids = array_slice($hot_user, $start);
                $rec_uids = array_merge($rec_uids, array_slice($online_rec_uids, 0, $off));
            } else {
                $rec_uids = array_slice($online_rec_uids, $start - count($hot_user), $rn); 
            }
        }

        $recommend_list = array();
        foreach ($rec_uids as $rec_uid) {
            $this->load->model('cache_model');            
            $info = $this->cache_model->get_user_info($rec_uid, array('sname', 'avatar', 'company', 'company_job', 'ukind', 'ukind_verify'));
            if (!$info) {
                continue; 
            }
            $info['uid'] = $rec_uid;
            $info['is_verify'] = intval($info['ukind_verify']);
            $info['ukind'] = intval($info['ukind']);
            unset($info['ukind_verify']);
            $recommend_list[] = $info;
        }
        $this->renderJson(STATUS_OK, array('recommend_list' => $recommend_list));
    }

}


/* End of file recommend.php */
/* Location: ./application/controllers/recommend.php */
