<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Find extends MY_Controller {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();
    }


    /**
     * 获取用户帖子列表
     *
     */
    function index() {

        $request = $this->request_array;
        log_message('debug', '[find]---test-------------------');
        $response = $this->response_array;

        $res_content = array('test find');
        $response['data'] = array(
            'content' => $res_content,
        );
        end:
        $this->renderJson($response['errno'], $response['data']);
    }


}



/* End of file tweet.php */
/* Location: ./application/controllers/tweet.php */  
