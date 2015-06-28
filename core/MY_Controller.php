<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * MY_Controller 
 * 
 */

class MY_Controller extends CI_Controller
{
    protected $request_array = array();
    protected $result_array = array();
    protected $_uid = -1;
    protected $_user_info = null;
    protected $_enable_user_verify_check = false;
    protected $_enable_token_check = true;
    protected $_enable_login_check = true;
    protected $_execute_file = '';

	public function __construct()
	{
		parent::__construct();

        date_default_timezone_set('Asia/Shanghai');
        $_POST += $_GET;
        $this->request_array = $_POST;

        $this->response_array = array(
            'errno' => STATUS_OK,
            'data' => array(),    
        );


        $this->load->model('Zan_model');

        $this->load->library('offclient');
        $this->load->library('uidclient');

	}

    protected function _set_token_check($is_on) {
        $this->_enable_token_check = $is_on; 
    }

    protected function _set_login_check($is_on) {
        $this->_enable_login_check = $is_on; 
    }

    protected function _get_user_info () {
        if ($this->_user_info) {
            return $this->_user_info; 
        } 
        $this->load->model('cache_model');
        return $this->cache_model->get_user_info($this->_uid, '*');
    }

    private function _user_verify_check() {
        $ret = $this->_get_user_info();
        if (!$ret) {
            return ERR_USER_NOT_VERIFIED; 
        }
        if (!isset($ret['ukind_verify'])
            || 0 == $ret['ukind_verify']) {
            return ERR_USER_NOT_VERIFIED; 
        }
        return 0; 
    }

    private function _other_checks() {
        if ($this->_enable_user_verify_check) {
            $ret = $this->_user_verify_check();
            if ($ret) return $ret; 
        }
        return 0; 
    }

    function execute($method = 'index') {
        // 1. 检查URI
        if (!method_exists($this, $method)) {
            $this->renderJson(ERR_BAD_URI);        
            return;
        }

        // 2. 检查TOKEN
        /*
        if ($this->_enable_token_check) {
            //需要验证登录
            if($this->_enable_login_check) {
                if (!isset($this->request_array['token']) || empty($this->request_array['token'])) {
                    $this->renderJson(ERR_TOKEN_MISS);        
                    return ;
                }

                $token = $this->request_array['token'];
                $ret = $this->check_token($token);

                switch ($ret) {
                case false:
                    $this->renderJson(ERR_TOKEN_NOT_FOUND);
                    return ;
                case 1:
                    $this->renderJson(ERR_TOKEN_UID_NOT_MATCH);
                    return ;
                case 2:
                    $this->renderJson(ERR_TOKEN_UID_NOT_MATCH);
                    return ;
                case 3:
                    $this->renderJson(ERR_TOKEN_EXPIRED);
                    return ;
                case 4:
                    $this->renderJson(ERR_TOKEN_FORBIDDEN);
                    return ;
                default:
                    ;
                }

                $this->_uid = $ret['uid'];
            }else {
                //无需验证登录
                if (isset($this->request_array['token']) && !empty($this->request_array['token'])) {
                    $token = $this->request_array['token'];
                    $ret = $this->check_token($token);
                    if(false !== $ret && is_array($ret)) {
                        $this->_uid = $ret['uid'];
                    }
                }
            }
        }
         */


        // 3. 检查签名
        /*
        if (!$this->checkSign($this->request_array)) {
            $this->renderJson(ERR_BAD_SIGN); 
            return;
        }
         */

        // 4. 其他的检查
        $ret = $this->_other_checks();
        if (0 !== $ret) {
            $this->renderJson($ret);
            return;
        }

        $this->$method(); 
    }

    private function checkSign($arrInput, $uid='') {
        $sign = $arrInput['sign'];
        return $sign == $this->getSign($arrInput, $uid);
    }

    private function getSign($arrInput, $uid) {
        $str = $this->getQueryString($arrInput);
        $str .= strval($uid);
        return md5(sha1($str));
    }

    private function getQueryString($arrInput, $superKey = '')
    {
        $str = '';
        ksort($arrInput);
        foreach ($arrInput as $key => $value) {
            if ($superKey != '') {
                $key = $superKey.'['.$key.']';
            }
            if (is_array($value)) {
                $str .= $this->getQueryString($value, $key);
            } else {
                $str .= $key . '=' . $value . '&';
            }
        }
        return $str;
    }

    function get_relation_type($uid, $target_uid) {
        $this->load->model('relation_model');
        $ret = $this->relation_model->get_relation_info($uid, $target_uid);
        if (!$ret) {
            return 0;
        }
        if ($uid < $target_uid) {
            $is_follower = $ret['a_follow_b'] != 0;
            $is_followee = $ret['b_follow_a'] != 0;
        } else {
            $is_follower = $ret['b_follow_a'] != 0;
            $is_followee = $ret['a_follow_b'] != 0;
        }
        if (!$is_follower) {
            return 0;
        }
        if (!$is_followee) {
            return 1;
        }
        return 2;
    }

    /**
     * 封装作品详情数据
     */
    function get_tweet_detail($tid) {
        $this->load->model('tweet_model');
        $this->load->model('Cache_model');

        $result = $this->tweet_model->get_tweet_info($tid);
        if(false === $result || empty($result)) {
            return $result;
        }
        if(!empty($result)) {
            $tweet = $result;
            $tweet['tags'] = empty($tweet['tags']) ? array() : explode(',', $tweet['tags']);

            //获取帖子用户数据
            if(isset($tweet['uid']) /*&& !empty($tweet['uid'])*/) {
                $uid = $tweet['uid'];
                $user_data = $this->get_user_by_uid($uid);
                $sname = $user_data['sname'];
                $avatar= $user_data['avatar'];
                $tweet['sname'] = $sname;
                $tweet['avatar'] = $avatar;
                $tweet['ukind'] = intval($user_data['ukind']);
                $tweet['intro'] = $user_data['intro'];

            }else {
                return false;
            }

            //判断帖子是否被删除
            if(isset($tweet['is_del']) && ($tweet['is_del'] == 1)) {
                $tweet['content'] = '[抱歉，此文章已被删除]';   
                if(isset($tweet['img'])) {
                    unset($tweet['img']);
                }
                if(isset($tweet['origin_tweet'])){
                    unset($tweet['origin_tweet']);
                }
            }

            //获取转发数
            if(isset($tweet['forward_num'])) {
                $forward_num = $tweet['forward_num'];
                $forward = array(
                    'num' => intval($forward_num),
                    'url' => TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid,
                );
                $tweet['share'] = $forward;
                unset($tweet['forward_num']);
            }

            //分享
            $share = array(
                'num' => 2,
                'url' => TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid,
            );
            $tweet['share'] = $share;

            //获取评论数
            if(isset($tweet['comment_num'])) {
                $comment_num = $tweet['comment_num'];
                $comment = array(
                    'num' => intval($comment_num),
                );
                $tweet['comment'] = $comment;
                unset($tweet['comment_num']);
            }

            //获取点赞信息
            $ret = $this->Cache_model->get_zan_list($tid);
            if (false === $ret) {
                $zan_num = 0;
            } else {
                $zan_num = count($ret);
            }

            //获取点赞标识
            $zan_dict = $this->Zan_model->get_tid_dianzan_dict($this->_uid, array($tid));
            if(false === $zan_dict) {
                $zan_dict = array();
            }  
            $praise_flag = isset($zan_dict[$tid]) ? $zan_dict[$tid] : false;
            $zan = array(
                'num' => intval($zan_num),
                'flag' => $praise_flag,
            );
            $tweet['praise'] = $zan;

            //获取关注关系
            $tweet['follow_type'] = $this->get_relation_type($this->_uid, $uid);

            if(is_array($tweet['content'])) {
                $tweet['content'] = $tweet['content'];
            }

            $tweet['imgs'] = json_decode($tweet['img'], true);
            $tweet['picnum'] = count($tweet['imgs']);
            unset($tweet['f_catalog']);
            unset($tweet['s_catalog']);
            unset($tweet['type']);
            unset($tweet['img']);
        }
        end:
        return $tweet;

    }

    /**
     * 根据用户id获取用户信息
     *
     * @param string uid 用户id
     * @return array
     */
    function get_user_by_uid($uid) {
        $this->load->model('cache_model');
        $fields = array('uname', 'sname', 'avatar', 'ukind', 'ukind_verify', 'intro');
        $user_data = $this->cache_model->get_user_detail_info($uid, $fields, 0);
        return $user_data;
    }

    /**
     * 根据用户id返回用户的详情信息
     *
     * @params string: $uid 用户id
     * @return array
     **/
    function get_user_detail_by_uid($uid, $fields = '*', $avatar_type = 0) {
        $this->load->model('cache_model');
        $user_detail_info = $this->cache_model->get_user_detail_info($uid, $fields, $avatar_type);

        return $user_detail_info;
    }

    /**
     * 根据用户id获取用户所有信息
     *
     * @param string uid 用户id
     * @return array
     */
    function get_user_info_by_uid($uid, $fields = '*') {
        $this->load->model('cache_model');
        $user_data = $this->cache_model->get_user_info($uid, $fields);

        return $user_data;

    }

    function get_img($json_img) {
        if(empty($json_img)) {
            return array(); 
        }
        return json_decode($json_img, true);
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
        if(!empty($arrData)) {
            $result['data'] = $arrData;
        }
        if (empty($this->request_array['callback'])) {
            echo json_encode($result);
        } else {
            echo '/**/'. $this->request_array['callback'] . ' && ' .
            $this->request_array['callback'] . '(' .
            json_encode($result) . ');';
        }
        return;
    }

    protected function analyse_content($content) {

        //短url处理
        $content = $this->shorten_url($content);
    }

    protected function analyse_at($content) {

        $at_name = '';
        $pattern = '/@([^\s]*)/i';
        preg_match_all($pattern, $content, $matches);
        if(!empty($matches[1])) {
            $at_name = $matches[1];
        }
        return $at_name;
    
    }

    protected function shorten_url($url_string) {
        $this->load->model('short_url_model');
        $pattern = '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i';
        $line  =  preg_replace_callback ($pattern ,
            function ( $matches ) {
                return  $this->short_url_model->generate_url($matches [ 0 ]);
            },$url_string);
        return $line;
    }

    protected function handle_at($content) {
       $pattern = '/@([^\s]*)/i'; 
       preg_match_all($pattern, $content, $matches);
    }

    //格式化时间
    protected function format_time($timestamp, $current_time = 0) {
        if(!$current_time)
            $current_time = time();
        $span = $current_time - $timestamp;
        $format_time = '';
        if($span < 60) {
            $format_time = "刚刚";
        }elseif($span < 3600) {
            $format_time = intval($span/60) . "分钟前";
        }elseif($span < 24*3600) {
            $format_time = intval($span/3600)."小时前";
        }elseif($span < (7*24*3600)) {
            $format_time = intval($span/(24*3600))."天前";
        }else{
            $format_time = date('Y-m-d',$timestamp);
        }
        return $format_time;
    }

    protected function check_token($hash_key) {
        $this->load->model('user_token_model');
        $result = $this->user_token_model->get_token_info($hash_key);
        if (!$result) {
            log_message('error', __METHOD__.':'.__LINE__.' get_token_info error,'
                .' hash_key='.strval($hash_key));
            return false;
        }
        if (!isset($result['invalid_time'])) {
            log_message('error', __METHOD__.':'.__LINE__.' invalid_time not exist,'
                .' hash_key='.strval($hash_key));
            return false;
        }
        if (!isset($result['hash_key'])) {
            log_message('error', __METHOD__.':'.__LINE__.' hash_key not exist,'
                .' hash_key='.strval($hash_key));
            return false;
        }
        if (!isset($result['uid'])) {
            log_message('error', __METHOD__.':'.__LINE__.' uid not exist,'
                .' hash_key='.strval($hash_key));
            return false;
        }
        if (!isset($result['is_valid'])) {
            log_message('error', __METHOD__.':'.__LINE__.' is_valid not exist,'
                .' has_key='.strval($hash_key));
            return false;
        }
        $current_time = time();
        $invalid_time = $result['invalid_time'];
        $user_hash_key = $result['hash_key'];
        $uid = $result['uid'];
        $is_valid = $result['is_valid'];

        // hash_key not match, maybe other device loginin.
        if ($hash_key !== $user_hash_key) {
            log_message('debug', __METHOD__.':'.__LINE__.' hash_key not match, uid='
                .strval($uid).' user_hash_key='.$hash_key.' current_key='.$user_hash_key);
            return 2;
        }
        // token timeout.
        if (intval($invalid_time) <= $current_time) {
            log_message('debug', __METHOD__.':'.__LINE__.' token is timeout,'.' current_key='.$user_hash_key);
            return 3;
        }
        if (1 != intval($is_valid)) {
            log_message('debug', __METHOD__.':'.__LINE__.' is_valid != 1.');
            return 4;
        }

        return $result;
    }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
