<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');


class Material extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        $this->load->model('tweet_model');
        $this->load->library('seclient');

        $this->_set_login_check(false);
	}

    /**
     * 列表页
     */
	function material_list()
    {
        $request = $this->request_array;
        $response = $this->response_array;

        $f_catalog = $request['f_catalog'];
        $s_catalog = $request['s_catalog'];
        $wd = $f_catalog.' '.$s_catalog;
        $type = isset($request['type']) ? $request['type'] : 1;
        $tag = isset($request['tag']) ? explode(",", $request['tag']) : array();
        $pn = isset($request['pn']) ? $request['pn'] : 0; 
        $rn = isset($request['rn']) ? $request['rn'] : 10;

        for ($i = 0; $i < count($tag); $i++) {
            $tag[$i] = trim($tag[$i]);
        }

        $se_input = array();
        $se_input['wd'] = $wd;
        $se_input['pn'] = $pn;
        $se_input['rn'] = $rn;
        $se_input['type'] = $type;
        $se_input['tag'] = $tag;

        $se_result = $this->seclient->search($se_input);
        if (!$se_result || $se_result->err_no != 0) {
            $response['errno'] = 1999;
            goto end;
        }

        $id_list = $se_result->id;

        $res_content = array();
        foreach($id_list as $tid) {
            $tweet = $this->get_tweet_detail($tid);
            $tweet['imgs'] = end($tweet['imgs']);
            $res_content[] = $tweet;
        }

        $response['data'] = array(
            'content' => $res_content,
            'choose_type' => $type,    
            'catalog' => $se_result->catalog,
        );

        end:
            $this->renderJson($response['errno'], $response['data']);

    }

    /**
     * 搜索结果页
     */
	function search()
    {
        $request = $this->request_array;
        $response = $this->response_array;

        
        if(!isset($request['type'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $type = isset($request['type']) ? $request['type'] : 1;
        $wd = $request['wd'];
        $tag = isset($request['tags']) ? explode(",", $request['tags']) : array();
        $pn = isset($request['pn']) ? $request['pn'] : 0;
        $rn = isset($request['rn']) ? $request['rn'] : 10;
        $catalog = isset($request['catalog']) ? $request['catalog'] : -1;

        for ($i = 0; $i < count($tag); $i++) {
            $tag[$i] = trim($tag[$i]);
        }

        $se_input = array();
        $se_input['wd'] = $wd;
        $se_input['pn'] = $pn;
        $se_input['rn'] = $rn;
        $se_input['type'] = $type;
        $se_input['tag'] = $tag;
        $se_input['catalog'] = $catalog;
         
        $se_result = $this->seclient->search($se_input);
        if (!$se_result || $se_result->err_no != 0) {
            $response['errno'] = 1999;
            goto end;
        }

        $id_list = $se_result->id;

        $res_content = array();
        foreach($id_list as $tid) {
            $tweet = $this->get_tweet_detail($tid);
            $tweet['imgs'] = end($tweet['imgs']);
            $res_content[] = $tweet;
        }

        $response['data'] = array(
            'content' => $res_content,
            'choose_type' => $type,    
            'catalog' => $se_result->catalog,
            'total_num' => $se_result->total_num,
        );

        end:
        $this->renderJson($response['errno'], $response['data']);

	}
}


/* End of file material.php */
/* Location: ./application/controllers/material.php */
