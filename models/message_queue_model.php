<?php

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Message_Queue_Model {

    private $_redis;

    function __construct() {
        $this->_redis = RedisProxy::get_instance('db_redis');
    }

    function get_user_message($uid) {
        return $this->_redis->lrange(USER_MSG_POSTFIX . $uid, 0, -1);
    }

    function get_rec_message() {
        return $this->_redis->lrange(REC_MSG_QUEUE_KEY, 0, -1);
    }

    function add_rec_message($tid) {
        $ret = $this->_redis->lrem(REC_MSG_QUEUE_KEY, 1, $tid);
        if (false === $ret) {
            return false;
        }
        if ($ret >= USER_MSG_MAX_NUM) {
            $ret = $this->_redis->ltrim(REC_MSG_QUEUE_KEY, 0, USER_MSG_MAX_NUM - 1);
            if (false === $ret) {
                return false;
            }
        }
        $ret = $this->_redis->lpush(REC_MSG_QUEUE_KEY, $tid);
        if (false === $ret) {
            return false;
        }
        return true;
    }

    function cancel_rec_message($tid) {
        return $this->_redis->lrem(REC_MSG_QUEUE_KEY, 0, $tid);
    }

}
