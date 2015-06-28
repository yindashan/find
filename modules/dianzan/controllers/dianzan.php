<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dianzan_Dianzan_module extends CI_Module {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct() {
		parent::__construct();
	}

	function add($uid, $tid, $username) {
		$this->load->model('Data_model');
        $result = $this->Data_model->add($uid, $tid, $username);
        $ret = array();
        if ($result) {
            $ret['errno'] = 0;
            $ret['msg'] = 'dianzan success';
        }
        else {
            $ret['errno'] = -1;
            $ret['msg'] = 'dianzan not success';
        }
        return $ret;
	}

    function remove($uid, $tid) {
        $this->load->model('Data_model');
        return $this->Data_model->remove($uid, $tid);
    }

    function get_tid_dianzan_dict($uid, $tid_list) {
        $this->load->model('Data_model');
        return $this->Data_model->get_tid_dianzan_dict($uid, $tid_list);
    }

    function get_count_by_tid($tid) {
        $this->load->model('Data_model');
        return $this->Data_model->get_count_by_tid($tid);
    }

    function get_user_list($tid) {
        $this->load->model('Data_model');
        return $this->Data_model->get_user_list($tid);
    }
}
