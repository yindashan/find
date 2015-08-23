<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__).'/../libraries/RedisProxy.php';

/**
 *  用户详情信息model
 */
class User_detail_model extends CI_Model {

    private $table_name = 'ci_user_detail';
	function __construct()
	{
        parent::__construct();
        $this->_redis = RedisProxy::get_instance('db_redis');
	}

    function add($query) {
        $result = $this->db->insert($this->table_name, $query);
        if (false === $result) {
            return false;
        }
        if (0 < $this->db->affected_rows()) {
            return true;
        }
        return NULL;
    }

    function get_info_by_sname($sname, $fields = '*') {
        $this->db->select($fields);
        $this->db->where('sname', $sname);
        $this->db->limit(1);
        $result = $this->db->get($this->table_name);
        if (false === $result) {
            return false;
        }
        if (0 >= $result->num_rows) {
            return NULL;
        }
        return $result->result_array()[0];
    }

    function get_info_by_uid($uid, $fields = '*') {
        $redis_key = USER_DETAIL_PREFIX.$uid;
        $this->db->select($fields);
        $this->db->where('uid', $uid);
        $result = $this->db->get($this->table_name);
        // 获取数据库信息失败
        if (false === $result) {
            return false;
        }
        // 查询无结果
        if (0 === $result->num_rows) {
            return NULL;
        }
        $arr_res = $result->result_array()[0];

        if ($this->_redis) {
            $ret = $this->_redis->hset($redis_key, $arr_res);
            if (false === $ret) {
                log_message('error', __FILE__.':'.__LINE__.' update user_detail redis error, uid='.$uid);
                return $arr_res;
            }
        }

        return $arr_res;
    }

    function update_info_by_uid($uid, $fields) {
        $this->db->where('uid', $uid);
        $ret = $this->db->update($this->table_name, $fields);
        if (false === $ret) {
            log_message('error', __METHOD__.':'.__LINE__.' update error, uid='.strval($uid));
            return false;
        }

        if (!$this->_redis) {
            log_message('error', __METHOD__.':'.__LINE__.' redis is null.');
            return true;
        }

        $redis_key = USER_DETAIL_PREFIX.$uid;
        $ret = $this->_redis->hset($redis_key, $fields);
        if (false === $ret) {
            log_message('error', __METHOD__.':'.__LINE__.' redis hmset failed, key='.$redis_key);
        }

        return true;
    }

    function get_user_list($keyword, $avatar_type = 0) {
        $mysql_ret = $this->db
            ->query("select * from ci_user_detail where sname like '%$keyword%'")
            ->result_array();
        if (false === $mysql_ret) {
            return false;
        }

        foreach ($mysql_ret as &$user_detail_info) {
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
        }

        return $mysql_ret;
    }

}
