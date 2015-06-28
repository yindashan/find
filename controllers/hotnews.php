<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');


require_once dirname(__FILE__).'/../libraries/Thrift/ClassLoader/ThriftClassLoader.php';

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Protocol\TBinaryProtocol;
class Hotnews extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        //$this->load->model('Community_model');
        //$this->load->model('Zan_model');
        $this->load->model('cache_model');

        $this->request_array['rn'] = isset($this->request_array['rn']) ? $this->request_array['rn'] : 5;
        $this->_set_token_check(true);
	}

	function home()
    {
        $request = $this->request_array;
        $response = $this->response_array;

        log_message('error', json_encode($response));
        log_message('error', json_encode($request));
        if(!isset($request['type'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $type = $request['type'];
        $this->load->model('message_queue_model');
        $rec_messages = $this->message_queue_model->get_rec_message();
        log_message('error', 'rec_messages:'.json_encode($rec_messages));
        if (false === $rec_messages) {
            $response['errno'] = MYSQL_ERR_SELECT; 
            goto end;

        }
        $tid_list = array();
        $res_content = array();
        if($type == 'new') {
            foreach ($rec_messages as $tid) {

                //过滤已删除的帖子
                $fields = array('is_del');
                $fields_arr = $this->cache_model->get_tweet_fields($tid, array('is_del'));
                log_message('error', 'fields_arr:'.json_encode($fields_arr));
                $is_del = isset($fields_arr['is_del']) ? $fields_arr['is_del'] : 0;
                log_message('error', 'is_del:'. $is_del);
                if(intval($is_del) === 1) {
                    log_message('error', 'is_del:'. $is_del);
                    continue;
                } 

                $tid_list[] = $tid; 
                if (10 == count($tid_list)) break;
            }
            /*一期只返回new
            $first_tid = $request['first_tid'];
            if(false !== array_search($first_tid, $tid_list)) {
                $key = array_search($first_tid, $tid_list);
                log_message('error', 'key:'.$key);
                $tid_list = array_slice($tid_list, 0, $key);
                //$type = 'append';
                //一期只返回new
                $type = 'new';
            }
             */

            $this->msclient->clear_red_by_uid($this->_uid, 1, 0);
        }elseif($type == 'next') {
            log_message('error', 'request:'.json_encode($request));
            $last_tid = $request['last_tid']; 
            $found = false;
            foreach ($rec_messages as $tid) {
                //过滤已删除的帖子
                $fields = array('is_del');
                $fields_arr = $this->cache_model->get_tweet_fields($tid, array('is_del'));
                log_message('error', 'fields_arr:'.json_encode($fields_arr));
                $is_del = isset($fields_arr['is_del']) ? $fields_arr['is_del'] : 0;
                log_message('error', 'is_del:'. $is_del);
                if(intval($is_del) === 1) {
                    log_message('error', 'is_del:'. $is_del);
                    continue;
                } 

                if (!$found) {
                    if (intval($tid) == intval($last_tid)) {
                        $found = true; 
                    } 
                } else {
                    $tid_list[] = $tid; 
                    if (10 == count($tid_list)) break;
                }
            }
            log_message('error', 'tid_list:'.json_encode($tid_list));
        }
        if (!empty($tid_list)) {

            //获取点赞标识  todo
            $zan_dict = $this->Zan_model->get_tid_dianzan_dict($this->_uid, $tid_list);

            foreach($tid_list as $res_tid) {
                //$res = $this->Community_model->get_detail_by_tid($res_tid);
                $content = $this->get_tweet_detail($res_tid);

                $praise_flag = $zan_dict[$res_tid];
                $content['praise']['flag'] = $praise_flag;

                //封装整体数据
                $res_content[] = $content;
            }
        }
        $response['data'] = array(
            'content' => $res_content,
            'type' => $type,    
        );
    end:
        $this->renderJson($response['errno'], $response['data']);
        //$this->renderJson($response['errno']);
	}

    /**
     * 获取首页广告
     */
    function advert() {
    
        $content = $this->get_ad();
        $result_arr['content'] = $content;
        $this->renderJson(0, $result_arr);
    
    }

}
