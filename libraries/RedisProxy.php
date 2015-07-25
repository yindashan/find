<?php

class RedisProxy {

    private static $redis_instance = array();

    private $_redis;
    private $_host;
    private $_port;

    private function __construct() {}

    private function init($conf) {
        if (!isset($conf['host']) || !isset($conf['port'])) {
            return false; 
        }
        $this->_host = $conf['host'];
        $this->_port = intval($conf['port']);
        $this->_redis = new Redis();
        return true;
    }

    private function connect() {
        return $this->_redis->pconnect($this->_host, $this->_port); 
    }

    public static function get_instance($name) {
        $CI =& get_instance();
        $instance = NULL;
        if (!isset(self::$redis_instance[$name])) {
            $CI->config->load('redis', true);
            $conf = $CI->config->item($name, 'redis');
            if (false === $conf) {
                log_message('error', 'load redis conf error: redis['.$name.']');
                return false; 
            }
            $instance = new RedisProxy(); 
            if (false === $instance->init($conf)) {
                log_message('error', 'init redis proxy error: redis['.$name.']');
                return false;
            }
            self::$redis_instance[$name] = $instance;
        } else {
            $instance = self::$redis_instance[$name];
        }
        if (false === $instance->connect()) {
            log_message('error', 'connect redis error'); 
            return false;
        }
        return $instance; 
    }

    public function get($key) {
        try {
            if (is_array($key)) {
                $ret = $this->_redis->mGet($key);
            } else {
                $ret = $this->_redis->get($key); 
                if (false === $ret) {
                    $ret = null; 
                }
            }
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error get: ['.$err.']');
                return false;
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception get: ['.$e->getMessage().']');
            return false;
        }
    } 

    public function set($key, $value, $expire = null) {
        try {
            if (is_null($expire)) {
                $ret = $this->_redis->set($key, $value);
            } else {
                $ret = $this->_redis->setex($key, $expire, $value); 
            }
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error set: ['.$err.']');
                return false;
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception set: ['.$e->getMessage().']');
            return false;
        }
    }

    public function lrange($key, $start, $stop) {
        try {
            $ret = $this->_redis->lRange($key, $start, $stop); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error lrange: ['.$err.']');
                return false;
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception lrange: ['.$e->getMessage().']');
            return false;
        }
    }

    public function lpush($key, $value) {
        try {
            $ret = $this->_redis->lPush($key, $value); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error lpush: ['.$err.']');
                return false;
            }
            return $ret; 
        } catch (Exception $e) {
            log_message('error', 'redis exception lpush: ['.$e->getMessage().']');
            return false;
        }
    }

    public function rpush($key, $value, $force=true) {
        try {
            if ($force) {
                $ret = $this->_redis->rPush($key, $value);
            } else {
                $ret = $this->_redis->rPushx($key, $value);
            }
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error rpush: ['.$err.']');
                return false;
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception rpush: ['.$e->getMessage().']');
            return false;
        }
    }

    public function lrem($key, $count, $value) {
        try {
            $ret = $this->_redis->lRem($key, $value, $count); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error lrem: ['.$err.']');
            }
            return $ret; 
        } catch (Exception $e) {
            log_message('error', 'redis exception lrem: ['.$e->getMessage().']');
            return false;
        }
    }

    public function ltrim($key, $start, $stop) {
        try {
            $ret = $this->_redis->lTrim($key, $start, $stop); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error ltrim: ['.$err.']');
            }
            return $ret; 
        } catch (Exception $e) {
            log_message('error', 'redis exception ltrim: ['.$e->getMessage().']');
            return false;
        }
    }

    public function expire($key, $seconds) {
        try {
            $ret = $this->_redis->expire($key, $seconds); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error expire: ['.$err.']');
            }
            return $ret; 
        } catch (Exception $e) {
            log_message('error', 'redis exception expire: ['.$e->getMessage().']');
            return false;
        } 
    }

    public function hget($key, $field) {
        try {
            if (is_array($field)) {
                $ret = $this->_redis->hmGet($key, $field);
                $is_ret_ok = false;
                foreach ($ret as $k => $v) {
                    if (false !== $v) {
                        $is_ret_ok = true;
                        break; 
                    } 
                }
                if (!$is_ret_ok) {
                    log_message('error', 'redis hmget error'); 
                    return false;
                }
            } else {
                $ret = $this->_redis->hGet($key, $field); 
                if (false === $ret) {
                    $ret = null;
                }
            }
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error hget: ['.$err.']');
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception hget: ['.$e->getMessage().']');
            return false;
        }
    } 

    public function hset($key, $data_arr) {
        try {
            $ret = $this->_redis->hmSet($key, $data_arr);
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error hset: ['.$err.']');
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception hset: ['.$e->getMessage().']');
            return false;
        }
    }

    public function hgetall($key) {
        try {
            $ret = $this->_redis->hGetAll($key);
            if (empty($ret)) {
                $ret = NULL;
            }
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error hgetall: ['.$err.']');
                return false;
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception hgetall: ['.$e->getMessage().']');
            return false;
        }
    }

    public function hincrby($key, $field, $num) {
        try {
            $ret = $this->_redis->hIncrBy($key, $field, $num);
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error hincrby: ['.$err.']');
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception hincrby: ['.$e->getMessage().']');
            return false;
        }
    }

    public function zadd($key, $score, $member) {
        try {
            $ret = $this->_redis->zAdd($key, $score, $member); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error zadd:['.$err.']'); 
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception zadd: ['.$e->getMessage().']');
            return false;
        } 
    }

    public function zrange($key, $start, $stop, $withscore=false, $asc=true) {
        try {
            if ($asc) {
                $ret = $this->_redis->zRange($key, $start, $stop, $withscore); 
            } else {
                $ret = $this->_redis->zRevRange($key, $start, $stop, $withscore); 
            }
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error zrange:['.$err.']'); 
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception zrange: ['.$e->getMessage().']');
            return false;
        } 
    }

    public function zremrangebyrank($key, $start, $end) {
        try {
            $ret = $this->_redis->zRemRangeByRank($key, $start, $end); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error zremrangebyrank:['.$err.']'); 
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception zremrangebyrank: ['.$e->getMessage().']');
            return false;
        } 
    }
    public function zrevrange($key, $start, $stop, $withscores = false) {
        try {
            $ret = $this->_redis->zRevRange($key, $start, $stop, $withscores);
            $err = $this->_redis->getLastError();
            if (false === $ret) {
                log_message('error', __METHOD__.':'.__LINE__.'redis error zrevrange:['.$err.']');
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', __METHOD__.':'.__LINE__
                .' redis exception zrevrange: ['.$e.getMessage().']');
        }
        return false;
    }

    public function zrem($key, $member) {
        try {
            $ret = $this->_redis->zRem($key, $member); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error zrem:['.$err.']'); 
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception zrem: ['.$e->getMessage().']');
            return false;
        } 
    }
    public function smembers($key) {
        try {
            $ret = $this->_redis->sMembers($key); 
            $err = $this->_redis->getLastError();
            if (false === $err) {
                log_message('error', 'redis error smembers:['.$err.']'); 
            }
            return $ret;
        } catch (Exception $e) {
            log_message('error', 'redis exception smembers: ['.$e->getMessage().']');
            return false;
        } 
    }

    public function zcard($key) {
        try {
            return $this->_redis->zcard($key);
        } catch (Exception $e) {
            log_message('error', 'redis exception zcard: ['.$e->getMessage().']');
            return false;
        }
        return false;
    }

    public function zscore($key, $member) {
        try {
            $ret = $this->_redis->zscore($key, $member);
        } catch (Exception $e) {
            log_message('error', 'redis exception zscore:['.$e->getMessage().']');
            return false;
        }

        return $ret;
    }

}
