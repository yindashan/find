<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Page extends MY_Controller {

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

        $this->load->model('Tweet_model');
        $this->load->model('Comment_model');
        $this->load->model('Cache_model');
        $this->load->model('Zan_model');

        $this->_set_token_check(false);
    }

    /**
     * 作品分享
     */
    function share() {
    
        $request = $this->request_array;
        $response = $this->response_array;
        $result_arr = array();

        $tid = $request['tid'];
        $uid = $request['uid'];
        $comment_rn = COMMENT_LIST_COUNT;

        $result = $this->get_tweet_detail($tid);

        //获取tweet失败
        if(false === $result) {
            log_message('error', __METHOD__ .':'.__LINE__.' tweet response error, tid['.$tid.'] errno[' . $response['errno'] .']');
            header("location: http://www.baidu.com");
        }

        //tweet不存在
        if(empty($result)) {
            log_message('error', __METHOD__ .':'.__LINE__.' tweet not exist, tid['.$tid.'] errno[' . $response['errno'] .']');
            header("location: http://www.baidu.com");
        }

        //tweet已删除
        if(isset($result['is_del']) && ($result['is_del'] == 1)) {
            log_message('error', __METHOD__ .':'.__LINE__.' tweet is del, tid['.$tid.'] errno[' . $response['errno'] .']');
            header("location: http://www.baidu.com");
        }


        //获取点赞人
        $praise_user_list = array();
        $praise_user = $this->Zan_model->get_user_list($tid, PRAISE_USER_COUNT);
        if(false === $praise_user) {
            $response['errno'] = MYSQL_ERR_SELECT; 
            log_message('error', __METHOD__ .':'.__LINE__.' get praise user error, tid['.$tid.'] errno[' . $response['errno'] .']');

        }
        if(!empty($praise_user)) {
            foreach($praise_user as $user) {
                $praise_user_list[] = $user['username'];
            }
        }
        $result['praise']['user'] = $praise_user_list; 

        //获取评论列表
        $comment = $this->tweet_comment($tid, $comment_rn);
        if(false === $comment) {
            $comment = array();
        }


        //封装整体数据
        $data['content'] = $result;
        $data['comment'] = $comment;
        $this->load->view('share/details', $data);
    }

    /**
     * 获取评论列表
     */
    function tweet_comment($tid, $rn) {

        $content = array();

        $result = $this->Comment_model->get_list_by_tid($tid, $rn);
        if(false === $result) {
            log_message('error', __METHOD__ .':'.__LINE__.' get comment list error, tid['.$tid.'] errno[' . $response['errno'] .']');
            return false;
        }
        if(empty($result)) {
            log_message('error', __METHOD__ .':'.__LINE__.' get comment list null, tid['.$tid.'] errno[' . $response['errno'] .']');
            return false;
        }
        if($result) {
            $content = $result;
            foreach($content as $idx => $comment) {
                //格式化时间
                $content[$idx]['ctime'] = $this->format_time($comment['ctime']);

                //获取当前评论用户数据
                $uid = $comment['uid'];

                $ret = $this->get_user_detail_by_uid($uid, array('sname', 'avatar'));
                if ($ret) {
                    $content[$idx]['sname'] = $ret['sname'];
                    $content[$idx]['avatar'] = $ret['avatar'];
                } else {
                    $content[$idx]['sname'] = '';
                    $content[$idx]['avatar'] = '';
                }

                //获取原评论信息和用户数据
                $reply_cid = $comment['reply_cid'];
                $reply_uid = $comment['reply_uid'];
                if($reply_cid && $reply_uid) {
                    $ret = $this->get_user_detail_by_uid($reply_uid, 'sname');
                    if ($ret) {
                        $content[$idx]['reply_sname'] = $ret;
                    } else {
                        $content[$idx]['reply_sname'] = '';
                    }
                }
            }
        }
        return $content;
    }


}



/* End of file page.php */
/* Location: ./application/controllers/page.php */  
