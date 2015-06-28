<?php
require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Message_Sum_Model extends CI_Model {

    private $_redis;
    private $private_msg_prefix = "ms:pmsg";
    private $sys_msg_key = "ms:msg";

    function __construct() {
        parent::__construct();
        $this->_redis = RedisProxy::get_instance('cache_redis');
    }

    function get_private_msg_num($uid) {
        $key = $this->private_msg_prefix.$uid;
        $from_uids = $this->_redis->zrange($key, 0, -1);
        if (empty($from_uids)) {
            return array();
        }

        for ($i = 0; $i < count($from_uids); $i++) {
            $from_uids[$i] = intval($from_uids[$i]);
        }
        return $from_uids;
    }

    function get_sys_msg_num($uid) {
        $num = $this->_redis->zscore($this->sys_msg_key, $uid);

        if (empty($num)) {
            return 0;
        }

        return $num;
    }
}
