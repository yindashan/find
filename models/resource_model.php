<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Resource_model extends CI_Model {
    private $table_name = 'ci_resource';

	function __construct() {
		parent::__construct();
	}

    /**
     * add resource 
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
     * get resource by rid
     * @param $rid string
     *
     * @return resource 
     */
    function get_resource_by_rid($rid) {
        $this->db->select('*');
        $this->db->where('rid', $rid);
        $this->db->limit(1);
        
        $result = $this->db->get($this->table_name);
        if(false === $result) {
            return false;
        }else if(0 == $result->num_rows) {
            return null;
        }
        return $result->row_array();
    }

}



/* End of file resource_model.php */
/* Location: ./application/models/resource_model.php*/
