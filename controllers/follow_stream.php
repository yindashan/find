<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Follow_stream extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        $this->load->library('msclient');
        $this->load->library('offclient');

        $this->load->model('Tweet_model');
        $this->load->model('Message_queue_model');
        $this->load->model('Zan_model');
        $this->load->model('Short_url_model');
	}

    /**
     * 广场关注列表
     */
    function get() 
    {
        //后端统一控制
        $this->request_array['rn'] = TWEET_COMMUNITY_LIST_COUNT;
        $request = $this->request_array;
        $response = $this->response_array;

        $res_content = array();

        if(!isset($request['type']) || !isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__.':'.__LINE__. ' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $type = $request['type'];
        $uid = $request['uid'];

        if(empty($type) || empty($uid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__. ' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $rn = $request['rn'];

        $tweets = $this->Message_queue_model->get_user_message($uid);
        if(false === $tweets) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' get user message error, uid['.$uid.'] errno['.$response['errno'].']');
            goto end;
        }else if(empty($tweets)){
            log_message('error', __METHOD__ .':'.__LINE__.' get user message null, uid['.$uid.'] errno['.$response['errno'].']');
            goto end;
        } elseif(!empty($tweets)) {
            if($type == 'new') {
                //下拉刷新取最新帖子
                foreach($tweets as $tid) {
                    //过滤已删除的帖子
                    $fields = array('is_del');
                    
                    $fields_arr = $this->Tweet_model->get_tweet_fields($tid, array('is_del'));
                    if(false === $fields_arr || empty($fields_arr)) {
                        continue;
                    }
                    log_message('error', 'fields_arr:'.json_encode($fields_arr));
                    $is_del = isset($fields_arr['is_del']) ? $fields_arr['is_del'] : 0;
                    if(intval($is_del) === 1) {
                        continue;
                    }

                    $tid_list[] = $tid;
                    if(count($tid_list) == $rn) break;
                }
            }elseif($type == 'next') {
                //上拉获取更多帖子
                $last_id = $request['last_id'];
                $pass_flag = true;
                foreach($tweets as $key => $tid) {
                    if ($last_id == $tid) {
                        $pass_flag = false;
                        continue;
                    }
                    if ($pass_flag) {
                        continue;
                    }
                    //过滤已删除的帖子
                    $fields = array('is_del');
                    
                    $fields_arr = $this->Tweet_model->get_tweet_fields($tid, array('is_del'));
                    if(is_null($fields_arr)) {
                        continue;
                    }
                    $is_del = isset($fields_arr['is_del']) ? $fields_arr['is_del'] : 0;
                    if(intval($is_del) === 1) {
                        continue;
                    }
                    $tid_list[] = $tid;
                    if(count($tid_list) == $rn) break;
                }
            }
            if(!empty($tid_list)) {
                foreach($tid_list as $k => $tid) {
                    $content = $this->get_tweet_detail($tid);
                    if(false === $content || empty($content) || empty($content['imgs'])) {
                        continue;
                    }       
                    if(isset($content['imgs'])) {
                        $img_num = count($content['imgs']);
                        if(0 < $img_num) {
                            $img_idx = $img_num - 1;
                            $content['imgs'] = $content['imgs'][$img_idx];
                        }
                    }  
                    $res_content[] = $content;
                }
            }
            $response['data'] = array(
                'content' => $res_content,
                'type' => $type,
            );
        }
        end:
            $this->renderJson($response['errno'], $response['data']);
    }
}


/* End of file follow_stream.php */
/* Location: ./application/controllers/follow_stream.php*/
