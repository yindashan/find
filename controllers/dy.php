<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');


require_once dirname(__FILE__).'/../libraries/Thrift/ClassLoader/ThriftClassLoader.php';

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Transport\TSocket;
use Thrift\Transport\TFramedTransport;
use Thrift\Protocol\TBinaryProtocol;
class dy extends MY_Controller {

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
	}

    function test() {
        try {
            $GEN_DIR = realpath(__DIR__.'/..').'/service/';
            $loader = new ThriftClassLoader();
            $loader->registerNamespace('Thrift', __DIR__ . '/../libraries');
            $loader->registerDefinition('offhub', $GEN_DIR);
            $loader->register();

            $socket = new TSocket('back1', 9027);
            $transport = new TFramedTransport($socket);
            $protocol = new TBinaryProtocol($transport);
            $client = new offhub\PostServiceClient($protocol);

            $transport->open();
            $request = new offhub\PostServiceRequest();
            $request->content = 'test';
            $request->uid = '9';
            $request->tid = '12';
            $response = $client->SendNewPost($request);
            print $response->err_no;

            $transport->close();
        } catch (TException $tx) {
              print 'TException: '.$tx->getMessage()."\n";
        }

    }

    function img() {
        $request = $this->request_array;
        $this->load->library('oss');
        $ret = $this->oss->upload_tweet_pic($_FILES['file']['tmp_name'], 
                                     $uid.'_'.$this->get_uuid().'.'.pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        print_r($ret);
    }

    function hi() {
        echo 'hi';
    }

    function xb () {
        $request = $this->request_array;
        $this->load->library('offclient'); 
        $this->offclient->send_event($request['tid'], $request['type']);
    }

    function wy () {
        $request = $this->request_array;
        $this->load->model('cache_model'); 
        echo json_encode($this->cache_model->get_user_session($request['uid'])); 
        echo json_encode($this->cache_model->get_user_session($request['uid'], array('a'))); 
    }

    function abc() {
        //$this->load->model('talk_model');
        //echo json_encode($this->talk_model->get_talk_list('15', 50));
        require_once dirname(__FILE__).'/../libraries/RedisProxy.php';
        $this->_redis = RedisProxy::get_instance('db_redis'); 
        echo json_encode($this->_redis->zrange('test_a', 0 , -1, true));
    }

}
