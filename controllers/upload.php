<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Upload extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();
        $this->_set_token_check(false);

        $this->load->library('oss');
	}

	function user_pic() {
        $request = $this->request_array;
        if (!isset($_FILES['file'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }

        $file = $_FILES['file'];
        $ret = $this->oss->upload_user_pic($file['tmp_name'], $file['name']);
        if (!$ret) {
            $this->renderJson(ERR_OSS);
            return;
        }
        $this->renderJson(STATUS_OK, array('img'=>$ret));
	}

    function tweet_pic() {
        $request = $this->request_array;
        if (!isset($_FILES['file'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }

        $file = $_FILES['file'];
        $ret = $this->oss->upload_tweet_pic($file['tmp_name'], $file['name']);
        if (!$ret) {
            $this->renderJson(ERR_OSS);
            return;
        }
        $this->renderJson(STATUS_OK, array('img'=>$ret));
    }

    function mis_pic() {
        $request = $this->request_array; 
        if (!isset($_FILES['file'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }

        $file = $_FILES['file'];
        $ret = $this->oss->upload_mis_pic($file['tmp_name'], $file['name']);
        if (!$ret) {
            $this->renderJson(ERR_OSS);
            return;
        }
        $this->renderJson(STATUS_OK, array('img'=>$ret));
    }

    function app_stuff() {
        $request = $this->request_array;
        log_message('error', 'libo'.json_encode($request));
        log_message('error', 'libo'.json_encode($_FILES));
        if (!isset($request['type']) 
            || !isset($request['uid']) 
            || !isset($_FILES['file'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }
        $file = $_FILES['file'];
        $uid = $request['uid'];
        $type = $request['type'];
        $imei = isset($request['imei']) ? $request['imei'] : '';
        $app_version = isset($request['app_version']) ? $request['app_version'] : '';
        $os = isset($request['os']) ? $request['os'] : '';
        $mobile_info = isset($request['mobile_info']) ? $request['mobile_info'] : '';
        if ('crash' == $type) {
            $url = $this->oss->upload_crash($file['tmp_name'], $file['name']);
            $type = 1;
        } else if ('log' == $type) {
            $url = $this->oss->upload_log($file['tmp_name'], $file['name']);
            $type = 2;
        } else {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }
        if (false === $url) {
            $this->renderJson(ERR_OSS);
            return;
        }
        $info = array(
            'url' => $url,
            'type' => $type,
            'uid' => $uid,    
            'imei' => $imei,    
            'app_version' => $app_version,    
            'mobile_info' => $mobile_info,    
            'os' => $os,
            'ctime' => time(),
        );
        $this->load->model('app_upload_model');
        $ret = $this->app_upload_model->add($info);
        if (!$ret) {
            $this->renderJson(MYSQL_ERR_INSERT); 
            return;
        }
        $this->renderJson(STATUS_OK);
    }

}
