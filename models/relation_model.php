<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  关注关系model
 */
class Relation_model extends CI_Model {

    private $table_name = 'ci_user_relation';
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
    function get_relation_info($follower_uid, $followee_uid) {
        $is_bigger = $followee_uid > $follower_uid;

        if ($is_bigger) {
            $this->db->where('a_uid', $follower_uid);
            $this->db->where('b_uid', $followee_uid);
        } else {
            $this->db->where('a_uid', $followee_uid);
            $this->db->where('b_uid', $follower_uid);
        }
        $this->db->select(array('a_follow_b', 'b_follow_a'));
        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false; 
        } else if (0 === $result->num_rows) {
            return 0; 
        } else {
            return $result->result_array()[0];
        }
    }

    function cancel_rec($uid, $rec_uid) {
        $this->db->where('a_uid', $uid); 
        $this->db->where('b_uid', $rec_uid); 
        $this->db->update($this->table_name, array('need_recommend' => 0)); 
        $ret = $this->db->affected_rows();
        if (0 > $ret) {
            return false; 
        }
        return $ret;
    }

    function get_rec_friends($uid, $start, $num) {
        $this->db->select('b_uid');
        $this->db->where('a_uid', $uid);
        $this->db->where('need_recommend', 1);
        $this->db->where('friend_type', 1);
        $this->db->where('follow_type', 0);
        $this->db->limit($num, $start);
        $result = $this->db->get($this->table_name);
        if (false === $result) {
            return false; 
        } else if (0 === $result->num_rows) {
            return NULL; 
        } else {
            $id_arr = array();
            foreach ($result->result_array() as $row) {
                $id_arr[] = $row['b_uid']; 
            } 
            return $id_arr;
        }
    }

    /**
     * 关注 
     *
     * @param arr 请求参数
     * @return bool 状态
     */
    function insert($follower_uid, $followee_uid, $time_str, $arr = array()) {
        $is_bigger = $followee_uid > $follower_uid;

        if ($is_bigger) {
            $query = array(
                'a_uid' => $follower_uid,
                'b_uid' => $followee_uid,
                'a_follow_b' => $time_str,
                'b_follow_a' => 0,
            );
            $query = array_merge($query, $arr);
        } else {
            $query = array(
                'a_uid' => $followee_uid,
                'b_uid' => $follower_uid,
                'a_follow_b' => 0,
                'b_follow_a' => $time_str,
            );
            $query = array_merge($query, $arr);
        }

        $ret = $this->db->insert($this->table_name, $query);
        if ($ret && $this->db->affected_rows() <= 0) {
            return false;
        }

        // TODO : added to redis

        return true;
    }

    function get_follower_num ($uid) {
        $this->db->where('b_uid', $uid);
        $this->db->where('a_follow_b !=', 0);
        $this->db->from($this->table_name);
        $follower_num = $this->db->count_all_results();

        $this->db->where('a_uid', $uid);
        $this->db->where('b_follow_a !=', 0);
        $this->db->from($this->table_name);
        $follower_num += $this->db->count_all_results();

        return $follower_num;
    }

    function get_followee_num ($uid) {
        $this->db->where('a_uid', $uid);
        $this->db->where('a_follow_b !=', 0);
        $this->db->from($this->table_name);
        $followee_num = $this->db->count_all_results();

        $this->db->where('b_uid', $uid);
        $this->db->where('b_follow_a !=', 0);
        $this->db->from($this->table_name);
        $followee_num += $this->db->count_all_results();

        return $followee_num;
    }

    /**
     * 关注 
     *
     * @param uid 请求参数
     * @param follower_uid 请求参数
     * @param arr 请求参数
     * @return bool 状态
     */
    function update($follower_uid, $followee_uid, $time_str, $arr = array()) {
        $is_bigger = $followee_uid > $follower_uid;
        if ($is_bigger) {
            $this->db->where('a_uid', $follower_uid);
            $this->db->where('b_uid', $followee_uid);
            $query = array(
                'a_follow_b' => $time_str
            );
            $query = array_merge($query, $arr);
        } else {
            $this->db->where('a_uid', $followee_uid);
            $this->db->where('b_uid', $follower_uid);
            $query = array(
                'b_follow_a' => $time_str,
            );
            $query = array_merge($query, $arr);
        }
        $ret = $this->db->update($this->table_name, $query); 
        
        if (false === $ret) {
            return false;
        }
        return $this->db->affected_rows();
    }


    /**
     * 取消关注
     *
     * @param uid 请求参数
     * @param follower_uid 请求参数
     * @return bool 状态
     */
    function remove($uid, $follower_uid) {
        $this->db->where('b_uid', $uid);
        $this->db->where('a_uid', $follower_uid);
        return $this->db->delete($this->table_name);
    }


    /**
     * 获取用户粉丝列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     */
    function get_follower_list_by_uid($uid, $limit, $offset) {
        $this->db->select('a_uid, b_follow_a');
        $this->db->where('b_uid', $uid);
        $this->db->where('a_follow_b !=', 0);

        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false; 
        }
        $user_num = $result->num_rows;
        $arr_rtn = array();
        $arr_result = $result->result_array();

        for ($i = 0, $j = $offset; $i < $limit && $j < $user_num;$i++, $j++) {
            $arr_rtn[] = array(
                'uid'   => $arr_result[$j]['a_uid'],
                'follow_type'   => $arr_result[$j]['b_follow_a'] != 0,
            );
        }

        
        $rtn_size = count($arr_result);
        if ($rtn_size >= $limit) {
            return $arr_rtn;
        }
        $offset -= $user_num;
        if ($offset < 0) {
            $offset = 0;
        }
        $limit -= $rtn_size;

        $this->db->select('b_uid, a_follow_b');
        $this->db->where('a_uid', $uid);
        $this->db->where('b_follow_a !=', 0);
        $this->db->limit($limit, $offset);
        $result = $this->db->get($this->table_name);
        if (false === $result) {
            return false;
        }
        foreach ($result->result_array() as $item) {
            $arr_rtn[] = array(
                'uid'   => $item['b_uid'],
                'follow_type'   => $item['a_follow_b'] != 0,
            );
        }

        return $arr_rtn;
    }

    /**
     * 获取用户关注的人列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     */
    function get_followee_list_by_uid($uid, $limit, $offset) {
        $this->db->select('b_uid, b_follow_a');
        $this->db->where('a_uid', $uid);
        $this->db->where('a_follow_b !=', 0);
        $arr_rtn = array();

        $result = $this->db->get($this->table_name); 
        if (false === $result) {
            return false;
        }
        $user_num = $result->num_rows;
        $arr_result = $result->result_array();

        for ($i = 0, $j = $offset; $i < $limit && $j < $user_num; $i++, $j++) {
            $arr_rtn[] = array(
                'uid'   => $arr_result[$j]['b_uid'],
                'follow_type'   => $arr_result[$j]['b_follow_a'] != 0,
            );
        }
        $rtn_size = count($arr_rtn);
        if ($rtn_size >= $limit) {
            return $arr_rtn;
        }
        $offset -= $user_num;
        if ($offset < 0) {
            $offset = 0;
        }
        $limit -= $rtn_size;

        $this->db->select('a_uid, a_follow_b');
        $this->db->where('b_uid', $uid);
        $this->db->where('b_follow_a !=', 0);
        $this->db->limit($limit, $offset);
        
        $result = $this->db->get($this->table_name);
        if (false === $result) {
            return false;
        }
        foreach ($result->result_array() as $item) {
            $arr_rtn[] = array(
                'uid'   => $item['a_uid'],
                'follow_type'   => $item['a_follow_b'] != 0,
            );
        }

        return $arr_rtn;
    }

}
