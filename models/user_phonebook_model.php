<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  通讯录联系人model
 */
class User_phonebook_model extends CI_Model {

    private $table_name = 'ci_user_phonebook';
	function __construct()
	{
		parent::__construct();
	}

    /**
     * 获取通讯录中有此新注册联系人的用户列表
     *
     * @param contact 用户手机号
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return false:错误 NULL:空 arr:row 
     */
    function get_user_list_by_mobile($mobile, $limit, $offset) {
        $this->db->select('uid, mobile');
        $this->db->where('mobile', $mobile);
        $this->db->limit($limit, $offset);
        $result = $this->db->get($this->table_name); 
        //$sql = $this->db->last_query();
        if (false === $result) {
            return false; 
        } else if (0 === $result->num_rows) {
            return NULL; 
        } else {
            return $result->result_array();
        }
    }

    function get_mobile_list_by_user($uid) {
        $this->db->select('mobile'); 
        $this->db->where('uid', $uid);
        $result = $this->db->get($this->table_name);
        if (false === $result) {
            return false; 
        } else if (0 == $result->num_rows) {
            return array(); 
        } else {
            $result_arr = array();
            foreach($result->result_array() as $row) {
                $result_arr[] = $row['mobile']; 
            }
            return $result_arr;
        }
    }

}
