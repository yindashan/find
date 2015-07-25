<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Tweet_model extends CI_Model {

    private $table_name = 'ci_tweet';
    private $_redis;

	function __construct()
	{
		parent::__construct();
        $this->_redis = RedisProxy::get_instance('cache_redis');
	}

    /**
     * 发表帖子
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function add($request) {

        if (!$this->_redis) {
            log_message('error', __METHOD__.':'.__LINE__.' add tweet error, redis is null.');
            return false;
        }


        $redis_fields = $request;
        $this->load->model('Cache_model');
        $user_detail_info = $this->Cache_model->get_user_detail_info($redis_fields['uid'], '*', 0);
        if (!$user_detail_info) {
            log_message('error', __METHOD__.':'.__LINE__.' add tweet, get user.'.$redis_fields['uid'].' error.');
            return false;
        }

        $redis_fields['avatar'] = $user_detail_info['avatar'];
        $redis_fields['sname'] = $user_detail_info['sname'];
        $redis_fields['ukind'] = $user_detail_info['ukind'];

        $redis_fields['zan_num'] = 0;
        $redis_fields['comment_num'] = 0;

        //save tweet
        if(isset($redis_fields['resource'])) {
            $resource = $redis_fields['resource']; 
            unset($redis_fields['resource']);
        }

        $ret = $this->_redis->hset(TWEET_PREFIX.$redis_fields['tid'], $redis_fields);

        log_message('error', 'wal_ice: '.TWEET_PREFIX.$redis_fields['tid']);
        if (false === $ret) {
            log_message('error', __METHOD__.':'.__LINE__.' hset tweet error tid['.$redis_fields['tid'].']');
            return false;
        }

        /*
        if(!isset($resource) || empty($resource)) {
            return true;
        }
        //save resource
        $rids = array();
        foreach($resource as $res) {
            $rid = $res['rid'];
            $ret_res = $this->_redis->hset(TWEET_RESOURCE_PREFIX.$rid, $res);
            if (false === $ret_res) {
                log_message('error', __METHOD__.':'.__LINE__.' hset resource error. rid['.$rid.']');
                return false;
            }
            $rids[] = $rid;

            //save mapping
            $ret_mapping = $this->_redis->hset(TWEET_MAPPING.$redis_fields['tid'], $rids);
            if (false === $ret_mapping) {
                log_message('error', __METHOD__.':'.__LINE__.' hset mapping error. tid['.$redis_fields['tid'].'] rid['.$rid.']');
                return false;
            }
        }
         */

/*
        // trick for tid
        $result = $this->db->insert($this->table_name, $request);
        log_message('error', 'wal_ice: '.$this->db->last_query());
    //    echo $this->db->last_query();
        return $this->db->affected_rows();*/
        return true;
    }

    /**
     * 获取帖子列表
     *
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    /*
    function get_list($limit, $offset, $condition = array()) {

        if(!empty($condition)) {
            foreach($condition as $key => $value) {
                
                $this->db->where($key, $value);
            }
        }
        $this->db->order_by('ctime', 'desc');

        $result = $this->db->get($this->table_name, $limit, $offset);
        if($result->num_rows > 0) {
            return $result->result_array();
        }
    }
     */

    /**
     * 根据帖子id获取帖子列表，上拉刷新
     *
     * @param string tid 帖子id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    /*
    function get_list_by_tid($tid, $limit, $offset, $condition = array()) {

        $this->db->select('*');
        $this->db->where('tid >', $tid);
        if(!empty($condition)) {
            foreach($condition as $key => $value) {
                
                $this->db->where($key, $value);
            }
        }
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        if($result->num_rows > 0) { 
            return $result->result_array();
        }
    }
     */

    /**
     * 获取用户帖子列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    /*
    function get_list_by_uid($uid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        if($result->num_rows > 0) {
            return $result->result_array();
        }
    }
     */

    /**
     * 获取用户帖子ID列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @return array 帖子ID列表
     */
    function get_tid_list_by_uid($uid, $limit) {
    
        $this->db->select('tid');
        $this->db->where('uid', $uid);
        $this->db->where('is_del', 0);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }

    /**
     * 获取更多用户帖子ID列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @return array 帖子ID列表
     */
    function get_next_tid_list_by_uid($uid, $tid, $limit) {
    
        $this->db->select('tid');
        $this->db->where('uid', $uid);
        $this->db->where('tid <', $tid);
        $this->db->where('is_del', 0);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name); 
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }
    
    
    /**
     * 获取用户获得成就的帖子ID列表
     *
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @return array 帖子ID列表
     */
    function get_achieve_tid_list_by_uid($uid, $limit) {
    
    	$this->db->select('tid');
    	$this->db->where('uid', $uid);
    	$this->db->where('is_del', 0);
    	$achieve_type_array = array(1, 2, 3);
    	$this->db->where_in('achievement_type', $achieve_type_array);
    	$this->db->order_by('ctime', 'desc');
    	$this->db->limit($limit);
    
    	$result = $this->db->get($this->table_name);
    	if(false === $result) {
    		return false;
    	}else if(0 == $result->num_rows) {
    		return null;
    	}
    	return $result->result_array();
    }
    
    
    /**
     * 获取更多用户获得成就帖子ID列表
     *
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @return array 帖子ID列表
     */
    function get_next_achieve_tid_list_by_uid($uid, $tid, $limit) {
    
    	$this->db->select('tid');
    	$this->db->where('uid', $uid);
    	$this->db->where('tid <', $tid);
    	$this->db->where('is_del', 0);
    	$achieve_type_array = array(1, 2, 3);
    	$this->db->where_in('achievement_type', $achieve_type_array);
    	$this->db->order_by('ctime', 'desc');
    	$this->db->limit($limit);
    
    	$result = $this->db->get($this->table_name);
    	if(false === $result) {
    		return false;
    	}else if(0 == $result->num_rows) {
    		return null;
    	}
    	return $result->result_array();
    }

    function get_tweet($tid, $fields = '*') {
        $this->db->select($fields); 
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);
        $this->db->limit(1);
        $result = $this->db->get();
        log_message('error', $this->db->last_query());
        if (false === $result) {
            log_message('error', 'get_tweet error: msg['.$this->db->_error_message().']'); 
            return false; 
        } else if (0 == $result->num_rows) {
            return null; 
        } else {
            return $result->result_array()[0]; 
        }
    }

    /**
     * 根据帖子id获取帖子详情
     *
     * @param int tid 帖子id
     * @return array 帖子详情
         */
    /*
        function get_detail_by_tid($tid) {

            $this->db->select('*');
            $this->db->from($this->table_name);
            $this->db->where('tid', $tid);

            $result = $this->db->get();
            if($result->num_rows > 0) {
            return $result->row_array();
        }
        }
     */

    /**
     * 根据帖子id删除帖子
     *
     * @param string tid 帖子id
     * @return bool 状态
     */
    /*
    function remove_by_tid($tid) {
    
        $this->db->where('tid', $tid);
        $result = $this->db->delete($this->table_name); 
        return $result;
        if($result->num_rows > 0) {
            return $result;
        }
    }
     */

    /**
     * 根据用户id删除帖子
     *
     * @param string 用户id
     * @return bool 状态
     */
    /*
    function remove_by_uid($uid) {
    
        $this->db->where('uid', $uid);
        $result = $this->db->delete($this->table_name); 
        if($result->num_rows > 0) {
            return $result;
        }
    }
     */

    /**
     * 更新帖子
     *
     * @param array 请求参数
     * @return bool 状态
     */
    //function update_by_tid($tid, $uid, $data) {
    /*
    function update_by_tid($tid, $data) {

        $this->db->where('tid', $tid);
        //$this->db->where('uid', $uid);
        $result = $this->db->update($this->table_name, $data); 
        log_message('error', 'update_result:'.var_export($result, true));
        if($this->db->affected_rows() > 0) {
            return $result;
        }
        return true;
    }
     */

    /**
     * 更新帖子
     *
     * @param string tid 
     * @param int uid
     * @param array data
     * @return bool 状态
     */
    function update_by_tid_uid($tid, $uid, $data) {

        $this->db->where('tid', $tid);
        $this->db->where('uid', $uid);
        $result = $this->db->update($this->table_name, $data); 
        if((false === $result) 
            || (0 == $this->db->affected_rows())) {
            return false;
        }
        return $this->db->affected_rows();
    }

    /**
     * 获取转发次数
     *
     * @param string tid
     *
     */
    function get_forward_num($tid) {
        $this->db->from($this->table_name); 
        $this->db->where('origin_tid', $tid);
        return $this->db->count_all_results();
    }

    /**
     * 获取用户作品数
     *
     * @param int uid
     */
    function get_tweet_num($uid) {
        $this->db->where('uid', $uid); 
        $this->db->where('is_del', 0);
        $this->db->from($this->table_name);
        return $this->db->count_all_results();
    }

    /**
     * 获取用户成就数
     *
     * @param int uid
     */
    function get_achieve_tweet_num($uid) {
        $this->db->where('uid', $uid);
        $this->db->where('is_del', 0);
        $achieve_type_array = array(1, 2, 3);
        $this->db->where_in('achievement_type', $achieve_type_array);
        $this->db->from($this->table_name);
        return $this->db->count_all_results();
    }

    /**
     * Redis操作
     */
    function get_tweet_info($tid) {
        log_message('error', 'get_tweet_info*******************');
        $redis_key = TWEET_PREFIX.$tid;

        if (false === $this->_redis) {
            goto mysql; 
        }   
        $redis_ret = $this->_redis->hgetall($redis_key); 
        log_message('error', json_encode($redis_ret));
        if (!$redis_ret || empty($redis_ret) || !isset($redis_ret['tid'])) {
            goto mysql;
        }   

        /*
        //get tweet mapping
        $redis_mapping_key = TWEET_MAPPING . $tid;
        $redis_mapping_ret = $this->_redis->hgetall($redis_mapping_key); 
        log_message('error', 'redis_mapping_ret:========'.var_export($redis_mapping_ret, true));
        if (!$redis_mapping_ret) {
            //goto mysql;
        }   
        
        if(!empty($redis_mapping_ret)) {
            foreach($redis_mapping_ret as $resource_id) {
                $redis_res_key = TWEET_RESOURCE_PREFIX . $resource_id;
                $redis_res_ret = $this->_redis->hgetall($redis_res_key); 
                if (!$redis_res_ret) {
                    //goto mysql;
                }   
        log_message('error', 'redis_res_ret:========'.var_export($redis_res_ret, true));
                
            }
        }
         */
        //goto mysql;
        return $redis_ret;

        mysql:

        //获取tweet信息
        $tweet = array();
        $ret = $this->get_tweet($tid);
        if (false ===$ret || empty($ret)) {
            return $ret; 
        }   

        $tweet = $ret;

        //get resource
        $imgs = array();
        if(!empty($ret['resource_id'])) {
            $this->load->model('Resource_model');
            $resource_id = explode(',', $ret['resource_id']);
            log_message('error', 'resource_id:'.var_export($resource_id, true));
            foreach($resource_id as $rid) {
                $ret_resource = $this->Resource_model->get_resource_by_rid($rid); 
            log_message('error', 'ret_resource:'.var_export($ret_resource, true));
                if(false === $ret_resource || empty($ret_resource)) {
                    break;
                }
                $img = $ret_resource['img'];
                $description = $ret_resource['description'];
                if(!empty($img)) {
                    $img_arr = json_decode($img, true);
        log_message('error', 'tweet_model_img_arr------------'.var_export($img_arr, true));
                    $img_arr['content'] = $description;
        log_message('error', 'tweet_model_img_arrrrrr------------'.var_export($img_arr, true));
                    $imgs[] = $img_arr;
                }
            }
        }
        log_message('error', 'tweet_model_imgs------------'.var_export($imgs, true));

        //todo 暂时优先tweet里的图片数据，如果tweet里没有数据再用resource里的img数据填充。待素材导入迁移后删除
        if(empty($tweet['img'])) {
            $tweet['img'] = json_encode($imgs);
        }
        //$tweet['img'] = json_encode($imgs);



        //获取用户信息
        $this->load->model('Cache_model');
        $user_detail_info = $this->Cache_model->get_user_detail_info($ret['uid'], '*');
        $tweet['avatar'] = isset($user_detail_info['avatar']) ? $user_detail_info['avatar'] : "";
        $tweet['sname'] = isset($user_detail_info['sname']) ? $user_detail_info['sname'] : "";
        $tweet['ukind'] = isset($user_detail_info['ukind']) ? $user_detail_info['ukind'] : 0;

        // 处理点赞
        /*
        $zan_user = $this->Cache_model->get_zan_user($tid);
        if(false === $zan_user) {
            $this->load->model('Zan_model');
            $zan_num = $this->Zan_model->get_count_by_tid($tid);
            if (false === $zan_num) {
                $zan_num = 0;
            }
        }else {
            $zan_num = count($zan_user);
        }
        $tweet['zan_num'] = $zan_num;
        $tweet['zan_user'] = $zan_user;
         */

        // 处理评论
        $this->load->model('Comment_model');
        $comment_num = $this->Comment_model->get_comment_num($tid);
        if (false === $comment_num) {
            $comment_num = 0;
        }
        $tweet['comment_num'] = $comment_num;

        if ($this->_redis) {
            $ret = $this->_redis->hset($redis_key, $tweet);
            log_message('error', 'redis ret:'.var_export($ret, true));
            if (false === $ret) {
                log_message('error', 'update tweet redis error, tid['.$tid.']');
            }
            $ret = $this->_redis->expire($redis_key, TWEET_CACHE_SECONDS);
            if (false === $ret) {
                log_message('error', 'set cache time error, tid['.$tid.']');
            }
        }
        return $tweet;
    }
    function get_tweet_fields($tid, $fields) {
        if (false === $this->_redis) {
            goto mysql;
        }
        $redis_key = TWEET_PREFIX.$tid;
        $redis_ret = $this->_redis->hget($redis_key, $fields);
            log_message('error', 'ret_hget'.json_encode($redis_ret));
        if (!$redis_ret || empty($redis_ret)) {
            goto mysql;
        }
        return $redis_ret;
        mysql:
        $tweet = array();
        $ret = $this->get_tweet($tid, $fields);
        if (false === $ret || empty($ret)) {
            return $ret;
        }
        $tweet = $ret;
        return $tweet;
    }

    function tweet_add($uid) {
        if ($this->_redis) {
            return $this->_redis->hincrby(USER_EXT_PREFIX.$uid, 'tweet_num', 1);
        }
        return false;
    }

    function tweet_cancel($uid) {
        if ($this->_redis) {
            $tweet_num = $this->_redis->hget(USER_EXT_PREFIX.$uid, 'tweet_num');
            if($tweet_num <= 0) {
                return 0;
            }
            return $this->_redis->hincrby(USER_EXT_PREFIX.$uid, 'tweet_num', -1);
        }
        return false;
    }

    function tweet_del($tid) {
        if ($this->_redis) {
            $data['is_del'] = 1;
            return $this->_redis->hset(TWEET_PREFIX.$tid, $data);
        }
        return false;
    }

}


/* End of file tweet_model.php */
/* Location: ./application/models/tweet_model.php*/
