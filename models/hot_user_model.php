<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

class Hot_user_model extends CI_Model {

    private $_redis;

	function __construct() {
		parent::__construct();
        $this->_redis = RedisProxy::get_instance('db_redis');
	}

    function get_hot_user() {
        if (!$this->_redis) {
            return array(); 
        }
        $ret = $this->_redis->smembers(K_HOT_USER);
        if (!$ret) {
            return array(); 
        }
        return $ret;
    }


}
