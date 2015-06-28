<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Community_Detail_module extends CI_Module {

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

	function index($req)
    {
        $data = array(
            'errno' => 0,
            'data' => array(
                'content' => array(
                    'user' => '张文文',
                    'profession' => '新华网记者',
                    'date' => '一天前',
                    'topic' => array(
                        'title' => '关于今年招生的一点看法',
                        'body' => '一中今年是怎么回事啊？一中今年是怎么回事啊？',
                        'img' => array(
                            'http://d.hiphotos.baidu.com/image/pic/item/562c11dfa9ec8a13d0f91696f403918fa1ecc0e1.jpg',
                            'http://c.hiphotos.baidu.com/image/pic/item/908fa0ec08fa513d9c492da83e6d55fbb3fbd9e1.jpg',
                        ),

                    ),
                    'forward_num' => 11, 
                    'comment_num' => 12, 
                    'praise_num' => 13, 
                    'praise' => array(
                        '许安安',
                        '李武',        
                    ),
                    'comments' => array(
                        array(
                            'comment_user' => '徐安安',
                            'user_url' => '',
                            'comment' => '一中今年是怎么回事？',
                        ),
                        array(
                            'comment_user' => '徐安安',
                            'user_url' => '',
                            'comment' => '一中今年是怎么回事？',
                        ),
                    ),
                ),    
            ),
        );  

        echo json_encode($data);exit;
	}
}
