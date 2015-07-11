<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class System_message_model extends CI_Model {
    private $table_name = 'ci_system_message';

    function __construct() {
        parent::__construct();
    }

    function get_system_msg($uid, $last_id, $type, $limit=10) {
        $this->db->select('*');
        $this->db->from($this->table_name);
        $this->db->where('to_uid', $uid);
        
        if ($type != 'new')
            $this->db->where('sys_message_id <', $last_id);
         
        $this->db->where('is_del', 0);
        $action_type_where = "(action_type=5 OR action_type=10 OR action_type=11)";
        $this->db->where($action_type_where);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit, 0);
        $result = $this->db->get();
        //echo $this->db->last_query();exit;
        return $result->result_array();
    }

    function get_earliest_msg_id($uid) {
        $this->db->select('sys_message_id');
        $this->db->from($this->table_name);
        $this->db->where('to_uid', $uid);
        $this->db->where('is_del', 0);
        $this->db->limit(1, 0);
        $result = $this->db->get();
        return $result->result_array();
    }
}
