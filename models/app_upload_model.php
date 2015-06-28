<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  用户model
 */
class App_upload_model extends CI_Model {

    private $table_name = 'ci_app_upload';
	function __construct()
	{
		parent::__construct();
	}

    function add($data) {
        return $this->db->insert($this->table_name, $data);
    }

}
