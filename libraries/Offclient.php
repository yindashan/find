<?php
require_once __DIR__."/Thrift/ClassLoader/ThriftClassLoader.php";

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol as TBinaryProtocol;  
use Thrift\Transport\TSocket as TSocket;  
use Thrift\Transport\TFramedTransport as TFramedTransport;  

class Offclient{

    private $socket = null;
    private $transport = null;
    private $protocol = null;
    private $client = null;

    function __construct() {
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Thrift', __DIR__);
        $loader->registerDefinition('offhub', realpath(__DIR__.'/..').'/service/' );
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
        $this->socket = new TSocket('mhback1', 9030);
        $this->socket->setSendTimeout(10000);
        $this->socket->setRecvTimeout(20000);

        $this->transport = new TFramedTransport($this->socket);
        $this->protocol = new TBinaryProtocol($this->transport);
        $this->client = new \offhub\PostServiceClient($this->protocol); 
        $this->transport->open(); 
    }
    public function dis_connect() {
        $this->transport->close();
        $this->client = null;
        $this->protocol = null;
        $this->transport = null;
        $this->socket = null;
    }

    public function SendNewPost($post_params) {
        try {
            $ps_request = new \offhub\PostServiceRequest();
            $ps_request->tweet_info = new \offhub\TweetStruct();
            $ps_request->tweet_info->tid = isset($post_params['tid']) ? $post_params['tid'] : 0;
            $ps_request->tweet_info->uid = isset($post_params['uid']) ? $post_params['uid'] : 0;;
            $ps_request->tweet_info->title = isset($post_params['title']) ? $post_params['title'] : '';
            $ps_request->tweet_info->content = isset($post_params['content']) ? $post_params['content'] : '';
            $ps_request->tweet_info->ctime = isset($post_params['ctime'])  ? $post_params['ctime'] : time();
            $ps_request->tweet_info->tags = isset($post_params['tags']) ? $post_params['tags'] : '';
            $ps_request->tweet_info->type = isset($post_params['type']) ? $post_params['type'] : -1;
            $ps_request->tweet_info->f_catalog = isset($post_params['f_catalog']) ? $post_params['f_catalog'] : '';
            $ps_request->tweet_info->s_catalog = isset($post_params['s_catalog']) ? $post_params['s_catalog'] : '';
            $ps_request->tweet_info->resource_id = isset($post_params['resource_id']) ? $post_params['resource_id'] : '';
            $resources = isset($post_params['resource']) ? $post_params['resource'] : array();
            $ps_request->tweet_info->resources = array();
            foreach ($resources as $res_str) {
                $res = new \offhub\ResourceStruct();
                $res->rid = $res_str['rid'];
                $res->img = $res_str['img'];
                $res->description = $res_str['description'];
                $ps_request->tweet_info->resources[] = $res;
            }
            $this->connect();     
            $res = $this->client->SendNewPost($ps_request);
            $this->dis_connect();
            return $res;
        } catch (Exception $e) {
            log_message('error', 'send new post error, msg['.$e-> getMessage().']');
            return false;
        }
    }

    public function SendSmsEvent($post_params) {
        try {
            $request = new \offhub\SmsRequest();
            $request->mobile = isset($post_params['mobile']) ? $post_params['mobile'] : "";
            $request->content = isset($post_params['content']) ? $post_params['content'] : "";
            $request->send_time = isset($post_params['send_time']) ? $post_params['send_time'] : 0;
            $request->type = isset($post_params['type']) ? $post_params['type'] : 0;
            $request->sid =isset($post_params['sid']) ? $post_params['sid'] : 0;

            $this->connect();
            $res = $this->client->SendSmsEvent($request);
            $this->dis_connect();

            return $res;
        } catch (Exception $e) {
            log_message('error', __FILE__.':'.__LINE__.' SendSmsEvent error, msg='.$e->getMessage());
            return false;
        }
        return false;
    }

    public function send_event($tid, $type) {
        try {
            $request = new \offhub\EventServiceRequest(); 
            $request->tid = $tid;
            $request->type = $type;
            $this->connect();
            $this->client->SendNewEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', 'send new event error, msg['.$e->getMessage().']'); 
        } 
    }

    public function SendSysMsgEvent($params) {
        try {
            $request = new \offhub\SysMsgRequest();
            $request->from_uid = isset($params['from_uid']) ? $params['from_uid'] : 0;
            $request->action_type = isset($params['action_type']) ? $params['action_type'] : -1;
            $request->to_uid = isset($params['to_uid']) ? $params['to_uid'] : array();
            $request->content_id = isset($params['content_id']) ? $params['content_id'] : 0;
            $this->connect();
            $this->client->SendSysMsgEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __FILE__.':'.__LINE__.'send sys msg error, msg['.$e->getMessage().']'); 
        }
    }

    public function SetSysMsgReadEvent($params) {
        try {
            $request = new \offhub\SetMsgReadRequest();
            $request->msg_id = isset($params['msg_id']) ? $params['msg_id']: -1;

            $this->connect();
            $this->client->SetSysMsgReadEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __FILE__.':'.__LINE__.'set sys msg read error, msg['.$e->getMessage().']'); 
        }
    }

    public function SetSysMsgDeleteEvent($params) {
        try {
            $request = new \offhub\SetMsgDelRequest();
            $request->msg_id = isset($params['msg_id']) ? $params['msg_id']: -1;

            $this->connect();
            $this->client->SetSysMsgReadEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __FILE__.':'.__LINE__.'set sys msg delete error, msg['.$e->getMessage().']'); 
        }
    }

    public function UpdateFriendQueue($params) {
        try {
            $request = new \offhub\FriendMsgRequest();
            $request->uid = isset($params['uid']) ? $params['uid'] : 0;
            $request->tid = isset($params['tid']) ? $params['tid'] : 0;
            $request->msg_type = isset($params['msg_type']) ? $params['msg_type'] : 0;
            $request->timestamp = isset($params['timestamp']) ? $params['timestamp'] : time();

            $this->connect();
            $this->client->UpdateFriendQueue($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __METHOD__.':'.__LINE__
                    .'update friend queue error, msg['.$e->getMessage().']');
        }
    }

    public function ClearRedEvent($params) {
        try {
            $request = new \offhub\ClearRedRequest();
            $request->uid = isset($params['uid']) ? $params['uid']:0;
            $request->mType = isset($params['mType']) ? $params['mType']:-1;
            $request->from_uid = isset($params['from_uid']) ? $params['from_uid']:0;

            $this->connect();
            $this->client->ClearRedEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __FILE__.':'.__LINE__.'clear red  error, msg['.$e->getMessage().']'); 
        }
    }

    public function SetPushTagEvent($params) {
        try {
            $request = new \offhub\SetPushTagRequest();
            $request->uid = isset($params['uid']) ? $params['uid']:0;
            $request->xg_device_token = isset($params['xg_device_token']) ? $params['xg_device_token']:'';
            $request->op = isset($params['op']) ? $params['op']: 1;
            $request->tag_list = isset($params['tag_list']) ? $params['tag_list']:array();

            $this->connect();
            $this->client->SetPushTagEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __FILE__.':'.__LINE__.'set push tag error, msg['.$e->getMessage().']'); 
        }
    }

    public function FollowNewEvent($params) {
        try {
            $request = new \offhub\FollowEvent();
            $request->uid = $params['followee_uid'] ? $params['followee_uid'] : 0;
            $request->follower_uid = $params['uid'] ? $params['uid'] : 0;

            $this->connect();
            $this->client->FollowNewEvent($request);
            $this->dis_connect();
        } catch (Exception $e) {
            log_message('error', __METHOD__.':'.__LINE__.' follow_new_event error, msg['.$e->getMessage().']');
        }
    }
}


