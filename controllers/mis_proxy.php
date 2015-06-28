<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mis_proxy extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        $this->load->model('Community_model');
        $this->load->model('Mis_topic_model');

	}

    /**
     * MIS管理内容接口 
     */
    function topic() {
        $request = $this->request_array;
        $response = $this->response_array;
        log_message('error', 'mis_request:'.json_encode($request));
        if(!isset($request['type'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $type = $request['type'];

        switch ($type) {
            case 'new':                        //发布内容
                $this->topic_new($request);
                break;
            case 'edit':                       //编辑内容
                $this->topic_edit($request);
                break;
            case 'recommend':                  //推荐或取消推荐
                $this->topic_recommend($request);
                break;
            case 'delete':                     //删除或取消删除
                $this->topic_delete($request);
                break;
        }
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    /**
     *
     */
    private function _check_rec($tid, $is_essence) {
        $res = $this->Mis_topic_model->get_fields_by_tid($tid, 'is_essence');  
        if ($res && 1 == count($res)) {
            if (intval($is_essence) == intval($res['is_essence'])) {
               return true; 
            }
            $data = array('is_essence' => $is_essence);
            $ret = $this->Mis_topic_model->update_by_tid($tid, $data);
            if (false === $ret) {
                return false; 
            }
            $ret = $this->Community_model->update_by_tid($tid, $data);
            if (false === $ret) {
                return false; 
            }
            $this->load->model('message_queue_model');          
            if (1 == intval($is_essence)) {
                $ret = $this->message_queue_model->add_rec_message($tid);
                if (false === $ret) {
                    return false; 
                }
            } else {
                $ret = $this->message_queue_model->cancel_rec_message($tid);     
                if (false === $ret) {
                    return false; 
                }
            }
        } else {
            return false; 
        }
        return true;
    }

    /**
     * 发布内容
     */
    private function topic_new($request) {
        $this->load->library('offclient'); 
        log_message('error', 'topic_new:'.json_encode($request));
        $errno = STATUS_OK;
        $res_arr = array();
        $data = array(
            'uid' => $request['uid'],
            'title' => $request['title'],            
            'content' => $request['content'],        
            'img' => $request['img'],    
            'industry' => $request['industry'],    
            'is_essence' => $request['is_essence'],    
            'ctime' => time(),    
        );  

        //操作线上数据库
        $online_tid = $this->Community_model->add($data);
        if($online_tid) {

            //请求离线模块
            $data['tid'] = $online_tid;
            $res = $this->offclient->SendNewPost($data);
            if (0 !== $res->err_no) {
                log_message('error', '[MIS]: send message to offhub error, tid['.$online_tid.'] errno['.$res['errno'].']'); 
                $errno = STATUS_ERR_OFFCLIENT;
                goto end;
            }
        }else {
            $errno = MYSQL_ERR_INSERT;
            goto end;
        }

        // add rec message
        if (1 == intval($request['is_essence'])) {
            $this->load->model('message_queue_model');          
            $ret = $this->message_queue_model->add_rec_message($online_tid);
            if (false === $ret) {
                $errno = MYSQL_ERR_INSERT; 
                goto end;
            }
        }
    end:
        $this->renderJson($errno, $res_arr);
    }

    /**
     * 编辑内容
     */
    private function topic_edit($request) {
        log_message('error', 'topic_edit:'.json_encode($request));
        $errno = STATUS_OK;
        if(!isset($request['tid'])) {
            $errno = STATUS_ERR_REQUEST;
            goto end;
        }
        $tid = $request['tid'];
        $data = array(
            'uid' => $request['uid'],
            'title' => $request['title'],            
            'content' => $request['content'],        
            'img' => $request['img'],    
            'industry' => $request['industry'],    
//            'ctime' => time(),    
        );  

        $ret = $this->_check_rec($tid, $request['is_essence']);
        if (false === $ret) {
            $errno = STATUS_ERR_RESPONSE; 
            goto end;
        }

        //更新线上库
        $online_update = $this->Community_model->update_by_tid($tid, $data);
        if($online_update) {
            //更新线下库，通过线上帖子ID映射
            $offline_update = $this->Mis_topic_model->update_by_tid($tid, $data);
            if(!$offline_update) {
                $errno = MYSQL_ERR_UPDATE;
                goto end;
            }
        }else {
            $errno = MYSQL_ERR_UPDATE;
            goto end;
        }
    end:
        $this->renderJson($errno);
    }

    /**
     * 推荐或取消推荐
     */
    private function topic_recommend($request) {
        log_message('error', 'topic_recommend:'.json_encode($request));
        $errno = STATUS_OK;
        if(!isset($request['tid'])) {
            $errno = STATUS_ERR_REQUEST;
            goto end;
        }
        $tid = $request['tid'];
        $op = isset($request['op']) ? $request['op'] : 0;            //0:取消推荐，1:推荐 
        if(intval($op) !== 0 && intval($op) !== 1) {
            $op = 0;
        }
        $ret = $this->_check_rec($tid, $op);
        if (false == $ret) {
            $errno = STATUS_ERR_RESPONSE; 
            goto end;
        }
        end:
        $this->renderJson($errno);
    }

    /**
     * 删除或取消删除
     */
    private function topic_delete($request) {
        $errno = STATUS_OK;
        $tid = $request['tid'];
        $op = isset($request['op']) ? $request['op'] : 0;            //0:取消删除，1:删除 
        if(intval($op) !== 0 && intval($op) !== 1) {
            $op = 0;
        }
        $data = array(
            'is_del' => $op,    
        );  

        //更新线上库
        $online_update = $this->Community_model->update_by_tid($tid, $data);
        if($online_update) {
            //更新线下库，通过线上帖子ID映射
            $offline_update = $this->Mis_topic_model->update_by_tid($tid, $data);
            if(!$offline_update) {
                $errno = MYSQL_ERR_UPDATE;
                goto end;
            }
        }else {
            $errno = MYSQL_ERR_UPDATE;
            goto end;
        }
        if (1 == intval($op)) {
            $this->load->model('message_queue_model');          
            $ret = $this->message_queue_model->cancel_rec_message($tid);
            if (false === $ret) {
                $errno = REDIS_ERR_OP; 
            }
        }
        end:
        $this->renderJson($errno);
    }

    function push() {
        $this->load->library('msclient');
        $request = $this->request_array;
        $title = $this->input->post('title');
        $content = $this->input->post('content');
        $industry = $this->input->post('industry');
        $type = $this->input->post('type');
        $tid = $this->input->post('tid');
        $url = $this->input->post('url');
        $send_time = $this->input->post('send_time');
        $push_task_id = $this->input->post('push_task_id');

        //请求消息中心
        $this->msclient->notice_notify($title, $content, $industry, $type, $tid, $url, $send_time, $push_task_id);
        $this->renderJson(STATUS_OK);
    }

    function notify_valid_user() {
        $request = $this->request_array;

        if (!isset($request['uid'])
            || !isset($request['mis_id'])) {
            $this->renderJson(STATUS_ERR_REQUEST); 
            return;
        }
        $this->load->library('msclient');
        $uid = $request['uid'];
        $mis_id = $request['mis_id'];
        $this->msclient->send_system_msg($mis_id, ms\ActionType::MIS_AUTHENTED, $uid);
        $this->renderJson(STATUS_OK); 
    }

}
