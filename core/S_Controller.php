<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * 
 * 
 */

class S_Controller extends CI_Controller
{
    protected $request_array = array();
    protected $result_array = array();
    protected $_uid = null;
    protected $_user_info = null;
    protected $_enable_user_verify_check = false;
    protected $_enable_token_check = false;

	public function __construct()
	{
		parent::__construct();

        date_default_timezone_set('Asia/Shanghai');
        $_POST += $_GET;
        $this->request_array = $_POST;

        $this->response_array = array(
            'errno' => STATUS_OK,
            'data' => array(),    
        );
	}

    public function execute($method = 'index') {
        if ($method != 'generate') {
            $this->index($method);
        }
        else {
            $this->$method(); 
        }
    }
}

