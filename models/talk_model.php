<?php

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Talk_Model {

    const MSG_BRIEF_LEN = 50;

    private $_redis;

    function __construct() {
        $this->_redis = RedisProxy::get_instance('db_redis'); 
    }

    private function get_msg_brief($msg) {
        if (mb_strlen($msg, 'utf8') > self::MSG_BRIEF_LEN) {
            return mb_substr($msg, 0, self::MSG_BRIEF_LEN - 3, 'utf8').'...';
        } else {
            return $msg;
        }
    }

    private function _update_talk_list($from_uid, $to_uid, $time) {
        $key = K_TALK_LIST.$from_uid;
        $ret = $this->_redis->zadd($key, -$time, $to_uid);
        if (false === $ret) {
            return false; 
        }
        if (1 == $ret) {
            $ret = $this->_redis->zremrangebyrank($key, TALK_LIST_SIZE - 1, -1); 
            if (false === $ret) {
                return false; 
            }
        }
        return true;
    }

    function update_talk_list($from_uid, $to_uid) {
        if (!$this->_redis) {
            return false; 
        }
        $time = time();
        if (!$this->_update_talk_list($from_uid, $to_uid, $time)) {
            return false; 
        }
        if (!$this->_update_talk_list($to_uid, $from_uid, $time)) {
            return false; 
        }
        return true;
    }

    function get_talk_list($uid, $num) {
        if (!$this->_redis) {
            return false; 
        }
        $key = K_TALK_LIST.$uid;
        $ret = $this->_redis->zrange($key, 0, $num, true);
        if (false === $ret) {
            return false; 
        }
        $result_arr = array();
        foreach ($ret as $k => $v) {
            $to_uid = $k;
            $timestamp = -$v;
            $session_key = K_TALK_SESSION.$this->get_key_term($uid, $to_uid);
            $ret = $this->_redis->hget($session_key, array('brief', $uid, 'newest_mid'));
            log_message('error', 'get_talk_list_ret:'.json_encode($ret));
            if (!$ret) {
                continue; 
            }
            $arr = explode('|', $ret['brief']);
            if (3 != count($arr)) {
                continue; 
            }
            $has_new_msg = 0;
            if (isset($ret['newest_mid']) 
                &&!empty($ret['newest_mid'])
                && $ret[$uid]
                && $ret['newest_mid'] != $ret[$uid]) {
                $has_new_msg = 1;  
            }
            $result_arr[] = array(
                'other_uid' => $to_uid,
                'timestamp' => $timestamp,
                'from_uid' => $arr[0],
                'to_uid' => $arr[1],
                'brief' => $arr[2],
                'has_new_msg' => $has_new_msg,
            );
        }
        log_message('error', 'get_talk_list_result:'.json_encode($result_arr));
        return $result_arr;
    }

    function remove_talk_list($uid, $msg_uid) {
        if (!$this->_redis) {
            return false; 
        }
        $key = K_TALK_LIST.$uid;
        $ret = $this->_redis->zrem($key, $msg_uid);
        if (false === $ret) {
            return false; 
        }
        return true;
    }

    private function get_key_term($from, $to) {
        $a = intval($from); 
        $b = intval($to); 
        if ($a > $b) {
            return strval($b) . '-' . strval($a); 
        } else {
            return strval($a) . '-' . strval($b); 
        }
    }

    function add_msg($from_uid, $to_uid, $mid, $content) {
        if (!$this->_redis) {
            return false; 
        }
        $key_term = $this->get_key_term($from_uid, $to_uid);
        $queue_key = K_TALK_MSG_QUEUE.$key_term;
        $ret = $this->_redis->lpush($queue_key, $mid);
        if (false === $ret) {
            return false; 
        }
        if ($ret > TALK_MSG_QUEUE_SIZE) {
            $ret = $this->_redis->ltrim($queue_key, 0, TALK_MSG_QUEUE_SIZE - 1); 
            if (false === $ret) {
                return false; 
            }
        }
        $session_key = K_TALK_SESSION.$key_term;
        $brief = $from_uid.'|'.$to_uid.'|'.$this->get_msg_brief($content);
        $ret = $this->_redis->hset($session_key, array('brief' => $brief, 'newest_mid' => $mid));
        if (!$ret) {
            return false; 
        }
        return true;
    }

    function get_msg_list ($uid, $other_uid) {
        if (!$this->_redis) {
            return null;
        } 
        $key_term = $this->get_key_term($uid, $other_uid);
        $queue_key = K_TALK_MSG_QUEUE.$key_term;
        return $this->_redis->lrange($queue_key, 0, -1);
    }

    function update_read_mid ($uid, $other_uid, $mid) {
        if (!$this->_redis) {
            return false; 
        } 
        $key_term = $this->get_key_term($uid, $other_uid);
        $session_key = K_TALK_SESSION.$key_term;
        return $this->_redis->hset($session_key, array($uid => $mid));
    }

    function get_read_mid($uid, $other_uid) {
        if (!$this->_redis) {
            return '-1'; 
        } 
        $key_term = $this->get_key_term($uid, $other_uid);
        $session_key = K_TALK_SESSION.$key_term;
        $ret = $this->_redis->hget($session_key, $uid);
        if ($ret) {
            return $ret; 
        } else {
            return '-1'; 
        }
    }

}



/* End of file talk_model.php */ 
/* Location: ./application/models/talk_model.php */ 
