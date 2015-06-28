<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Version_model extends CI_Model {
    private $table_name = 'ci_version';

	function __construct() {
		parent::__construct();
	}

    function add($os, $version, $url, $is_pub, $info, $md5) {
        $data = array(
            'os' => $os,
            'version' => $version,
            'url' => $url,
            'is_pub' => $is_pub,
            'info' => $info,
            'md5' => $md5,
            'ctime' => time(),
        );
        return $this->db->insert($this->table_name, $data); 
    }

    function get_latest_version($os) {
        $this->db->select('version, url, md5, info');
        $this->db->where('is_pub', true);
        $this->db->where('os', strtolower($os));
        $this->db->from($this->table_name);
        $this->db->order_by("ctime", "desc"); 
        $this->db->limit(1);
        $query = $this->db->get();
        if (!$query) {
            return false; 
        }
        return $query->result_array();
    }

}
