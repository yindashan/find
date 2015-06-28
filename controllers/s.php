<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class S extends MY_Controller {
    public function _remap($method) {
        if ($method == 'generate') {
            $this->$method();
        }
        else {
            $this->index($method);
        }
    }

    private function checkUrl($weburl) { 
            //return !preg_match('/^http(s)*:\/\/[_a-zA-Z0-9-]+(.[_a-zA-Z0-9-]+)*$/', $weburl); 
            return preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $weburl);
    }

    function __construct() {
        parent::__construct();
        $this->execute_file = 's';

        $this->load->model('Short_url_model');
    }

    function index($shorturl) {
        $url = $this->Short_url_model->get_origin_url($shorturl);
        if (empty($url)) {
            echo 'invalid short url';
            return;
        }

        header('Location: '.$url);
        die();
    }

    function generate() {
        $request = $this->request_array;
        $url = @$request['url'];
        if (!$this->checkUrl($url)) {
            echo 'invalid url, please start with http(s)://';
            return;
        }
        echo $this->Short_url_model->generate_url($url);
    }
}
