<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tweet_action_model extends CI_Model {
    private $table_name = 'ci_tweet_action';

	function __construct() {
		parent::__construct();
	}

    /**add favourite
     * @param $uid long user id
     * @param $tid long 贴子id
     * @param $action_type int 1:发帖, 2:点赞, 3:分享
     *
     * @return true/false
     */
    //function add($uid, $tid, $sname, $owneruid) {
    function add($data) {
        $result = $this->db->insert($this->table_name, $data);
        //echo $this->db->insert_string($this->table_name, $data);
        if (false === $result 
            || 0 == $this->db->affected_rows()) {
            log_message('error', 'zan_add:'.$this->db->affected_rows().' sql:'.$this->db->insert_string($this->table_name, $data));
            return false;
        }
        return $this->db->affected_rows();    
    }


    /**
     * cancel favourite
     * @param $uid long user id
     * @param $tid long 贴子id
     * @param $action_type int 操作类型
     * @return true/false
     */
    function remove($uid, $tid, $action_type) {
        $data = array(
            'uid' => $uid,
            'tid' => $tid,
            'action_type' => $action_type,
        );
        $result = $this->db->delete($this->table_name, $data);
        if (false !== $result) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }

    /**
     * judge a list weibo if favourite by one user
     * @param $uid user id
     * @param $tid_list 
     * @return dict key:weibo_id, value:true/false
     */
    function get_tid_dianzan_dict($uid, $tid_list) {
        $this->db->select('tid');
        $this->db->where('uid', $uid);
        $this->db->where('action_type', 2);
        $this->db->where_in('tid', $tid_list);
        $result = $this->db->get($this->table_name);
        log_message('error', 'get_tid_dianzan_dict:'.$this->db->last_query());

        if((false === $result) || (0 == $result->num_rows)) {
            return false;
        }
        $dict = array();
        foreach($result->result_array() as $value) {
            $id = $value['tid'];
            $dict[$id] = true;
        }

        foreach ($tid_list as $id) {
            if (!isset($dict[$id])) {
                $dict[$id] = false;
            }
        }

        return $dict;
    }

    /**
     *get total count by tid
     *@param $tid long 
     *@return favorite count of tid
     */
    function get_count_by_tid($tid, $action_type) {
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);
        $this->db->where('action_type', $action_type);

        return $this->db->count_all_results();
    }


    /**
     * 获取用户作品被赞次数
     */
    function get_count_by_owneruid($uid, $action_type=2) {
        $this->db->select('uid');
        $this->db->from($this->table_name);
        $this->db->where('owner_id', $uid);
        $this->db->where('action_type', $action_type);

        return $this->db->count_all_results();
    }

    /**
     * get favourite user list by tid
     * @param $tid long
     *
     * @return usename list
     */
    function get_user_list($tid, $action_type, $limit, $offset) {
        $this->db->select('uid');
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);
        $this->db->order_by('ctime', 'asc');
        $this->db->limit($limit, $offset);
        
        $result = $this->db->get();
        if (false === $result ) {
            log_message('error', 'result:'.$result.' get_user_list:'.$this->db->last_query());
            return array();
        }
        if (0 == $result->num_rows) {
            log_message('error', 'get_user_list:'.$this->db->last_query());
            return array();
        }
        return $result->result_array();
    }
    
    function get_tweet_list($uid, $action_type=2, $limit, $offset) {
    	$this->db->select('tid, count(*) as user_num');
    	$this->db->from($this->table_name);
    	$this->db->where('action_type', $action_type);
    	$this->db->where('owner_id', $uid);
    	$this->db->group_by('tid');
    	$this->db->limit($limit, $offset);
    
    	$result = $this->db->get();
    	if (false === $result ) {
    		log_message('error', 'result:'.$result.' get_tweet_list:'.$this->db->last_query());
    		return array();
    	}
    	if (0 == $result->num_rows) {
    		log_message('error', 'get_tweet_list:'.$this->db->last_query());
    		return array();
    	}
    	return $result->result_array();
    }
    
    function get_new_user($tid, $limit=1, $offset=0) {
    	$this->db->select('uid,ctime');
    	$this->db->from($this->table_name);
    	$this->db->where('action_type', 2);
    	$this->db->where('tid', $tid);
    	$this->db->order_by('ctime', 'desc');
    	$this->db->limit($limit, $offset);
    
    	$result = $this->db->get();
    	if (false === $result ) {
    		log_message('error', 'result:'.$result.' get_new_user:'.$this->db->last_query());
    		return array();
    	}
    	if (0 == $result->num_rows) {
    		log_message('error', 'get_new_user:'.$this->db->last_query());
    		return array();
    	}
    	return $result->result_array();
    }
    
    /**
     * 获取用户帖子ID和点赞数列表
     *
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @return array 帖子ID列表
     */
    function get_tweet_list_by_uid($uid, $action_type=2, $limit) {
    	$this->db->select('tid, count(*) as user_num');
    	$this->db->from($this->table_name);
    	$this->db->where('action_type', $action_type);
    	$this->db->where('owner_id', $uid);
    	$this->db->group_by('tid');
    	$this->db->order_by('tid', 'desc');
    	$this->db->limit($limit);
    	
    	$result = $this->db->get();
    	if (false === $result ) {
    		log_message('error', 'result:'.$result.' get_tweet_list_by_uid:'.$this->db->last_query());
    		return array();
    	}
    	if (0 == $result->num_rows) {
    		log_message('error', 'get_tweet_list_by_uid:'.$this->db->last_query());
    		return array();
    	}
    	return $result->result_array();
    }
    
    /**
     * 获取更多用户帖子ID和点赞数列表
     *
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @return array 帖子ID列表
     */
    function get_next_tweet_list_by_uid($uid, $action_type=2, $tid, $limit) {
    	$this->db->select('tid, count(*) as user_num');
    	$this->db->from($this->table_name);
    	$this->db->where('action_type', $action_type);
    	$this->db->where('owner_id', $uid);
    	$this->db->where('tid <', $tid);
    	$this->db->group_by('tid');
    	$this->db->order_by('tid', 'desc');
    	$this->db->limit($limit);
    	
    	$result = $this->db->get();
    	if (false === $result ) {
    		log_message('error', 'result:'.$result.' get_next_tweet_list_by_uid:'.$this->db->last_query());
    		return array();
    	}
    	if (0 == $result->num_rows) {
    		log_message('error', 'get_next_tweet_list_by_uid:'.$this->db->last_query());
    		return array();
    	}
    	return $result->result_array();
    }

    function get_user_by_tid($tid, $action_type=2) {
        $this->db->select('uid');
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);
        $this->db->where('action_type', $action_type);
        $this->db->order_by('ctime', 'asc');
        
        $result = $this->db->get();
        if (false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }

    function get_list_by_tid_uid($tid, $uid) {
        $this->db->select('uid');
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);
        $this->db->where('uid', $uid);
        
        $result = $this->db->get();
        if (false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }
    /**
     * get praised tweet list
     * 
     * @param uid
     * @return tweet list
     */
    function get_praised_list_by_uid($uid, $limit, $offset) {
        $sql = "SELECT count(`tid`) as 'praisednum',`tid` FROM `ci_zan` where `owneruid` = ".$uid." group by tid order by praisednum desc limit ".$offset.", ".$limit;
        $result = $this->db->query($sql);

        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array(); 
    }
 }



/* End of file zan_model.php */
/* Location: ./application/models/zan_model.php*/
