<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Catalog extends MY_Controller {
    function __construct() {
        parent::__construct();
        $this->load->helper('file');
        $this->_set_token_check(false);

    }

    function get() {
        $data = array();
        try{
            $string = read_file('./dict/catalog.json');
            if (!empty($string)) {
                $data = json_decode($string, true);
                $data['errno'] = 0;
            }
            else {
                $data['errno'] = -1;
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $data['errno'] = -1;
        }
        $this->renderJson($data['errno'], $data['data']);

    }

}



/* End of file zan.php */
/* Location: ./application/controllers/catalog.php */
