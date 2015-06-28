<?php
/**
 *  @消息中心client for php
 *  @Author : dingchuan
 *  @Create : 2015-01-31
 */

require_once __DIR__."/Thrift/ClassLoader/ThriftClassLoader.php";

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol as TBinaryProtocol;  
use Thrift\Transport\TSocket as TSocket;  
use Thrift\Transport\TBufferedTransport as TBufferedTransport;  

class Msclient{

    private $socket = null;
    private $transport = null;
    private $protocol = null;
    private $client = null;

    function __construct() {
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Thrift', __DIR__);
        $loader->registerDefinition('ms', realpath(__DIR__.'/..').'/service/');
        $loader->register();
    }

    function __destruct() {
        //$this -> dis_connect();
    }

    /**
    * @Theme  : 
    * @Return : boolean
    */
    public function connect() {
        $this->socket = new TSocket('lj-ol-back1', 8999);
        $this->socket->setSendTimeout(10000);
        $this->socket->setRecvTimeout(20000);

        $this->transport = new TBufferedTransport($this->socket);
        $this->protocol = new TBinaryProtocol($this->transport);
        $this->client = new \ms\MessageServerClient($this->protocol);

        $this->transport->open();

    }
    public function dis_connect() {
        $this->transport->close();
        $this->client = null;
        $this->protocol = null;
        $this->transport = null;
        $this->socket = null;
    }
    /**
    * @Theme  : 
    * @Params : string $jsonString
    * @Return : boolean
    */

    public function send_system_msg(
        $from_uid,
        $action_type, 
        $to_uid,
        $content_id = 0) {
        try {
            $sys_ms = new \ms\SystemMessage();
            $sys_ms->from_uid = $from_uid;
            $sys_ms->action_type = $action_type;
            if (is_array($to_uid)) {
                $sys_ms->to_uid = $to_uid;
            } else {
                $sys_ms->to_uid = array($to_uid); 
            }
            $sys_ms->content_id = $content_id;

            $this->connect();     
            $this->client->send_system_msg($sys_ms);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
        }
    }

    public function set_read($sysMessageId) {
        $ret = true;
        try {
            $this->connect();
            $this->client->set_read($sysMessageId);
            $this->dis_connect();
        } catch (EXception $e) {
            log_message('error', $e->getMessage());
            $ret = false;
        }

        return $ret;
    }

    public function set_delete($sysMessageId) {
        $ret = true;
        try {
            $this->connect(); 
            $this->client->set_delete($sysMessageId);
            $this->dis_connect();
        } catch (EXception $e) {
            log_message('error', $e->getmessage());
            $ret = false;
        }

        return $ret;
    }

    public function send_msg_by_uid($to_uid,
        $type,
        $tid,
        $t_title,
        $t_content) {
        try {
            $this->connect();
            $tweet = new \ms\Tweet();
            $tweet->tid = $tid;
            $tweet->title = $t_title;
            $tweet->content = $t_content;
            $this->client->send_msg_by_uid($to_uid,
                $type, $tweet);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', $e->getmessage());
        }
    }

    public function clear_red_by_uid($uid, $mType, $num) {
        try {
            $this->connect();
            $this->client->clear_red_by_uid($uid, $mType, $num);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', $e->getmessage());
        }
    }

    public function update_config($uid, $config) {
        try {
            $this->connect();
            $this->client->update_config($uid, $config);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', $e->getmessage());
        }
    }

    public function get_num($uid, $queue_type) {
        $num = 0;
        try {
            $this->connect();
            $num = $this->client->get_num($uid, $queue_type);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', $e);
            log_message('error', $e->getmessage());
        }

        return $num;
    }


    public function notice_notify($title, $content, $industry_id, $type, $tid=null, $url=null, $send_time=1424085450) {
        try {
            $this->connect();
            $notice_request = new \ms\NoticeRequest();
            $notice_request->title = $title;
            $notice_request->content = $content;
            $notice_request->industry_id = $industry_id;
            $notice_request->type = $type;
            if (!empty($tid)) {
                $notice_request->tid = $tid;
            }

            if (!empty($url)) {
                $notice_request->url = $url;
            }

            if (!empty($send_time)) {
                $notice_request->send_time = $send_time;
            }
            $this->client->notice_notify($notice_request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', $e->getmessage());
        }
   }

}
