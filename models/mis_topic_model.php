<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mis_topic_model extends CI_Model {

    private $db_offline;
    private $table_name = 'ci_tweet_offline';
	function __construct()
	{
        parent::__construct();

        $this->db_offline = $this->load->database('offline', TRUE);
	}

    /**
     * 发表帖子
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function add($request) {

        //$result = $this->db_offline->db->insert($this->table_name, $request);
        $result = $this->db_offline->insert($this->table_name, $request);
        if($this->db_offline->affected_rows() > 0) {
            $tid = $this->db_offline->insert_id();
            return $tid;
        }
        return false;
    }

    /**
     * 获取帖子列表
     *
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    function get_list($limit, $offset) {

        $result = $this->db->get($this->table_name, $limit, $offset);
        if($result->num_rows > 0) {
            return $result->result_array();
        }
    }

    /**
     * 根据帖子id获取帖子列表，上拉刷新
     *
     * @param string tid 帖子id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    function get_list_by_tid($tid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('tid >', $tid);
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        if($result->num_rows > 0) { 
            return $result->result_array();
        }
    }

    /**
     * 获取用户帖子列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    function get_list_by_uid($uid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        if($result->num_rows > 0) {
            return $result->result_array();
        }
    }

    /**
     * 根据帖子id获取帖子详情
     *
     * @param int tid 帖子id
     * @return array 帖子详情
     */
    function get_detail_by_tid($tid) {
    
        $this->db->select('*');
        $this->db->from($this->table_name);
        //$this->db->where('tid', intval($tid));
        $this->db->where('tid', $tid);

        $result = $this->db->get();
        if($result->num_rows > 0) {
        //$str = $this->db->last_query();
        //$result = $this->db->query("SELECT * FROM `lj_tweet` WHERE `tid` = 1");
        //echo $str;exit;
        //print_r($result);exit;
            return $result->row_array();
        }
    }

    function get_fields_by_tid($tid, $fields) {
        $this->db->select($fields);
        $this->db->from($this->table_name);
        $this->db->where('online_tid', $tid);
        $result = $this->db->get();
        if ($result) {
            return $result->row_array(); 
        } 
    }

    /**
     * 根据帖子id删除帖子
     *
     * @param string tid 帖子id
     * @return bool 状态
     */
    function remove_by_tid($tid) {
    
        $this->db->where('tid', $tid);
        $result = $this->db->delete($this->table_name); 
        return $result;
        if($result->num_rows > 0) {
            return $result;
        }
    }

    /**
     * 根据用户id删除帖子
     *
     * @param string 用户id
     * @return bool 状态
     */
    function remove_by_uid($uid) {
    
        $this->db->where('uid', $uid);
        $result = $this->db->delete($this->table_name); 
        if($result->num_rows > 0) {
            return $result;
        }
    }

    /**
     * 更新帖子
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function update_by_tid($tid, $data) {
        $this->db->where('online_tid', $tid);
        $result = $this->db->update($this->table_name, $data); 
        if($this->db->affected_rows() >= 0) {
            return $result;
        }
        return false;
    }

    /**
     * 统计最近一个月内发帖数最多的用户top30
     * 
     * @param array 请求参数
     * @return bool 状态
     */
    function get_topic_statistics($ctime, $limit, $offset) {
        $this->db_offline->select('uid, count(*)');
        $this->db_offline->where('ctime >', $ctime);
        $this->db_offline->group_by('uid');
        $this->db_offline->order_by('count(*)', 'desc');
        $this->db_offline->limit($limit, $offset);
        $result = $this->db_offline->get($this->table_name); 
        //$sql = $this->db_offline->last_query();
        //echo $sql;
        if (false === $result) {
            return false; 
        } else if($result->num_rows > 0) {
            return $result->result_array();
        } else {
            return NULL; 
        }   

    }


}
