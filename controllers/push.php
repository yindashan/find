<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Push extends MY_Controller {
    function __construct() {
        parent::__construct();

        $this->load->model('Push_user_model');
        $this->load->model('Push_config_model');
    }

    function bind() {
        $this->load->library('offclient');
        $request = $this->request_array;
        $response = $this->response_array;

        $xg_device_token = $request['xg_device_token'];
        $uid = intval($request['uid']);
        $device_type = intval($request['device_type']);
        $ios_device_token = $request['ios_device_token'];
        if(empty($ios_device_token)) {
            $ios_device_token = '-1';
        }
        //$response['errno'] = ERR_DEVICE_TOKEN;

        if (empty($xg_device_token) || empty($uid) || empty($device_type) || $xg_device_token == 'null'){
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }

        $result = $this->Push_user_model->update($xg_device_token, $uid, $device_type, $ios_device_token);
        if (!$result) {
            $response['errno'] = MYSQL_ERR_UPDATE;
            goto end;

        }

        //绑定成功后修改设备关联的tag
        /*$params = array();
        $params['uid'] = $uid;
        $params['op'] = 1;
        $params['xg_device_token'] = $xg_device_token;
        $user_info = $this->get_user_detail_by_uid($uid);
        $tags = array();
        array_push($tags, $user_info['city']);
        array_push($tags, $user_info['school']);
        if ($device_type == 1) {
            array_push($tags, 'android');
        }
        else if ($device_type == 2) {
            array_push($tags, 'ios');
        }

        if ($user_info['ukind_verify'] === 0) {
            array_push($tags, 'verify');
        }
        else if ($device_type === 1) {
            array_push($tags, 'unverify');
        }

        $params['tag_list'] = $tags;

        $this->offclient->SetPushTagEvent($params);*/
        end:
        $this->renderJson($response['errno'], $response['data']);
    }
    private function _init_config() {
        $config = array();
        $config['comment_notify'] = 1; //评论
        $config['zan_notify'] = 0;//点赞
        $config['pmsg_notify'] = 1;//私信
        $config['sys_notify'] = 1;//系统消息
        $config['friend_notify'] = 1; //好友消息

        //return array('config' => $config);
        return $config;
    }

    function get_config() {
        $request = $this->request_array;
        $response = $this->response_array;
        if(!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $uid = intval($request['uid']);
        if (empty($uid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $config = $this->_init_config();
        $ret = array();
        $result = $this->Push_config_model->get($uid);
        if (!empty($result)) {
            $config = json_decode($result, true);
        }
        //$config = array('config' => $config);
        $response['data']['config'] = $config;

        end:
        $this->renderJson($response['errno'], $response['data']);
        //$this->renderJson($errno, $config);
    }

    function update_config() {
        $request = $this->request_array;
        $response = $this->response_array;
        if(!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $uid = intval($request['uid']);
        $config = $request['config'];
        if (empty($uid) || empty($config)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $result = $this->Push_config_model->update($uid, $config);
        if (!$result) {
            $response['errno'] = MYSQL_ERR_UPDATE;
            goto end;
        }
        /*else {
            $this->msclient->update_config($uid, $config);
        }*/
        end:
        $this->renderJson($response['errno'], $response['data']);
        //$this->renderJson($errno, array());
    }
}
