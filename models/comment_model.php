<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 评论model
 */
class Comment_model extends CI_Model {

    private $table_name = 'ci_comment';
	function __construct()
	{
		parent::__construct();
	}

    /**
     * 发表评论
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function add($data) {

        $result = $this->db->insert($this->table_name, $data);
        if((false === $result) 
            || $this->db->affected_rows() == 0) {
            return false;
        }else if($this->db->affected_rows() > 0) {
            $tid = $this->db->insert_id();
            return $tid;
        }
        return false;
    }

    /**
     * 根据用户id和评论id获取评论列表，上拉刷新
     *
     * @param string cid 评论id
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 评论列表
     */
    function get_list_by_cid_uid($cid, $uid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->where('cid >', $cid);
        //$this->db->order_by('ctime', 'desc');
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }

    /**
     * 根据帖子id和评论id获取评论列表，上拉刷新
     *
     * @param string tid 帖子id
     * @param string cid 评论id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 评论列表
     */
    function get_list_by_cid_tid($cid, $tid, $limit) {
    
        $this->db->select('*');
        $this->db->where('tid', $tid);
        //$this->db->where('cid <', $cid);
        $this->db->where('cid >', $cid);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false; 
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }
    /**
     * 获取用户评论列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 评论列表
     */
    function get_list_by_uid($uid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('uid', $uid);
        //$this->db->order_by('ctime', 'desc');
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false; 
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }

    /**
     * 根据帖子id获取评论列表
     * 
     * @param string tid 帖子id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 评论列表
     */
    function get_list_by_tid($tid, $limit) {
    
        $this->db->select('*');
        $this->db->where('tid', $tid);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false; 
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }

    /**
     * 根据评论id获取评论详情
     *
     * @param int cid 评论id
     * @return array 评论详情
     */
    function get_detail_by_cid($cid) {
    
        $this->db->select('*');
        $this->db->where('cid', $cid);

        $result = $this->db->get($this->table_name);
        if (false === $result) {
            return false; 
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->row_array();
    }

    /** 
     * 更新评论
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function update_by_cid($cid, $data) {

        $this->db->where('cid', $cid);
        $result = $this->db->update($this->table_name, $data);

        if((false === $result) 
            || (0 == $this->db->affected_rows())) {
                return false;
            }   
        return $this->db->affected_rows();
    }

    function update_by_cid_uid($cid, $uid, $data) {

        $this->db->where('cid', $cid);
        $this->db->where('uid', $uid);
        $result = $this->db->update($this->table_name, $data);

        if((false === $result) 
            || (0 == $this->db->affected_rows())) {
                return false;
            }   
        return $this->db->affected_rows();
    }


    function get_comment_num($tid) {
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid); 
        $this->db->where('is_del', 0);
        return $this->db->count_all_results();
    }

}




/* End of file comment_model.php */
/* Location: ./application/models/comment_model.php */
