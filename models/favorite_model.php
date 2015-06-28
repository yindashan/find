<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Favorite_model extends CI_Model {
    private $table_name = 'ci_favorite';

	function __construct() {
		parent::__construct();
	}

    /**
     * add favorite
     * @param $uid long user id
     * @param $tid long tweet id
     *
     * @return true/false
     */
    function add($data) {
        $result = $this->db->insert($this->table_name, $data);
        if((false === $result) 
            || $this->db->affected_rows() == 0) {
            return false;
        }else if($this->db->affected_rows() > 0) {
            $fid = $this->db->insert_id();
            return $fid;
        }
        return false;
    }


    /**
     * cancel favorite
     * @param $uid long user id
     * @param $tid long tweet id
     *
     * @return true/false
     */
    function remove($uid, $tid) {
        $data = array(
            'uid' => $uid,
            'tid' => $tid,
        );
        $result = $this->db->delete($this->table_name, $data);
        if(false === $result) {
            return false;
        }
        return $this->db->affected_rows();
    }

    /**
     * get favorite by uid tid
     * @param $uid int
     * @param $tid int
     *
     * @return tweet list
     */
    function get_favorite_by_uid_tid($uid, $tid) {
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->where('tid', $tid);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit(1);
        
        $result = $this->db->get($this->table_name);
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }

    /**
     * get favorite list by uid
     * @param $uid long
     *
     * @return tweet list
     */
    function get_favorite_list_by_uid($uid, $limit) {
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);
        
        $result = $this->db->get($this->table_name);
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }


    /**
     * get next favorite list by uid
     * @param $uid 
     * @param $fid
     *
     * @return tweet list
     */
    function get_next_favorite_list_by_uid($uid, $fid, $limit) {
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->where('fid <', $fid);
        $this->db->order_by('ctime', 'desc');
        $this->db->limit($limit);

        $result = $this->db->get($this->table_name);
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->result_array();
    }
}



/* End of file favorite_model.php */
/* Location: ./application/models/favorite_model.php*/
