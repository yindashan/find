<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * 
 * 
 */

class MY_Controller extends CI_Controller
{
    protected $request_array = array();
    protected $result_array = array();
    protected $_redis;

	public function __construct()
	{
		parent::__construct();

        date_default_timezone_set('Asia/Shanghai');
        $_POST += $_GET;
        $this->request_array = $_POST;

        $this->_redis = new Redis();
        $this->_redis->connect('123.57.249.47',8888);

        $this->load->model('Community_model');
        $this->load->model('Zan_model');

        $this->load->library('msclient');
        $this->load->library('offclient');

	}

    function redis_exists($key) {

        return $this->_redis->exists($key);
    }

    function redis_get($key) {

        return $this->_redis->get($key);
    
    }

    function redis_set($key, $value) {

        $ttl = 60;
        $this->_redis->setex($key, $ttl, $value);
    }

    private function get_uuid() {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        return  strtoupper(md5(uniqid(rand(), true)));
    }

    /**
     * 封装帖子详情数据
     */
    function get_topic_detail($tid) {
        $this->load->model('cache_model');

        $result = $this->cache_model->get_tweet_info($tid);
        //$result = $this->Community_model->get_detail_by_tid($tid);
        if(!empty($result)) {
            $res = $result;
            $topic = $res;

            //获取帖子用户数据
            if(isset($topic['uid']) && !empty($topic['uid'])) {
                $uid = $topic['uid'];
                $user_data = $this->get_user_by_uid($uid);
                $sname = $user_data['sname'];
                $avatar= $user_data['avatar'];
                $topic['sname'] = $sname;
                $topic['avatar'] = $avatar;
            }

            $topic['body'] = $topic['content'];
            $topic['img'] = $this->get_img($topic['img']);
            unset($topic['content']);
            //unset($topic['catalog']);
        }
        return $topic;

    }

    /**
     * 根据用户id获取用户信息
     *
     * @param string uid 用户id
     * @return array
     */
    function get_user_by_uid($uid) {
        $this->load->model('cache_model');
        $fields = array('uname', 'sname', 'avatar');
        $user_data = $this->cache_model->get_user_info($uid, $fields);
        log_message('error', 'user_data:'.json_encode($user_data));
        return $user_data;

    }

    function get_uname_by_uid($uid) {
        
        //test
        $sname = 'lanjing_test';
        return $sname;
    }

    /**
     * 根据帖子id获取评论信息
     *
     * @param string tid 帖子id
     * @return array
     */
    function get_comment_by_tid($tid) {

        //$result = $this->Comment_model->get_list_by_tid($tid);
        $comment_data = array(
            array(
                'cid' => 1,
                'uid' => 1,
                'tid' => 1,
                'content' => '一中今年是怎么回事?',
                'ctime' => 1362997137,
                'reply_uid' => 1,
                'reply_cid' => 1,
            ),
            array(
                'cid' => 2,
                'uid' => 2,
                'tid' => 2,
                'content' => '一中今年是怎么回事?',
                'ctime' => 1362997137,
                'reply_uid' => 1,
                'reply_cid' => 1,
            ),
        );
        return $comment_data;
    }

    /**
     * 根据帖子id获取点赞信息
     *
     * @param string tid 帖子id
     * @return array
     */
    function get_praise_by_tid($tid) {
            
        $result = $this->Zan_model->get_user_list($tid, 10);
        if(!$result) {
            return array();
        }   
        return $result;

        $praise_data = array(
            array(
                'uid' => 1,
                'tid' => 1,
                'username' => 'test',    
            ),  
            array(
                'uid' => 2,
                'tid' => 1,
                'username' => 'test2',    
            ),  
        );  
        return $praise_data;
    }

    /** 
     * 反序列化图片
     *
     * @param string img 序列化后的图片链接
     * @return array
     */
    function get_img($serialized_img) {
        if(unserialize($serialized_img)) {
            return unserialize($serialized_img);
        } else {
            return array();
        }   
    } 

    /**
     * 获取广告内容
     */
    function get_ad() {
    
        return array();
    }

	/**
	 *
	 * @param   int	
	 * @param   array	
	 * @return	string
	 */
    protected function renderJson($intStatus, $arrData = array()) {
        header("Content-Type:application/json;charset=utf-8");
        $result = array(
            'errno' => $intStatus,
        );
        //if(!empty($arrData)) {
            $result['data'] = $arrData;
        //}
        if (empty($this->arrRequest['callback'])) {
            echo json_encode($result);
        } else {
            echo '/**/'. $this->arrRequest['callback'] . ' && ' .
            $this->arrRequest['callback'] . '(' .
            json_encode($result) . ');';
        }
        return;
    }


}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
