<?php

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Hot_stream_Model extends CI_Model{

    private $_list = null;

    function __construct() {
        parent::__construct();
    }

    function get_by_page($pn = 0, $rn = 20) {
        $redis = RedisProxy::get_instance('cache_redis'); 
        if (!$redis) {
            log_message('error', 'hot_stream[get_by_page]: connect redis error'); 
            return false;
        }
        $ret = $redis->lrange(K_HOT_STREAM, $pn * $rn, ($pn + 1) * $rn - 1);
        if (false === $ret) {
            log_message('error', 'hot_stream[get_by_page]: lrange error'); 
            return false;
        }
        return $ret;
    } 

}
