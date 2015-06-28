<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Hot_stream extends MY_Controller {

	function __construct()
	{
        parent::__construct();

        $this->_set_login_check(false);
	}

    public function get() {
        $this->load->model('hot_stream_model');
        $pn = isset($this->request_array['pn']) ? intval($this->request_array['pn']) : 0;
        $rn = isset($this->request_array['rn']) ? intval($this->request_array['rn']) : 20;
        $ret = $this->hot_stream_model->get_by_page($pn, $rn);

        if (false === $ret) {
            $this->renderJson(REDIS_ERR_OP); 
            return;
        }
        if (0 == count($ret)) {
            $this->renderJson(STATUS_OK, array('content' => array())); 
            return;
        }
        $this->load->model('tweet_model');
        $result = array();
        foreach ($ret as $tid) {
            $ret = $this->get_tweet_detail($tid);
            if(false === $ret) {
                log_message('error', __METHOD__ .':'.__LINE__.'tweet response error, tid['.$tid.'] uid['.$this->_uid.'] errno[' . $response['errno'] .']');
                $this->renderJson(STATUS_ERR_RESPONSE, array('content' => array()));
            }
            if(empty($ret) || empty($ret['imgs'])) {
                continue;
            }

            $img_num = count($ret['imgs']);
            if(0 < $img_num) {
                $img_idx = $img_num - 1;
                $ret['imgs'] = $ret['imgs'][$img_idx];
            }
            if ($ret /*&& 0 == intval($ret['is_del'])*/) {
                $result[] = $ret;  
            }
        }
        end:
        $this->renderJson(STATUS_OK, array('content' => $result, 'type' => 'new'));
    }

}


/* End of file hot_stream.php */
/* Location: ./application/controllers/hot_stream.php */
