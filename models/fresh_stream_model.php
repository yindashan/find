<?php

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Fresh_stream_model extends CI_Model{

    const FRESH_STREAM_MAX_LEN = 1000;

    private $_list = null;

    function __construct() {
        parent::__construct();
    }

    private function _load_list() {
        if (!$this->_list) {
            $redis = RedisProxy::get_instance('cache_redis');
            if (!$redis) {
                log_message('error', 'fresh_stream[_load_list]: connect redis error'); 
                return false;
            }
            $this->_list = $redis->lrange(K_NEW_STREAM, 0, -1);
            if (false === $this->_list) {
                log_message('error', 'fresh_stream[_load_list]: lrange error'); 
                return false; 
            }
        }
        return true;
    }

    function push($tid) {
        $redis = RedisProxy::get_instance('cache_redis'); 
        if (!$redis) {
            log_message('error', 'fresh_stream[add]: connect redis error, tid['.$tid.']'); 
            return;
        }
        $ret = $redis->lpush(K_NEW_STREAM, $tid);
        if (false === $ret) {
            log_message('error', 'fresh_stream[add]: add error, tid['.$tid.']'); 
        }
        if (self::FRESH_STREAM_MAX_LEN < $ret) {
            if (10 == rand(1, 10)) {
                $ret = $redis->ltrim(K_NEW_STREAM, 0, self::FRESH_STREAM_MAX_LEN); 
                if (!$ret) {
                    log_message('error', 'fresh_stream[add]: trim error, tid['.$tid.']'); 
                }
            } 
        }
    } 

    function get_by_page($pn = 0, $rn = 20) {
        if ($this->_list) {
            return array_slice($this->_list, $pn * $rn , $rn);
        } else {
            $redis = RedisProxy::get_instance('cache_redis');
            if (!$redis) {
                log_message('error', 'fresh_stream[get_by_page]: connect redis error');
                return false; 
            }
            $ret = $redis->lrange(K_NEW_STREAM, $pn * $rn , ($pn + 1) * $rn - 1);
            if (false === $ret) {
                log_message('error', 'fresh_stream[get_by_page]: lrange error'); 
                return false;
            }
            return $ret;
        }
    }

    function index($tid) {
        if (!$this->_load_list()) {
            return false; 
        }
        $ret = array_search($tid, $this->_list);
        if (false === $ret) {
            return -1; 
        } else {
            return $ret;  
        }
    }

    function get_newer($idx, $rn = 20) {
        if (!$this->_load_list()) {
            return false; 
        }
        if ($rn > $idx) {
            return array_slice($this->_list, 0, $idx); 
        } else {
            return array_slice($this->_list, $idx - $rn, $rn);
        }
    }

    function get_older($idx, $rn = 20) {
        log_message('error', '------------');
        if (!$this->_load_list()) {
            return false; 
        } 
            log_message('error', array_slice($this->_list, $idx + 1, $rn));
        return array_slice($this->_list, $idx + 1, $rn);
    }

}
