<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
class Info extends MY_Controller {
	function __construct() {
		parent::__construct();
        
        $this->load->model('Message_sum_model');
        $this->_set_token_check(false);
    }

    function msgnum() {
        $request = $this->request_array;
        if (!isset($request['uid']) || empty($request['uid'])) {
            $this->renderJson(STATUS_ERR_REQUEST);  
            return;
        }
        $uid = intval($request['uid']);
        $from_uids = $this->Message_sum_model->get_private_msg_num($uid);
        $ret = array();
        $private_msg_num = 0;
        $from_uid_list = array();
        $ret['private_msg'] = array('num'=>$private_msg_num, 'from_uid'=>$from_uid_list);
        if (!empty($from_uids)) {
            $private_msg_num = count($from_uids);
            $from_uid_list = $from_uids;
            $ret['private_msg']['num'] = $private_msg_num;
            $ret['private_msg']['from_uid'] = $from_uid_list;
        }

        $sys_msg_num = 0;
        $ret['sys_msg'] = array('num'=>$sys_msg_num);
        $sys_msg_num = $this->Message_sum_model->get_sys_msg_num($uid);
        $ret['sys_msg']['num'] = $sys_msg_num;
        $this->renderJson(0, $ret);
    }
}
