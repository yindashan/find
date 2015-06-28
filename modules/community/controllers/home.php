<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Community_Home_module extends CI_Module {

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

	function index($request)
    {
        $this->load->model('Data_model');

        $type = $request['type'];
        $pn = $request['pn'];
        $rn = $request['rn'];
        if($type == 'new') {
            
            //下拉刷新取最新帖子

            $tid = $request['first_tid'];
            $result = $this->Data_model->get_list($rn, $pn * $rn);

        }elseif($type == 'next') {
            
            //上拉刷新取最后一个帖子之后的帖子
            
            $tid = $request['last_tid'];
            $result = $this->Data_model->get_list($tid, $rn, $pn * $rn+1);
        }
        $result_arr = $result->result_array();
        foreach($result_arr as $key => $res) {
            $origin_tid = $res['origin_tid'];
            if(!empty($origin_tid)) {
                $origin_result = $this->Data_model->get_detail_by_tid($origin_tid);          
                foreach($origin_result->result_array() as $row) {
                    $result_arr[$key]['origin_topic'] = $row;
                }
            }
        }
        //return $result_arr;
        //print_r($result->result_array());exit;
        $data = array(
            'errno' => 0,
            'data' => array(
                'content' => array(
                    array(
                        'tid' => '33435454',
                        'uid' => '4543543543',
                        'uname' => '张文文',
                        'profession' => '新华网记者',
                        'date' => '一天前',
                        'disscussion' => '//转发 @ABC',
                        'origin_topic' => array(
                            'tid' => '3243243',
                            'uid' => '563452rewre',
                            'uname' => '许安安',
                            'title' => '许安安：这是里转发原文',
                            'body' => '新闻联播第三条，再谈高考改革。有粉丝说上学太早了，那还愿意回炉么？',
                            'img' => array('http://www.lanjinger.com/data/uploads/2015/0117/19/middle_54ba4962bb726.png?time=Sun%20Jan%2025%202015%2012:35:34%20GMT+0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)',
                            ),
                            'ctime' => '34234324',
                            'type' => '1',
                        ),  
                        'forward' => array(
                            'num' => 11,
                            'url' => 'xxx',
                        ), 
                        'comment_num' => 12, 
                        'praise' => array(
                            'num' => 13,
                            'flag' => 1,
                        ), 
                        'ctime' => '34234324',
                        'type' => '1',
                    ),
                    array(
                        'tid' => '343435',
                        'uid' => '4354353365',
                        'uname' => '张文文',
                        'profession' => '新华网记者',
                        'date' => '一天前',
                        'disscussion' => '',
                        'origin_topic' => array(
                            'tid' => '343434546546',
                            'uid' => 'dfdsfdsf',
                            'uname' => '许安安',
                            'title' => '这里是标题啊！！',
                            'body' => '【快讯】-财联社23日讯，刘鹤被任命为中财办主任兼发改委副主任。',
                            'img' => array(
                                'http://f.hiphotos.baidu.com/image/pic/item/d1a20cf431adcbefcbddf872afaf2edda2cc9fdd.jpg',
                                'http://f.hiphotos.baidu.com/image/pic/item/d52a2834349b033b86a9bcc816ce36d3d539bdfb.jpg'    
                            ),
                            'ctime' => '34234324',
                            'type' => '1',

                        ),    
                        'forward' => array(
                            'num' => 11, 
                            'url' => 'xxx',
                        ),
                        'comment_num' => 12, 
                        'praise' => array(
                            'num' => 13,
                            'flag' => 1,
                        ), 
                        'ctime' => '34234324',
                        'type' => '1',
                    ),
                    array(
                        'tid' => '33435454',
                        'uid' => '4543543543',
                        'uname' => '张文文',
                        'profession' => '新华网记者',
                        'date' => '一天前',
                        'disscussion' => '//转发 @ABC',
                        'origin_topic' => array(
                            'tid' => '3243243',
                            'uid' => '563452rewre',
                            'uname' => '许安安',
                            'title' => '许安安：这是里转发原文',
                            'body' => '快讯：3月11日下午，中国人民银行行长周小川在政协十二届一次会议第四次全体会议上当选政协第十二届全国委员会副主席。',
                            'img' => array(),
                            'ctime' => '34234324',
                            'type' => '1',
                        ),  
                        'forward' => array(
                            'num' => 11,
                            'url' => 'xxx',
                        ), 
                        'comment_num' => 12, 
                        'praise' => array(
                            'num' => 13,
                            'flag' => 1,
                        ), 
                        'ctime' => '34234324',
                        'type' => '1',
                    ),
                    array(
                        'tid' => '33435454',
                        'uid' => '4543543543',
                        'uname' => '张文文',
                        'profession' => '新华网记者',
                        'date' => '一天前',
                        'disscussion' => '//转发 @ABC',
                        'origin_topic' => array(
                            'tid' => '3243243',
                            'uid' => '563452rewre',
                            'uname' => '许安安',
                            'title' => '许安安：这是里转发原文',
                            'body' => '21日晚，证监会官网披露赣州稀土借壳威华股份被否，原本是要和昌九生化联姻的。而失利根源是赣州稀土未能获得稀土行业准入资格。今早威华股份开盘跌停，昌九生化开盘即涨停。今早最佳：昌九生化好像听到小三被前夫抛弃的原配.....昌九股吧的人们可是热闹了一把',
                            'img' => array(
                                    'http://www.lanjinger.com/data/uploads/2015/0122/10/middle_54c062c6728be.png?time=Sun%20Jan%2025%202015%2012:28:44%20GMT+0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)',
                            'http://www.lanjinger.com/data/uploads/2015/0122/10/middle_54c062c6728be.png?time=Sun%20Jan%2025%202015%2012:28:44%20GMT+0800%20(%E4%B8%AD%E5%9B%BD%E6%A0%87%E5%87%86%E6%97%B6%E9%97%B4)',
                            ),
                            'ctime' => '1362997137',
                            'type' => '1',
                        ),  
                        'forward' => array(
                            'num' => 11,
                            'url' => 'xxx',
                        ), 
                        'comment_num' => 12, 
                        'praise' => array(
                            'num' => 13,
                            'flag' => 1,
                        ), 
                        'ctime' => '34234324',
                        'type' => '1',
                    ),
                    array(
                        'tid' => '33435454',
                        'uid' => '4543543543',
                        'uname' => '张文文',
                        'profession' => '新华网记者',
                        'date' => '一天前',
                        'disscussion' => '//转发 @ABC',
                        'origin_topic' => array(
                            'tid' => '3243243',
                            'uid' => '563452rewre',
                            'uname' => '许安安',
                            'title' => '许安安：这是里转发原文',
                            'body' => '—独家：财联社22日讯，高层有意再扩大四个城市的房地产税试点范围。',
                            'img' => array(),
                            'ctime' => '1363946658',
                            'type' => '1',
                        ),  
                        'forward' => array(
                            'num' => 11,
                            'url' => 'xxx',
                        ), 
                        'comment_num' => 12, 
                        'praise' => array(
                            'num' => 13,
                            'flag' => 1,
                        ), 
                        'ctime' => '1364050334',
                        'type' => '1',
                    ),
                ),  
            ),    
        );  

        //$this->load->module('test2/home_made/add');
        echo json_encode($data);exit;
	}
}
