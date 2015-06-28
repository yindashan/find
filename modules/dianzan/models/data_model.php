<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dianzan_data_model extends CI_Model {
    private $table_name = 'lj_dianzan';

	function __construct() {
		parent::__construct();
	}

    /**add favourite
     * @param $uid long user id
     * @param $tid long 贴子id
     *
     * @return true/false
     */
    function add($uid, $tid, $username) {
        $data = array(
            'uid' => $uid,
            'tid' => $tid,
            'username' => $username,
        );

        $res = true;
        try {
            $temp = $this->db->insert($this->table_name, $data); 
        } catch (PDOException $e) {
            $res = false;
        }

        return $res;
    }

    /**
     * cancel favourite
     * @param $uid long user id
     * @param $tid long 贴子id
     *
     * @return true/false
     */
    function remove($uid, $tid) {
        $data = array(
            'uid' => $uid,
            'tid' => $tid,
        );

        $res = true;
        try {
            $res = $this->db->delete($this->table_name, $data);
        } catch (Exception $e) {
            $res = false;
        }

        return $res;
    }

    /**
     * judge a list weibo if favourite by one user
     * @param $uid user id
     * @param $tid_list 
     * @return dict key:weibo_id, value:true/false
     */
    function get_tid_dianzan_dict($uid, $tid_list) {
        $this->db->select('tid');
        $this->db->from($this->table_name);
        $this->db->where('uid', $uid);
        $this->db->where_in('tid', $tid_list);
        $query = $this->db->get();
        $dict = array();
        foreach($query->result_array() as $value) {
            $id = $value['tid'];
            $dict[$id] = true;
        }

        foreach ($tid_list as $id) {
            if (!isset($dict[$id])) {
                $dict[$id] = false;
            }
        }

        return $dict;
    }

    /**
     *get total count by tid
     *@param $tid long 
     *@return favorite count of tid
     */
    function get_count_by_tid($tid) {
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);

        return $this->db->count_all_results();
    }

    /**
     * get favourite user list by tid
     * @param $tid long
     *
     * @return usename list
     */
    function get_user_list($tid) {
        $this->db->select('username');
        $this->db->from($this->table_name);
        $this->db->where('tid', $tid);

        $query = $this->db->get();
        $username_list = array();
        foreach($query->result() as $row) {
            array_push($username_list, $row->username);
        }

        return $username_list;
    }
}
