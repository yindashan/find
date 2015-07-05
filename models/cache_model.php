<?php

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Cache_Model extends CI_Model {

    private $_redis;

    function __construct() {
        parent::__construct();
        $this->_redis = RedisProxy::get_instance('cache_redis');
        $this->_db_redis = RedisProxy::get_instance('db_redis');
    }

    function get_user_info($uid, $fields) {

        if (false === $this->_redis) {
            goto mysql; 
        }
        $redis_key = USER_PREFIX.$uid;
        if ('*' === $fields) {
            $ret = $this->_redis->hgetall($redis_key); 
        } else {
            $ret = $this->_redis->hget($redis_key, $fields);    
        }
        if (false === $ret || empty($ret)) {
            goto mysql; 
        }
        return $ret;
    mysql:
        $this->load->model('user_model');    
        $this->load->model('user_detail_model');    
        if (is_array($fields)) {
            $fields_str = join(',', $fields); 
        } else {
            $fields_str = $fields;
        }
        $ret = $this->user_model->get_user_info($uid, $fields_str);
        if (false === $ret) {
            return false; 
        } else if (NULL === $ret) {
            if (is_array($fields)) {
                $res = array();
                foreach ($fields as $f) {
                    $res[$f] = null; 
                }
                return $res; 
            } else {
                return NULL; 
            } 
        } else {
            $user_more_ret = $this->user_detail_model->get_user_more_info($uid);
            if ($user_more_ret) {
                $ret = $ret + $user_more_ret; 
            }
            if ('*' === $fields || is_array($fields)) {
                return $ret;
            } else {
                return $ret[$fields]; 
            }
        }
    }

    function get_user_detail_info($uid, $fields, $avatar_type = 0) {
        $user_detail_info = false;

        // get mysql failed
        if (false === $this->_db_redis) {
            goto mysql; 
        }
        $redis_key = USER_DETAIL_PREFIX.$uid;
        if ('*' === $fields) {
            $user_detail_info = $this->_db_redis->hgetall($redis_key); 
        } else {
            $user_detail_info = $this->_db_redis->hget($redis_key, $fields);    
        }
        if (!$user_detail_info) {
            goto mysql; 
        }

        log_message('error', 'wal_ice: user_detail in redis, key='.$redis_key);
        goto user_detail_end;

    mysql:
        log_message('error', 'wal_ice: user_detail in mysql');
        $str_fields = "";

        $this->load->model('User_detail_model');
        $user_detail_info = $this->User_detail_model->get_info_by_uid($uid, $str_fields);
        if (!$user_detail_info) {
            return false;
        }
        log_message('debug', __FILE__.':'.__LINE__
            ." get_user_detail_info ".strval($se_id).' info: '.json_encode($user_detail_info));

    user_detail_end:
        log_message('debug', 'wal_ice: '.strval($avatar_type));
        if (isset($user_detail_info['avatar'])) {
            switch ($avatar_type) {
            case 0:
                $arr_img_info = json_decode($user_detail_info['avatar'], true);
                if ($arr_img_info 
                    && isset($arr_img_info['img']) 
                    && isset($arr_img_info['img']['s']) 
                    && isset($arr_img_info['img']['s']['url'])) {
                        $user_detail_info['avatar'] = $arr_img_info['img']['s']['url'];
                    } else  {
                        $user_detail_info['avatar'] = "";
                    }
                break;
            case 1:
                $arr_img_info = json_decode($user_detail_info['avatar'], true);
                if ($arr_img_info 
                    && isset($arr_img_info['img'])
                    && isset($arr_img_info['img']['n']) 
                    && isset($arr_img_info['img']['n']['url'])) {
                        $user_detail_info['avatar'] = $arr_img_info['img']['n']['url'];
                    } else {
                        $user_detail_info['avatar'] ="";
                    }
                break;
            }
        }

        return $user_detail_info;
    }

    function get_user_industry ($uid) {
        if (false === $this->_redis) {
            goto mysql; 
        } 

        $redis_key_industry = USER_INDUSTRY_PREFIX . $uid;  
        $ret = $this->_redis->zrange($redis_key_industry, 0, -1);
        if(false === $ret) {
            goto mysql;
        }
        //test
        if(is_null($ret)) {
            goto mysql;
        }
        if(empty($ret)) {
            goto mysql;
        }
        $ret_str = implode(',', $ret);
        return $ret_str;
        //return $ret;
        mysql:
            $this->load->model('User_industry_model');
        $ret = $this->User_industry_model->get_user_industry($uid);

        if (!$ret) {
            return ''; 
        }
        foreach($ret as $indus_ret) {
            $indus[] = $indus_ret['industry_id'];
        }
        $ret_str = implode(',', $indus);
        return $ret_str;

    }

    function get_user_ext_info ($uid) {
        if (false === $this->_redis) {
            goto mysql; 
        } 
        $redis_key = USER_EXT_PREFIX.$uid;
        $redis_ret = $this->_redis->hgetall($redis_key);
        if (!$redis_ret 
            || !isset($redis_ret['follower_num']) 
            || !isset($redis_ret['followee_num'])
            || !isset($redis_ret['tweet_num'])) {
                goto mysql; 
            }
        return $redis_ret;
        mysql:
            $ext_info = array();
        $this->load->model('relation_model');
        $ret = $this->relation_model->get_follower_num($uid);
        if ($ret) {
            $ext_info['follower_num'] = $ret;
        } else {
            $ext_info['follower_num'] = 0; 
        }
        $ret = $this->relation_model->get_followee_num($uid);
        if ($ret) {
            $ext_info['followee_num'] = $ret; 
        } else {
            $ext_info['followee_num'] = 0; 
        }
/*        $this->load->model('Community_model');
$ret = $this->Community_model->get_tweet_num($uid);*/
        $this->load->model('tweet_model');
        $ret = $this->tweet_model->get_tweet_num($uid);
        if ($ret) {
            $ext_info['tweet_num'] = $ret; 
        } else {
            $ext_info['tweet_num'] = 0; 
        }
        if ($this->_redis) {
            $ret = $this->_redis->hset($redis_key, $ext_info); 
            if (false === $ret) {
                log_message('error', 'update user_ext redis error, uid['.$uid.']'); 
            }
            $ret = $this->_redis->expire($redis_key, USER_EXT_CACHE_SECONDS);
            if (false === $ret) {
                log_message('error', 'set cache time error, uid['.$uid.']'); 
            }
        }
        return $ext_info;
    }

    function get_zan_list($tid) {
    
        if (!$this->_redis) {
            goto mysql;
        }
        $redis_key = K_ZAN_LIST.$tid;
        $ret = $this->_redis->lrange($redis_key, 0, -1);
        if (false === $ret || 0 == count($ret)) {
            goto mysql;
        }
        $set = array();
        $result = array();
        foreach ($ret as $uid) {
            if ('-1' == $uid) {
                continue;
            }
            if (isset($set[$uid])) {
                continue;
            }
            $result[] = $uid;
            $set[$uid] = 1;
        }
        return $result;
        mysql:
            $this->load->model('zan_model');
        $list = $this->zan_model->get_user_list($tid, 100);
        if (false === $list || empty($list)) {
            return array();
        }
        $result = array();
        foreach ($list as $row) {
            $result[] = $row['uid'];
        }
        if ($this->_redis) {
            $this->_redis->rpush($redis_key, '-1');
            foreach ($result as $uid) {
                $ret = $this->_redis->rpush($redis_key, $uid);
            }
            $this->_redis->expire($redis_key, 172800);
        }
        return $result;
    }

    function zan_add($tid, $uid) {
        if ($this->_redis) {
            return $this->_redis->rpush(K_ZAN_LIST.$tid, $uid, false);

        }
        return false;
    } 

    function zan_cancel($tid, $uid) {
        if ($this->_redis) {
            $this->_redis->lrem(K_ZAN_LIST.$tid, 0, $uid);
        }
        return false;
    }

    function zan_user_add($tid, $uid) {
        if($this->_redis) {
            return $this->_redis->lpush(ZAN_USER_PREFIX.$tid, $uid);
        }
        return false;
    }

    function zan_user_cancel($tid, $uid) {
        if($this->_redis) {
            return $this->_redis->lrem(ZAN_USER_PREFIX.$tid, 0, $uid);
        }
        return false;
    }

    function get_zan_user($tid) {
        if($this->_redis) {
            return $this->_redis->lrange(ZAN_USER_PREFIX.$tid, 0, -1);
        }
        return false;
    
    }



    function comment_add($tid) {
        if ($this->_redis) {
            return $this->_redis->hincrby(TWEET_PREFIX.$tid, 'comment_num', 1); 
        }
        return false;
    }

    function comment_cancel($tid) {
        if ($this->_redis) {
            $comment_num = $this->_redis->hincrby(TWEET_PREFIX.$tid, 'comment_num', -1);
            if($comment_num < 0) {
                return $this->_redis->hset(TWEET_PREFIX.$tid, array('comment_num' => 0)); 
            }else {
                return $comment_num;
            }
        }
        return false;
    }
    function forward_add($tid) {
        if ($this->_redis) {
            return $this->_redis->hincrby(TWEET_PREFIX.$tid, 'forward_num', 1); 
        }
        return false;
    }

    function add_follow($follower_uid, $followee_uid) {
        if ($this->_redis) {
            $this->_redis->hincrby(USER_EXT_PREFIX.$follower_uid, 'followee_num', 1); 
            $this->_redis->hincrby(USER_EXT_PREFIX.$followee_uid, 'follower_num', 1);
        }    
    }

    function cancel_follow($follower_uid, $followee_uid) {
        if ($this->_redis) {
            $follower_num = $this->_redis->hincrby(USER_EXT_PREFIX.$followee_uid, 'follower_num', -1);
            if($follower_num < 0) {
                $this->_redis->hset(USER_EXT_PREFIX.$followee_uid, array('follower_num' => 0)); 
            }
            $followee_num = $this->_redis->hincrby(USER_EXT_PREFIX.$follower_uid, 'followee_num', -1);
            if($followee_num < 0) {
                $this->_redis->hset(USER_EXT_PREFIX.$follower_uid, array('followee_num' => 0));
            }
        }    
    }

    function tweet_add($uid) {
        if ($this->_redis) {
            return $this->_redis->hincrby(USER_EXT_PREFIX.$uid, 'tweet_num', 1); 
        } 
        return false;
    }

    function tweet_cancel($uid) {
        if ($this->_redis) {
            $tweet_num = $this->_redis->hincrby(USER_EXT_PREFIX.$uid, 'tweet_num', -1);
            if($tweet_num < 0) {
                return $this->_redis->hset(USER_EXT_PREFIX.$uid, array('tweet_num' => 0)); 
            } else {
                return $tweet_num;
            }
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

    function get_online_rec($uid) {
        if ($this->_redis) {
            return $this->_redis->get(K_ONLINE_REC.$uid); 
        } 
        return false;
    }

    function set_online_rec($uid, $result) {
        if ($this->_redis) {
            return $this->_redis->set(K_ONLINE_REC.$uid, json_encode($result), 60); 
        } 
        return false;
    }
}
