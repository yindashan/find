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
    protected $_uid = null;
    protected $_user_info = null;
    protected $_enable_user_verify_check = false;
    protected $_enable_token_check = false;
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

        //用户认证验证  todo
        //$this->checkAuth($this->request_array);

#        $this->load->model('Community_model');
#        $this->load->model('Zan_model');
#        $this->load->model('user_token_model');

#        $this->load->library('msclient');
#        $this->load->library('offclient');


	}

    protected function _set_token_check($is_on) {
        $this->_enable_token_check = $is_on; 
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
            log_message('error', '_enable_token_check:'.$this->_enable_token_check);
        /*if ($this->execute_file == 's' && $method != 'generate') {
            $this->index($method);
            return;
        }
        if ($this->execute_file == 's' && $method == 'generate') {
            $this->$method();
            return;
        }*/
        // 1. 检查URI
        if (!method_exists($this, $method)) {
            $this->renderJson(ERR_BAD_URI);        
            return;
        }

        // 2. 检查TOKEN
        if ($this->_enable_token_check) {
            if (!isset($this->request_array['token'])) {
                $this->renderJson(ERR_TOKEN_MISS);        
                return;
            }

            $token = $this->request_array['token'];
            $ret = $this->user_token_model->get_token_info($token); 
            if (!$ret) {
                $this->renderJson(ERR_TOKEN_NOT_FOUND);
                return;
            }
            if (!isset($ret['time_keep'])
                || !isset($ret['valid'])
                || !isset($ret['uid'])) {
                $this->renderJson(ERR_TOKEN_NOT_FOUND);
                return; 
            }
            if (intval($ret['time_keep']) < time()) {
                $this->renderJson(ERR_TOKEN_EXPIRED);
                return; 
            } 
            if (0 == $ret['valid']) {
                $this->renderJson(ERR_TOKEN_FORBIDDEN);
                return; 
            }
            $this->_uid = $ret['uid'];
        }


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

    //验证用户认证信息
    private function checkAuth($arrInput) {
        $this->load->model('cache_model');
        $controller = $this->uri->segment(1);
        $func = $this->uri->segment(2);

        $is_verify = TRUE;
        //获取用户认证配置文件
        $this->config->load('authority', TRUE);
        $user_auth = $this->config->item('user_auth', 'authority');
        log_message('error', 'auth_all:'.json_encode($user_auth));

        $user_auth_all = $user_auth['all'];
        $interface_cmp = $this->interface_cmp($user_auth_all, $controller, $func);
        if(!$interface_cmp) {
            $result = $this->cache_model->get_user_info($arrInput['uid'], array('ukind'));
            log_message('error', 'via check');
            if($result) {
                if(isset($result['ukind_verify']) && ($result['ukind_verify'] == 0)) {
                    $is_verify = FALSE;
                }
            }else {
                $is_verify = FALSE;
            }
        }
        if(false === $is_verify) {
            $errno = STATUS_ERR_AUTH;
            $this->renderJson($errno);    
            exit(0);
        }
    }

    private function interface_cmp($interface_path, $controller, $func) {
        if(!empty($interface_path)) {
            foreach($interface_path as $path) {
                $path_arr = explode('/', $path);
                log_message('error', 'path_arr_0:'. $path_arr[0]);
                log_message('error', 'controller:'. $controller);
                if(isset($path_arr[0])) {
                    $concmp = strcmp($controller, $path_arr[0]);
                    log_message('error', 'concmp:'.$concmp);
                }else {
                    return FALSE;
                }
                if(0 === $concmp) {
                    if(isset($path_arr[1])) {
                        $funccmp = strcmp($func, $path_arr[1]);
                    log_message('error', 'funccmp:'.$funccmp);
                        if(0 === $funccmp)
                            return TRUE;
                    }
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * 封装帖子详情数据
     */
    function get_topic_detail($tid) {
        $this->load->model('tweet_model');

        $result = $this->tweet_model->get_tweet_info($tid);
        log_message('error', 'tid:'.$tid);
        log_message('error', 'topic_res:'.json_encode($result));
        //$result = $this->Community_model->get_detail_by_tid($tid);
        if(!empty($result)) {
            $topic = $result;

            //处理帖子发表时间
            //$topic['timestamp'] = $topic['ctime'];
            //$topic['ctime'] = $this->format_time($topic['ctime']);
            //test
            $topic['tags'] = array('静物');
            $topic['content'] = '描述描述描述';
            //获取帖子用户数据
            if(isset($topic['uid']) && !empty($topic['uid'])) {
                $uid = $topic['uid'];
                $user_data = $this->get_user_by_uid($uid);
                log_message('error', 'MY_Controller_user_data:'.json_encode($user_data));
                $sname = $user_data['sname'];
                $avatar= $user_data['avatar'];
                $topic['sname'] = $sname;
                $topic['avatar'] = $avatar;
                $topic['ukind'] = $user_data['ukind'];

                //test
                $topic['sname'] = 'sname';
                $topic['avatar'] = '';
                $topic['ukind'] = 1;

            }

            //获取原帖数据
            if(isset($topic['origin_tid']) && !empty($topic['origin_tid'])) {
                $origin_tweet = $this->tweet_model->get_tweet($topic['origin_tid']);
                
                //echo json_encode($topic);exit;
                //echo json_encode($origin_tweet);exit;
                log_message('error', '*********************************'.json_encode($origin_tweet));

                $ret_origin_uname = $this->tweet_model->get_user_info($origin_tweet['uid'], 'sname');
                if (false !== $ret_origin_uname) {
                    $origin_uname = $ret_origin_uname;
                }
                $body = $origin_tweet['content'];
                $img = $this->get_img($origin_tweet['img']);
                if(empty($body) && !empty($img)) {
                    $body .= '[图片]'; 
                }
                $origin_topic = array(
                    'tid' => $origin_tweet['tid'],
                    'title' => $origin_tweet['title'],
                    'body' => $body,
                    //'img' => $img,
                    'uid' => $origin_tweet['uid'],
                    'uname' => $origin_uname,
                );
                //原帖被删除
                if(isset($origin_tweet['is_del']) && ($origin_tweet['is_del'] == 1)) {
                    $origin_topic['body'] = '[抱歉，此文章已被删除]';   
                }
                $topic['origin_topic'] = $origin_topic;
            }

            //判断帖子是否被删除
            if(isset($topic['is_del']) && ($topic['is_del'] == 1)) {
                $topic['content'] = '[抱歉，此文章已被删除]';   
                if(isset($topic['img'])) {
                    unset($topic['img']);
                }
                if(isset($topic['origin_topic'])){
                    unset($topic['origin_topic']);
                }
            }

            //获取转发数
            if(isset($topic['forward_num'])) {
                $forward_num = $topic['forward_num'];
                $forward = array(
                    'num' => $forward_num,
                    //'url' => "http://app.lanjinger.com/community/detail?tid=" . $tid,
                    'url' => TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid,
                    //'url' => $this->shorten_url(TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid);
                    
                );
                $topic['share'] = $forward;
                unset($topic['forward_num']);
            }

            //分享
            $share = array(
                'num' => 2,
                'url' => TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid,
            );
            $topic['share'] = $share;

            //获取评论数
            if(isset($topic['comment_num'])) {
                $comment_num = $topic['comment_num'];
                $comment = array(
                    'num' => $comment_num,
                );
                $topic['comment'] = $comment;
                unset($topic['comment_num']);
            }

            //获取点赞数
            if(isset($topic['zan_num'])) {
                $zan_num = $topic['zan_num'];
                $zan = array(
                    //'num' => intval($zan_num),
                    'num' => $zan_num,
                    'flag' => true,
                );
                $topic['praise'] = $zan;
                unset($topic['zan_num']);
            }

            if(is_array($topic['content'])) {
                $topic['content'] = $topic['content'];
            }

            $img = array(
                'small' =>array(
                    'url' => 'http://a.hiphotos.baidu.com/image/pic/item/e4dde71190ef76c6ef2f8e929e16fdfaae51678d.jpg',
                    'width' => 200,
                    'height' => 200,
                ),
                'middle' =>array(
                    'url' => 'http://a.hiphotos.baidu.com/image/pic/item/e4dde71190ef76c6ef2f8e929e16fdfaae51678d.jpg',
                    'width' => 350,
                    'height' => 350,
                ),
                'big' =>array(
                    'url' => 'http://a.hiphotos.baidu.com/image/pic/item/e4dde71190ef76c6ef2f8e929e16fdfaae51678d.jpg',
                    'width' => 400,
                    'height' => 400,
                ),
            );

            $topic['img'] = $img;
            $topic['picnum'] = count($img);
            //$topic['body'] = $topic['content'];
            //$topic['img'] = $this->get_img($topic['img']);
            /*
            if(is_array($topic['img'])) {
                $topic['img'] = $topic['img'][0];
            }
             */
            unset($topic['f_catalog']);
            unset($topic['s_catalog']);
            unset($topic['is_del']);
            unset($topic['type']);
        }
        log_message('error', 'topic---:'.json_encode($topic));
        end:
        return $topic;

    }

    /**
     * 封装作品详情数据
     */
    function get_tweet_detail($tid) {
        $this->load->model('tweet_model');

        $result = $this->tweet_model->get_tweet_info($tid);
        log_message('error', 'tid:'.$tid);
        log_message('error', 'topic_res:'.json_encode($result));
        //$result = $this->Community_model->get_detail_by_tid($tid);
        if(!empty($result)) {
            $tweet = $result;

            //处理帖子发表时间
            $tweet['timestamp'] = $tweet['ctime'];
            $tweet['ctime'] = $this->format_time($tweet['ctime']);
            //获取帖子用户数据
            if(isset($tweet['uid']) && !empty($tweet['uid'])) {
                $uid = $tweet['uid'];
                $user_data = $this->get_user_by_uid($uid);
                log_message('error', 'MY_Controller_user_data:'.json_encode($user_data));
                $sname = $user_data['sname'];
                $avatar= $user_data['avatar'];
                $tweet['sname'] = $sname;
                $tweet['avatar'] = $avatar;
                $tweet['ukind'] = $user_data['ukind'];
                $tweet['company_job'] = $user_data['company_job'];
            }

            //获取原帖数据
            if(isset($tweet['origin_tid']) && !empty($tweet['origin_tid'])) {
                $origin_tweet = $this->Community_model->get_tweet($tweet['origin_tid']);
                
                //echo json_encode($tweet);exit;
                //echo json_encode($origin_tweet);exit;
                log_message('error', '*********************************'.json_encode($origin_tweet));

                $ret_origin_uname = $this->tweet_model->get_user_info($origin_tweet['uid'], 'sname');
                if (false !== $ret_origin_uname) {
                    $origin_uname = $ret_origin_uname;
                }
                $body = $origin_tweet['content'];
                $img = $this->get_img($origin_tweet['img']);
                if(empty($body) && !empty($img)) {
                    $body .= '[图片]'; 
                }
                $origin_tweet= array(
                    'tid' => $origin_tweet['tid'],
                    'title' => $origin_tweet['title'],
                    'body' => $body,
                    //'img' => $img,
                    'uid' => $origin_tweet['uid'],
                    'uname' => $origin_uname,
                );
                //原帖被删除
                if(isset($origin_tweet['is_del']) && ($origin_tweet['is_del'] == 1)) {
                    $origin_tweet['body'] = '[抱歉，此文章已被删除]';   
                }
                $tweet['origin_tweet'] = $origin_tweet;
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
                    'num' => $forward_num,
                    //'url' => "http://app.lanjinger.com/community/detail?tid=" . $tid,
                    'url' => TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid,
                    //'url' => $this->shorten_url(TWEET_DETAIL_LANDING_PAGE . "?tid=" . $tid);
                    
                );
                $tweet['forward'] = $forward;
                unset($tweet['forward_num']);
            }

            //获取评论数
            if(isset($tweet['comment_num'])) {
                $comment_num = $tweet['comment_num'];
                $comment = array(
                    'num' => $comment_num,
                );
                $tweet['comment'] = $comment;
                unset($tweet['comment_num']);
            }

            //获取点赞数
            if(isset($tweet['zan_num'])) {
                $zan_num = $tweet['zan_num'];
                $zan = array(
                    //'num' => intval($zan_num),
                    'num' => $zan_num,
                );
                $tweet['praise'] = $zan;
                unset($tweet['zan_num']);
            }

            $tweet['body'] = $tweet['content'];
            $tweet['img'] = $this->get_img($tweet['img']);
            unset($tweet['content']);
            //unset($tweet['catalog']);
        }
        log_message('error', 'tweet---:'.json_encode($tweet));
        end:
        return $tweet;

    }
    /**
     * 行业信息ID和明文相互转换
     */
    protected function get_indus_by_str($indus_str) {
        $this->config->load('user', TRUE);
        $industries = $this->config->item('industry', 'user');
        log_message('error', 'industries:'.json_encode($industries));

        $indus_id = array();
        if(!empty($indus_str)) {
            $strs = explode(',', $indus_str);
            foreach($strs as $str) {
                $indus_id[] = array_search($str, $industries);
            }   
        } 
        return $indus_id;
    
    }

    protected function get_indus_by_id($indus_id) {
        $this->config->load('user', TRUE);
        $industries = $this->config->item('industry', 'user');
        log_message('error', 'industries:'.json_encode($industries));

        $indus_str = array();
        if(!empty($indus_id)) {
            $ids = explode(',', $indus_id);
            foreach($ids as $id) {
                $indus_str[] = $industries[$id];
            }   
        } 
        return $indus_str;
    }

    /**
     * 根据用户id获取用户信息
     *
     * @param string uid 用户id
     * @return array
     */
    function get_user_by_uid($uid) {
        $this->load->model('cache_model');
        $fields = array('uname', 'sname', 'avatar', 'ukind', 'ukind_verify', 'company', 'company_job');
        $user_data = $this->cache_model->get_user_info($uid, $fields);
        log_message('error', 'user_data:'.json_encode($user_data));
        return $user_data;

    }

    /**
     * 根据用户id返回用户的详情信息
     *
     * @params string: $uid 用户id
     * @return array
     **/
    function get_user_detail_by_uid($uid) {
        $this->load->model('cache_model');
        $user_detail_info = $this->cache_model->get_user_detail_info($uid, '*');

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
        log_message('debug', 'user_data:'.json_encode($user_data));

        return $user_data;

    }

    function get_img($json_img) {
        if(empty($json_img)) {
            return array(); 
        }
        return json_decode($json_img, true);
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
        /*
        if(empty($arrData)){
            $result['data'] = (object)$arrData;
        }else {
            $result['data'] = $arrData;
        }
         */
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
        //preg_match_all($pattern, $url_string, $matches, PREG_SET_ORDER);
        $line  =  preg_replace_callback ($pattern ,
            function ( $matches ) {
                return  $this->short_url_model->generate_url($matches [ 0 ]);
            },$url_string);
        return $line;

        //log_message('error', 'matches:'.json_encode($matches));
        log_message('error', 'line:'.json_encode($line));
    }

    protected function handle_at($content) {
       $pattern = '/@([^\s]*)/i'; 
       preg_match_all($pattern, $content, $matches);
       log_message('error', 'at_matches:'.json_encode($matches));
    }

    //格式化时间
    protected function format_time($timestamp, $current_time = 0) {
        if(!$current_time)
            $current_time = time();
        $span = $current_time - $timestamp;
        log_message('error', '-------------------span:'.$span);
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


}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
