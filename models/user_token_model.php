<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

/**
 *  用户model
 */
class User_token_model extends CI_Model {

    private $table_name = 'ci_user_token';

	function __construct()
	{
        parent::__construct();
        $this->_redis = RedisProxy::get_instance('cache_redis');
    }

    function add($query) {
        // select all token and make it invalid
        if (!isset($query['uid'])) {
            log_message('error', __METHOD__.':'.__LINE__.'add_token get uid failed.');
            return false;
        }
        if (!isset($query['hash_key'])) {
            log_message('error', __METHOD__.':'.__LINE__.' hash_key not exist.');
            return false;
        }
        $uid = $query['uid'];
        /*
        $this->db->where('uid', $uid);
        $this->db->where('is_valid', 1);
        $currect_time = time() - 5;
        $arr_update_req = array('is_valid'  => 0,
            'invalid_time'  => $currect_time);

        $result = $this->db->update($this->table_name, $arr_update_req);
        if (false === $result) {
            log_message('error', __METHOD__.' update  user_token failed, uid='.strval($uid));
            return false;
        }*/
        
        // add to mysql
        $result = $this->db->insert($this->table_name, $query);
        log_message('debug', __METHOD__.' insert query: '.$this->db->last_query());
        if (false === $result) {
            log_message('error', __METHOD__.' insert user_token failed, uid='.strval($uid));
            return false;
        }
        if (0 >= $this->db->affected_rows()) {
            return NULL;
        }

        // add to redis
        $hash_key = $query['hash_key'];
        $redis_key = USER_TOKEN_PREFIX.$hash_key;
        $ret = $this->_add_redis($redis_key, $query);
        if (false === $ret) {
            log_message('error', __METHOD__.' _add_redis error, key='.$redis_key);
        }

        return true;
    }

    function get_token_info_by_uid($uid, $fields = '*') {
        $this->db->select($fields);
        $this->db->from($this->table_name);
        $this->db->where('uid', $uid);
        $this->db->where('is_valid', 1);
        $result = $this->db->get();
        log_message('error', $this->db->last_query());
        if (false === $result) {
            log_message('error', __METHOD__.':'.__LINE__.' select mysql error, uid='.$uid);
            return false;
        }
        if (0 >= $this->db->affected_rows()) {
            return NULL;
        }

        return $result->result_array();
    }

    function get_token_info($hash_key, $fields = '*') {
        $redis_key = USER_TOKEN_PREFIX.$hash_key;
        if ($this->_redis) {
            if ('*' === $fields) {
                $result = $this->_redis->hgetall($redis_key);
            } else {
                $result = $this->_redis->hgetall($redis_key, $fields);
            }
            if (false === $result || empty($result)) {
                log_message('error', __METHOD__.' hgetall redis error, uid='.strval($uid));
            } else {
                return $result;
            }
        } else {
            log_message('error', __METHOD__.' redis is null.');
        }

        // read mysql
        $this->db->select($fields);
        $this->db->from($this->table_name);
        $this->db->where('hash_key', $hash_key);
        $this->db->where('is_valid', 1);
        $result = $this->db->get();
        if (false === $result) {
            log_message('error', __METHOD__.' select mysql error, uid='.strval($uid));
            return false;
        }
        $affect_row = $this->db->affected_rows();
        if (0 >= $affect_row) {
            log_message('error', __METHOD__.' affect_rows <= 0, uid='.strval($uid));
            return NULL;
        }
        if (1 != $affect_row) {
            log_message('error', __METHOD__.' affect_rows != 1, uid='.strval($uid));
        }

        $ret = $this->_add_redis($redis_key, $result->result_array()[0]);
        if (false === $ret) {
            log_message('error', __METHOD__.' _add_redis error, key='.$redis_key);
        }
        
        return $result->result_array()[0];
    }

    function set_token_invalid_of_redis($token_list) {
        foreach ($token_list as $token) {
            $redis_key = USER_TOKEN_PREFIX.$token;
            if (!$this->_redis) {
                return false;
            }
            log_message('error', 'wa_ice:'.$redis_key);
            $ret = $this->_redis->hset($redis_key, array('is_valid' => 0));
        }

        return true;
    }

    function set_token_invalid_by_uid($uid) {
        $this->db->where('uid', $uid);
        $ret = $this->db->update($this->table_name, array('is_valid' => 0));
        if (false === $ret) {
            return false;
        }
        return true;
    }

    protected function _add_redis($redis_key, $fields) {
        // add to redis
        if (!$this->_redis) {
            log_message('error', __METHOD__.' add user_token redis is null.');
            return false;
        }
        $result = $this->_redis->hset($redis_key, $fields);
        if (false === $result) {
            log_message('error', __METHOD__.' hset token='.$redis_key.' failed.');
            return false;
        }
        $result = $this->_redis->expire($redis_key, TOKEN_CACHE_TIMEOUT);
        if (false === $result) {
            log_message('error', __METHOD__.' token set cache_time error, token='.$redis_key);
            return false;
        }

        return true;
    }
}
