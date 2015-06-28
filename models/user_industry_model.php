<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  用户行业model
 */
class User_industry_model extends CI_Model {

    private $table_name = 'ci_user_industry';
	function __construct()
	{
		parent::__construct();
	}

    function get_user_industry($uid) {
        $this->db->select('industry_id');
        $this->db->where('uid', $uid);
        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            log_message('error', 'get_user_industry error: msg['.$this->db->_error_message().']');
            return false; 
        } else if (0 === $result->num_rows) {
            return NULL; 
        } else {
            return $result->result_array();
        }
    }

}
