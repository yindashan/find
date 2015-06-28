<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class UserDetail extends MY_Controller {

    function __construct() {
        parent::__construct();

        $this->load->model('user_detail_model');
    }

    function _check_sname($sname) {
        $ret = $this->user_detail_model->get_info_by_sname($sname);
        if (false === $ret) {
            return fales;
        }
        if (NULL !== $ret) {
            $this->renderJson(USER_SNAME_EXIST);
            return 1;
        }
        
        return 0;
    }

}
