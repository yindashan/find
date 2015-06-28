<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Push_user_model extends CI_Model {
    private $table_name = 'ci_user_push';

    function __construct() {
        parent::__construct();
    }

    function update($xg_device_token, $uid, $device_type, $ios_device_token=-1) {
        $sql = "INSERT INTO ".$this->table_name." (`xg_device_token`, `ios_device_token`, `uid`, `device_type`) values (?,?,?,?) ON DUPLICATE KEY UPDATE `uid`=?";
        $result = $this->db->query($sql, array($xg_device_token, $ios_device_token, $uid, $device_type, $uid));
        if ($result->num_rows >= 0) { //TODO 这里应该是数据库异常捕获
            return $result;
         }

        return false;
    }
}

      
