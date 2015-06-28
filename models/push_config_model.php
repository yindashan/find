<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';
class Push_config_model extends CI_Model {
    private $table_name = 'ci_config_push';
    private $_redis;
    private $CONFIG_PREFIX = 'myb::push::config::'; //后面接uid

    function __construct() {
        parent::__construct();
        $this->_redis = RedisProxy::get_instance('cache_redis');
    }

    function update($uid, $config) {
        $sql = "INSERT INTO ".$this->table_name." (`uid`, `config`) values (?,?) ON DUPLICATE KEY UPDATE `config`=?";
        try {
            $this->db->query($sql, array($uid, $config, $config));
        } catch (Exception $e) {
            return false;
        }
        //echo $this->db->last_query();exit;
        $key = $this->CONFIG_PREFIX.$uid;
        $this->_redis->set($key, $config);

        return true;
    }

    function get($uid) {
        $key = $this->CONFIG_PREFIX.$uid;
        $ret = $this->_redis->get($key);
        log_message('info', 'get config from redis '.$ret);
        if ($ret !== false) {
            return $ret;
        }
        $this->db->select('config');
        $this->db->from($this->table_name);
        $this->db->where('uid', $uid);

        $query = $this->db->get();

        $ret_arr = $query->result_array();
        return $ret_arr[0]['config'];
    }
}

      
