<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');


class Search extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();
        $this->load->library('testclient');
        $this->_set_token_check(false);
	}

    /**
     * 搜索结果页
     */
	function test()
    {
        $request = $this->request_array;
        $wd = $request['wd'];
        $pn = isset($request['pn']) ? $request['pn'] : 0;
        $rn = isset($request['rn']) ? $request['rn'] : 10;
        $type = isset($request['type']) ? intval($request['type']) : 1;
        $catalog = isset($request['catalog']) ? intval($request['type']) : 0;
        $tag = isset($request['tag']) ? explode("|", $request['tag']) : array();
        $se_result = $this->testclient->search(array(
            'wd' => $wd,
            'pn' => $pn,
            'rn' => $rn,
            'type' => $type,
            'catalog' => $catalog,
            'tag' => $tag,
        ));
        echo json_encode($se_result);exit;

        $id_list = $se_result->id;
        $res_content = array();
        foreach($id_list as $tid) {
            echo $tid; echo 'xxx';
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

        echo json_encode($response['data']);

        
	}

}
