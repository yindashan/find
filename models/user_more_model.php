<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  关注关系model
 */
class User_more_model extends CI_Model {

    private $table_name = 'ci_user_more';
	function __construct()
	{
		parent::__construct();
	}

    /**
     * 是否是单边关注 
     *
     * @param uid user_id
     * @param follower_uid  粉丝id
     * @return false:错误 NULL:空 arr:row 
     */
    function get_user_more_info($uid) {
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false; 
        } else if (0 === $iresult->num_rows) {
            return NULL; 
        } else {
            return $result->result_array()[0];
        }
    }
}
