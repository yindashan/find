<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  好友推荐model
 */
class Recommend_model extends CI_Model {

    private $table_name = 'ci_user_recommend';
	function __construct()
	{
		parent::__construct();
	}

    /**
     * 获取推送的用户列表
     *
     * @param uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return false:错误 NULL:空 arr:row 
     */
    function get_recommend_by_uid($uid, $limit, $offset) {
        $this->db->select('b_uid, recommend_type, recommend_time');
        $this->db->where('a_uid', $uid);
        $this->db->where('is_deleted =', 1);
        $this->db->order_by('recommend_time', 'desc');
        //$this->db->limit($limit, $offset);
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


    /**
     * 根据用户id删除推荐记录,只标志删除位，不真正删除
     * 
     * @param string a_uid
     * @param string b_uid
     * @return bool 状态
     */
    function delete_by_uid($a_uid, $b_uid) {
        $data = array(
                'is_deleted' => 0,
                );
        $this->db->where('a_uid', $a_uid);
        $this->db->where('b_uid', $b_uid);
        $result = $this->db->update($this->table_name, $data); 
        if($result) {
            return true;
        }
        return false;
    }

    /**
     * 保存推荐信息
     *  
     * @param array 请求参数
     * @return bool 状态
     */
     function add($request) {
         $result = $this->db->insert($this->table_name, $request);
         if($this->db->affected_rows() > 0) {
             $tid = $this->db->insert_id();
             return $tid;
         }
         return false;
     }

    /**
     * 批量保存推荐信息
     *  
     * @param array 请求参数
     * @return bool 状态
     */
     function add_batch($request) {
         $result = $this->db->insert_batch($this->table_name, $request);
         //print_r($result);
         return $result;
     }

}
