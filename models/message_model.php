<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 私信model
 */
class Message_model extends CI_Model {

    private $table_name = 'ci_message';
	function __construct()
	{
		parent::__construct();
	}

    /**
     * 发私信
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
     * 获取私信列表
     *
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 私信列表
     */
    function get_list($limit, $offset) {

        $result = $this->db->get($this->table_name, $limit, $offset);
        if($result->num_rows > 0) {
            return $result->result_array();
        }
    }

    /**
     * 根据私信id获取私信列表，上拉加载更多
     *
     * @param string mid 私信id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 私信列表
     */
    function get_list_by_mid($mid, $uid, $limit) {
    
        $this->db->select('*');
        $this->db->where('to_uid', $uid);
        $this->db->where('mid <', $mid);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if ($result) {
            return $result->result_array();
        
        } else {
            return false; 
        }
    }

    /**
     * 获取用户私信列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 私信列表
     */

    /*
    function get_list_by_touid($uid, $limit) {
    
        $this->db->select('*');
        $this->db->where('to_uid', $uid);
        $this->db->order_by('ctime', 'desc');
        $this->db->group_by('from_uid');
        //$this->db->limit($limit, $offset);
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if ($result) {
            return $result->result_array();
        } else {
            return false; 
        }
    }
     */

    function get_list_by_uid($uid, $limit) {
    
        $this->db->select('*');
        $this->db->where('from_uid', $uid);
        $this->db->or_where('to_uid', $uid);
        $this->db->order_by('ctime', 'desc');
        //$group_by = array('from_uid', 'to_uid');
        //$this->db->group_by($group_by);
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if ($result) {
            return $result->result_array();
        } else {
            return false; 
        }
    }
    /**
     * 获取对话列表
     */
    function get_talk_list($from_uid, $to_uid, $mid = 0) {

        $this->db->select('*');
        $this->db->where('from_uid', $from_uid);
        $this->db->where('to_uid', $to_uid);
        if($mid !== 0) {
            $this->db->where('mid >', $mid);
        }

        $result = $this->db->get($this->table_name); 
        //$sql = $this->db->last_query();
        if($result->num_rows > 0) {
            return $result->result_array();
        }
        return false;
    
    }

    /**
     * 根据私信id获取私信详情
     *
     * @param int mid 私信id
     * @return array 私信详情
     */
    function get_detail_by_mid($mid) {
    
        $this->db->select('*');
        $this->db->from($this->table_name);
        $this->db->where('mid', $mid);

        $result = $this->db->get();
        if($result->num_rows > 0) {
            return $result->row_array();
        }
        return false;
    }


    function get_detail_by_mids($mids) {
        $step = 10;
        $result = array();
        for ($i = 0; $i < count($mids); $i = $i + $step) {
            $this->db->select('*'); 
            $this->db->where_in('mid', array_slice($mids, $i , $step));
            $this->db->from($this->table_name); 
            $ret = $this->db->get();
            if ($ret && $ret->num_rows > 0) {
                $result = array_merge($result, $ret->result_array());
            }
        } 
        usort($result, 
              function ($a, $b) {
                   return intval($a['mid']) - intval($b['mid']); 
              });
        return $result;
    }

    function get_previous_msgs($uid, $other_uid, $mid, $num) {
        $this->db->select('*'); 
        $this->db->where("((from_uid=".$uid." and to_uid=".$other_uid.") or (from_uid=".$other_uid." and to_uid=".$uid.")) and mid<".$mid);
        $this->db->order_by('mid', 'desc');
        $this->db->limit($num);
        $ret = $this->db->get();
        if (false === $ret) {
            return false; 
        } else if (0 == $ret->num_rows){
            return null;
        } else {
            return $ret->result_array(); 
        }
    }

    /**
     * 根据私信id删除私信
     *
     * @param string mid 私信id
     * @return bool 状态
     */
    function remove_by_mid($mid) {
    
        $this->db->where('mid', $mid);
        $result = $this->db->delete($this->table_name); 
        return $result;
    }

    /**
     * 根据用户id删除私信
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

}


/* End of file message_model.php */  
/* Location: ./application/models/message_model.php */ 
