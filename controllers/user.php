<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class User extends MY_Controller {

    const NO_FOLLOW = 0;
    const ONE_WAY_FOLLOW = 1;
    const MUTUAL_FOLLOW = 2;
    const R_ONE_WAY_FOLLOW = 3;
    const EMAIL_PATTERN = "/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/";
	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();

        $this->load->model('relation_model');
        $this->load->model('user_model');
        $this->load->model('user_detail_model');
        $this->load->model('user_token_model');

        if (in_array($this->uri->segment(2), array('get_info'))) {
            $this->_set_token_check(true);
        } else {
            $this->_set_token_check(false);
        }
    }

    function _check_sname($sname, $uid) {
        $ret = $this->user_detail_model->get_info_by_sname($sname);
        if (false === $ret) {
            return false;
        }
        if (NULL !== $ret && $ret['uid'] != $uid) {
            return 1;
        }
        
        return 0;
    }

    function _is_mobile_exist($umobile) {
        $ret = $this->user_model->get_uid_by_phone($umobile);
        if (false === $ret) {
            return false;
        }
        // phone exist
        if ($ret) {
            return 1;
        }
    
        return 0;
    }

    function _init_register_user($uid) {
        // set user_ext info
        $this->load->model('cache_model');
        $this->cache_model->get_user_ext_info($uid);

        // TODO: set follower info

        return true;
    }

    function _random_sname($sname) {
        $new_name = $sname;
        $max_seed = MAX_SNAME_SEED;
        $min_seed = 0;
        $counter = 0;
        while (NULL !== $this->user_detail_model->get_info_by_sname($new_name)) {
            if ($counter >= 10) {
                $min_seed = $max_seed;
                $max_seed *= 10;
                $counter = 0;
            }
            $new_name = $sname . '_' . strval(rand($min_seed, $max_seed - 1));
            $counter++;
        }

        return $new_name;
    }

    function _verify_captcha($umobile, $captcha, $type) {
        // load sms_model
        $this->load->model('sms_model');
        $sms_info = $this->sms_model->get_info_by_verifycode($umobile, $captcha, $type);
        if(false === $sms_info) {
            return false;
        }

        //验证码非法
        if(is_null($sms_info)) {
            return ERR_SMS_VERIFYCODE_ILLEGAL;
        }

        //校验验证码是否失效
        $ctime_keep = $sms_info['ctime_keep'];
        if(time() > $ctime_keep) {
            return ERR_SMS_VERIFYCODE_TIMEOUT;
        }
    
        return true;
    }

    function is_valified() {
        $request = $this->request_array;
        $response = $this->response_array;
        $errno = 0;
        $result_arr = array();

        if(!isset($request['uid'])) {
            $this->renderJson(STATUS_ERR_REQUEST);
            return;
        }

        $ret = $this->get_user_info_by_uid($request['uid'], array('ukind_verify')); 
        if (!$ret) {
            $this->renderJson(STATUS_ERR_RESPONSE);
            return;
        }

        $this->renderJson(STATUS_OK, array('is_valified' => intval($ret['ukind_verify'])));
    }

	function get_info()
    {
        $request = $this->request_array;
        $response = $this->response_array;
        $arr_result = array();
        $arr_select_column = array();
        $own_info = false;

        // 1. judge uid exist
        if (!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE__.":".__LINE__.' get_info doesn\'t have [uid].');
            goto end;
        }
        $uid = $request['uid'];
        
        // 2. judge se_id exist
        if (!isset($request['se_id'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __FILE.":".__LINE__." get_info doesn't have [se_id]");
            goto end;
        }
        $se_id = $request['se_id'];

        // 3. judge own_info or others_info
        if ($se_id == $uid) {
            $own_info = true;
        }

        // 4. get user_info
        $user_info_res = $this->get_user_detail_by_uid($se_id, '*', 1);
        if (!$user_info_res) {
            $response['errno'] = STATUS_ERR_RESPONSE;
            log_message('error', __FILE__.":".__LINE__." get_info: get_user_info_by_uid failed.");
            goto end;
        }

        log_message('debug', __FILE__.':'.__LINE__
            ." get_info user ".strval($se_id).' info: '.json_encode($user_info_res));

        // 5. get ext info
        // my_info: get follower, fans, approval
        // follower and followee num
        $user_ext_info = $this->cache_model->get_user_ext_info($uid);
        if (false === $user_ext_info) {
            log_message('error', __FILE__.":".__LINE__." get_info: get_user_ext_info failed.");
            $user_ext_info = array(
                'follower_num'  => 0,
                'followee_num'  => 0
            ); 
        }
        $user_info_res = array_merge($user_info_res, $user_ext_info);

         
        $this->load->model('tweet_action_model');
        $action_type = 2; // action_type  1:发帖 2：点赞  3：分享
        $approval_num = $this->tweet_action_model->get_count_by_owneruid($uid, $action_type);
        if (false === $approval_num) {
            log_message('error', __METHOD__.':'.__LINE__.' get_count_by_owneruid error.');
            $approval_num = 0;
        }
        $user_info_res['approval_num'] = $approval_num;
        
		
        // 7. get timeline/photo/achievement
        $user_info_res['sex'] = 1; // 1:男，2:女
        $user_info_res['timeline_num'] = 23;
        $user_info_res['photo_num'] = 11;
        $user_info_res['achievement_num'] = 935;

        // 5. get others info
        if ($own_info) {
            /*
            // my_info: get follower, fans, approval
            // follower and followee num
            $user_ext_info = $this->cache_model->get_user_ext_info($uid);
            if (false === $user_ext_info) {
                log_message('error', __FILE__.":".__LINE__." get_info: get_user_ext_info failed.");
                $user_ext_info = array(
                    'follower_num'  => 0,
                    'followee_num'  => 0
                );
            }
            $user_info_res = array_merge($user_info_res, $user_ext_info);
            */
        } else {
            // others_info: get follower status
            $follow_type = $this->relation_model->get_relation_info($uid, $se_id);
            if (is_null($follow_type)) {
                $follow_type = array(
                    'follow_type'   => 0,
                );
            } else {
                if ($uid < $se_id) {
                    $a_follow_b = $follow_type['a_follow_b'] != 0;
                    $b_follow_a = $follow_type['b_follow_a'] != 0;
                } else {
                    $a_follow_b = $follow_type['b_follow_a'] != 0;
                    $b_follow_a = $follow_type['a_follow_b'] != 0;
                }
                if (!$a_follow_b) {
                    $follow_type = NO_FOLLOW;
                } else {
                    if (!$b_follow_a) {
                        $follow_type = ONE_WAY_FOLLOW;
                    } else {
                        $follow_type = MUTUAL_FOLLOW;
                    }
                }
                $follow_type = array(
                    'follow_type'   => $follow_type,
                );
            }
            $user_info_res = array_merge($user_info_res, $follow_type);
        }

        $response['data'] = $user_info_res;

        end:
            $this->renderJson($response['errno'], $response['data']);
    }

    function modify_user_info() {
        $request = $this->request_array;
        $response = $this->response_array;

        $mysql_req = array();
        if (!isset($request['uid'])) {
            log_message('error', __METHOD__.':'.__LINE__.' uid not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        $uid = $request['uid'];

        if (isset($request['sname'])) {
            $mysql_req['sname'] = $request['sname'];
        }
        if (isset($request['avatar'])) {
            $mysql_req['avatar'] = $request['avatar'];
        }
        if (isset($request['intro'])) {
            $mysql_req['intro'] = $request['intro'];
        }
        if (isset($request['sex'])) {
            $mysql_req['sex'] = $request['sex'];
        }

        if (empty($mysql_req)) {
            log_message('error', __METHOD__.':'.__LINE__.' request is empty.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }

        $ret = $this->user_model->get_user_info($uid, 'register_status');
        if (!$ret) {
            log_message('error', __METHOD__.':'.__LINE__.' select register_status error.');
            $this->renderJson(MYSQL_ERR_SELECT);
            return ;
        }
        $register_status = intval($ret['register_status']);

        // check sname
        if (isset($mysql_req['sname'])) {
            $ret = $this->_check_sname($mysql_req['sname'], $uid);
            if (false === $ret) {
                log_message('error', __METHOD__.':'.__LINE__.' sname select error, sname='.$mysql_req['sname']);
                $this->renderJson(MYSQL_ERR_CONNECT);
                return ;
            }
            if (1 === $ret) {
                $this->renderJson(USER_SNAME_EXIST);
                return ;
            }
        }

        // not the real user
        if (2 === $register_status) {
            $arr_user_req = array(
                'register_status'   => 0,
            );
            if (!isset($mysql_req['sname'])) {
                log_message('error', __METHOD__.':'.__LINE__.' sname not exist, uid='.$uid);
                $this->renderJson(STATUS_ERR_REQUEST);
                return ;
            }
            // insert user detail
            $mysql_req['uid'] = $uid;
            $ret = $this->user_detail_model->add($mysql_req);
            if (!$ret) {
                log_message('error', __METHOD__.':'.__LINE__.' user_detail_model add error, uid='.$uid);
                $this->renderJson(MYSQL_ERR_INSERT);
                return ;
            }
            // update user register_status
            $ret = $this->user_model->update_by_uid($uid, $arr_user_req);
            if (!$ret) {
                log_message('error', __METHOD__.':'.__LINE__.' user not exist, uid='.$uid);
                $this->renderJson(USER_NOT_EXIST);
                return ;
            }
        } else if (0 === $register_status) {
            // TODO: verify the token
            $ret = $this->user_detail_model->update_info_by_uid($uid, $mysql_req);
            if (false === $ret) {
                log_message('error', __METHOD__.':'.__LINE__.' update_info_by_uid error, uid='.$uid);
                $this->renderJson(MYSQL_ERR_UPDATE);
                return ;
            }
        } else {
            log_message('error', __METHOD__.':'.__LINE__.' unkown register type, type='.$register_status);
            $this->renderJson(STATUS_ERR_RESPONSE);
            return ;
        }

        $this->response['data'] = array(
            'uid'   => $uid,
        );

        //用户资料修改成功后，推送绑定的tag需要对应修改
        /*
        $this->load->library('offclient');
        $params = array();
        $params['uid'] = $uid;
        $params['op'] = 1;
        $tags = array();
        $user_info = $this->get_user_detail_by_uid($uid);
        array_push($tags, $user_info['city']);
        array_push($tags, $user_info['school']);
        if ($user_info['ukind_verify'] === 0) {
            array_push($tags, 'verify');
        }
        else if ($device_type === 1) {
            array_push($tags, 'unverify');
        }
        $params['tag_list'] = $tags;
        $this->offclient->SetPushTagEvent($params);*/

        $this->renderJson(STATUS_OK, $this->response['data']);

    }

    function register() {
        $request = $this->request_array;
        $response = $this->response_array;

        if (!isset($request['email_addr'])) {
            log_message('error', __METHOD__.':'.__LINE__.' email_addr not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['password'])) {
            log_message('error', __METHOD__.':'.__LINE__.' password not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        /*
        if (!isset($request['captcha'])) {
            log_message('error', __METHOD__.':'.__LINE__.' captcha not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }*/
        $email_addr = $request['email_addr'];
        $password = $request['password'];
        //$captcha = $request['captcha'];

        if (!preg_match($this::EMAIL_PATTERN, $email_addr)) {
            log_message('debug', __METHOD__.':'.__LINE__.' email pattern not match.');
            $this->renderJson(USER_EMAIL_VALID);
            return ;
        }
        // check email
        $is_email_exist = $this->user_model->is_user_emtail_exist($email_addr);
        if (false === $is_email_exist) {
            log_message('error', __METHOD__.':'.__LINE__.' is_user_emtail_exist error.');
            $this->renderJson(MYSQL_ERR_CONNECT);
            return ;
        }
        if (1 === $is_email_exist) {
            log_message('error', __METHOD__.':'.__LINE__.' user exist, email_addr='.$email_addr);
            $this->renderJson(USER_EXIST);
            return ;
        }

        $this->user_model->clear_valid_user($email_addr);

        // load sms_model
        /*
        $this->load->model('sms_model');
        $sms_info = $this->sms_model->get_info_by_verifycode($umobile, $captcha, 1);
        if(false === $sms_info) {
            $this->renderJson(MYSQL_ERR_SELECT);
            log_message('error', __METHOD__.':'.__LINE__.' get sms info error, '
                .'mobile['.$mobile.'] verifycode['.$captcha.'] errno[' .MYSQL_ERR_SELECT.']');
            return ;
        }

        //验证码非法
        if(is_null($sms_info)) {
            $this->renderJson(ERR_SMS_VERIFYCODE_ILLEGAL);
            log_message('error', __METHOD__ . ' verifycode illegal error, '
                .'mobile['.$umobile.'] verifycode['.$captcha.'] errno[' . ERR_SMS_VERIFYCODE_ILLEGAL.']');
            return ;
        }

        //校验验证码是否失效
        $ctime_keep = $sms_info['ctime_keep'];
        if(time() > $ctime_keep) {
            $this->renderJson(ERR_SMS_VERIFYCODE_TIMEOUT);
            log_message('error', __METHOD__ . ' verifycode timeout error, '
                .'mobile['.$umobile.'] verifycode['.$captcha.'] errno[' . ERR_SMS_VERIFYCODE_TIMEOUT  .']');
            return ;
        }*/

        $arr_mysql_req = array(
            'email_addr'   => $email_addr,
            'pass_word' => $password,
            'login_type'    => 0,
            'create_time'   => time(),
            'register_status'   => 2,
        );

        // add user
        $uid = $this->user_model->add($arr_mysql_req);
        if (false === $uid) {
            log_message('error', __METHOD__.':'.__LINE__.' add_user error.');
            $this->renderJson(MYSQL_ERR_CONNECT);
            return ;
        }
        if (NULL === $uid) {
            log_message('error', __METHOD__.':'.__LINE__.' add_user not affect rows.');
            $this->renderJson(MYSQL_ERR_INSERT);
            return ;
        }

        // invalid captcha
        /*
        $ret = $this->sms_model->set_captcha_invalid($umobile, 1);
        if (false === $ret) {
            log_message('error', __METHOD__.':'.__LINE__.' invalid captcha error, umobile='.$umobile);
        }*/

        $this->_init_register_user($uid);

        $response['data'] = array(
            'uid'   => $uid,
        );


        $this->renderJson($response['errno'], $response['data']);

    }

    function rewrite_pass() {
        $request = $this->request_array;
        $response = $this->response_array;

        if (!isset($request['umobile'])) {
            log_message('error', __METHOD__.':'.__LINE__.' umobile not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['password'])) {
            log_message('error', __METHOD__.':'.__LINE__.' password not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['captcha'])) {
            log_message('error', __METHOD__.':'.__LINE__.' captcha not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        $umobile = $request['umobile'];
        $password = $request['password'];
        $captcha = $request['captcha'];
        $ret = $this->_verify_captcha($umobile, $captcha, 2);
        if (false === $ret) {
            log_message('error', __METHOD__.':'.__LINE__.' verity_captcha error.');
            $this->renderJson(MYSQL_ERR_CONNECT);
            return ;
        }
        if (true !== $ret) {
            $this->renderJson($ret);
            return ;
        }
        $arr_mysql_req = array(
            'pass_word'  => $password,
        );

        $user_info = $this->user_model->get_user_by_phone($umobile);
        if (false === $user_info) {
            log_message('error', __METHOD__.':'.__LINE__.' get_info_by_phone error, umobile='.$umobile);
            $this->renderJson(MYSQL_ERR_SELECT);
            return ;
        }
        if (NULL === $user_info) {
            $this->renderJson(USER_NOT_EXIST);
            return ;
        }

        $ret = $this->user_model->update_by_uid($user_info['id'], $arr_mysql_req);
        if (!$ret) {
            log_message('error', __METHOD__.':'.__LINE__.' update_by_uid error, uid='.strval($uid));
            $this->renderJson(MYSQL_ERR_UPDATE);
            return ;
        }

        // set token invalid
        $token_info_list = $this->user_token_model->get_token_info_by_uid($user_info['id']);
        log_message('error', var_export($token_info_list, true));
        if (!$token_info_list) {
            log_message('error', __METHOD__.':'.__LINE__.' get_token_info_by_uid error, uid='.$user_info['id']);
        } else {
            foreach ($token_info_list as $token_info) {
                $token_list[] = $token_info['hash_key'];
            }
            $ret = $this->user_token_model->set_token_invalid_of_redis($token_list);
            log_message('error', 'wal_ice:'.var_export($ret, true));
            if (false === $ret) {
                log_message('error', __METHOD__.':'.__LINE__.' set_token_invalid_of_redis error, uid='.$user_info['id']);
            }
            $ret = $this->user_token_model->set_token_invalid_by_uid($user_info['id']);
            if (false === $ret) {
                log_message('error', __METHOD__.':'.__LINE__.' set_token_invalid_by_uid error, uid='.$user_info['id']);
            }
        }
        // set captcha invalid
        $ret = $this->sms_model->set_captcha_invalid($umobile, 2);
        if (false === $ret) {
            log_message('error', __METHOD__.':'.__LINE__.' set_captcha_invalid error, umobile='.$umobile);
        }

        $this->renderJson(STATUS_OK);
    }

    function verify_forgot_pass() {
        $request = $this->request_array;
        $response = $this->response_array;

        if (!isset($request['umobile'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key umobile not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['captcha'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key captcha not exist.');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        $umobile = $request['umobile'];
        $captcha = $request['captcha'];

        $ret = $this->_is_mobile_exist($umobile);
        if (0 === $ret) {
            $this->renderJson(USER_NOT_EXIST);
            return ;
        }

        // load sms_model
        $this->load->model('sms_model');
        $sms_info = $this->sms_model->get_info_by_verifycode($umobile, $captcha, 2);
        if(false === $sms_info) {
            $this->renderJson(MYSQL_ERR_SELECT);
            log_message('error', __METHOD__.':'.__LINE__.' get sms info error, '
                .'mobile['.$mobile.'] verifycode['.$captcha.'] errno[' .MYSQL_ERR_SELECT.']');
            return ;
        }

        //验证码非法
        if(is_null($sms_info)) {
            $this->renderJson(ERR_SMS_VERIFYCODE_ILLEGAL);
            return ;
        }

        //校验验证码是否失效
        $ctime_keep = $sms_info['ctime_keep'];
        if(time() > $ctime_keep) {
            $this->renderJson(ERR_SMS_VERIFYCODE_TIMEOUT);
            return ;
        }

        $this->renderJson(STATUS_OK);
    }

    function normal_login() {
        $request = $this->request_array;
        $response = $this->response_array;

        if (!isset($request['email_addr'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key [email_addr] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['password'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key [password] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        $email_addr = $request['email_addr'];
        $password = $request['password'];

        $result = $this->user_model->get_user_by_email_addr($email_addr);
        if (false === $result) {
            log_message('error', __METHOD__.':'.__LINE__.' get_user_by_email_addr error.');
            $this->renderJson(MYSQL_ERR_CONNECT);
            return ;
        }
        if (NULL === $result) {
            log_message('error', __METHOD__.':'.__LINE__.' get_user_by_email_addr not found.');
            $this->renderJson(USER_NOT_EXIST);
            return ;
        }

        $uid = $result['id'];
        $right_pass = $result['pass_word'];
        if ($password !== $right_pass) {
            log_message('debug', __METHOD__.':'.__LINE__.' password not correct.');
            $this->renderJson(USER_ERR_PASS);
            return ;
        }

        $register_status = intval($result['register_status']);
        if (0 !== $register_status) {
            switch ($register_status) {
            case 2:
                log_message('error', __METHOD__.':'.__LINE__.' user detail lacked, status='.$register_status);
                $this->renderJson(USER_DETAIL_LACK);
                return ;
            default:
                log_message('error', __METHOD__.':'.__LINE__.' unkown register_status = '.$result['register_status']);
                $this->renderJson(STATUS_ERR_RESPONSE);
                return ;
            }
        }

        // return info
        $arr_response = array();
        $arr_response['data'] = array(
            'uid'   => $uid,
        );

        // TODO: need ip
        $user_token = $this->create_token($uid, '');
        if (false === $user_token) {
            log_message('error', __METHOD__.':'.__LINE__.' create_token failed, uid='.strval($uid));
        }
        $arr_response['token'] = $user_token;

        $response['errno'] = 0;
        $response['data'] = $arr_response;

        $this->renderJson($response['errno'], $response['data']);
    }

    function third_part_login() {
        $request = $this->request_array;
        $response = $this->response_array;

        if (!isset($request['oauth_type'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key [oauth_type] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['oauth_key'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key [oauth_key] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['sname'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key [sname] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        if (!isset($request['avatar'])) {
            log_message('error', __METHOD__.':'.__LINE__.' key [avatar] not exist!');
            $this->renderJson(STATUS_ERR_REQUEST);
            return ;
        }
        $str_avatar = $request['avatar'];
        if ("" !== $str_avatar) {
            // process avatar to json
            $arr_avatar = array(
                'img'   => array(
                    'n' => array(
                        'url'   => $str_avatar,
                    ),
                    's' => array(
                        'url'   => $str_avatar,
                    ),
                ),
            );
            $str_avatar = json_encode($arr_avatar);
        }
        $arr_user_req = array(
            'oauth_type'    => $request['oauth_type'],
            'oauth_key'     => $request['oauth_key'],
            'login_type'    => 1,
            'register_status'   => 0,
            'create_time'   => time(),
        );
        $arr_user_detail_req = array(
            'sname'         => $request['sname'],
            'avatar'        => $str_avatar,
        );


        // check is a new user or not
        $is_new = false;
        $uid = $this->user_model->get_uid_by_oauth($arr_user_req['oauth_type'], $arr_user_req['oauth_key']);
        if (false === $uid) {
            log_message('error', __METHOD__.':'.__LINE__.' get_uid_by_oauth error.');
            $this->renderJson(MYSQL_ERR_CONNECT);
            return ;
        }
        if (NULL === $uid) {
            log_message('debug', __METHOD__.':'.__LINE__.' it\'s a new user.');
            $is_new = true;
        }

        // a new user
        if ($is_new) {
            // random a new sname
            $sname = $this->_random_sname($arr_user_detail_req['sname']);
            $arr_user_detail_req['sname'] = $sname;

            // get base info
            $uid = $this->user_model->add($arr_user_req);
            if (false === $uid) {
                log_message('error', __METHOD__.':'.__LINE__.' add_user error.');
                $this->renderJson(MYSQL_ERR_CONNECT);
                return ;
            }
            if (NULL === $uid) {
                log_message('error', __METHOD__.':'.__LINE__.' add_user not affect rows.');
                $this->renderJson(MYSQL_ERR_INSERT);
                return ;
            }
            // get detail
            $arr_user_detail_req['uid'] = $uid;
            $user_detail_res = $this->user_detail_model->add($arr_user_detail_req);
            if (false === $user_detail_res) {
                // TODO: roll back the last query
                log_message('error', __FILE__.':'.__LINE__.' add_user_detail error, uid='.$uid);
                $this->renderJson(MYSQL_ERR_CONNECT);
                return ;
            }
            if (NULL === $user_detail_res) {
                log_message('error', __FILE__.':'.__LINE__.' add_user_detail not affect rows, uid='.$uid);
                $this->renderJson(MYSQL_ERR_INSERT);
                return ;
            }
            log_message('debug', __METHOD__.':'.__LINE__.' new user, uid='.$uid);
        }

        // get detail info
        /*
        $arr_user_detail = $this->get_user_detail_by_uid($uid);
        if (false === $arr_user_detail) {
            log_message('error', __FILE__.':'.__LINE__.' user_detail get_user_detail_by_uid error, uid='.$uid);
            $this->renderJson(MYSQL_ERR_CONNECT);
            return ;
        }
        if (NULL === $arr_user_detail) {
            log_message('error', __FILE__.':'.__LINE__.' user_detail not affect rows, uid='.$uid);
            $this->renderJson(MYSQL_ERR_INSERT);
            return ;
        }*/

        // TODO: need ip
        $user_token = $this->create_token($uid, '');
        if (false === $user_token) {
            log_message('error', __METHOD__.' create_token failed, uid='.strval($uid));
            //$this->renderJson(TOKEN_CREATE_ERR);
            //return ;
        }
        // return info
        $response['errno'] = 0;
        $response['data'] = array(
            'uid'   => $uid,
            'token' => $user_token,
        );

        $this->renderJson($response['errno'], $response['data']);
    }

    function create_token($uid, $ip = '') {
        $create_time = time();
        $invalid_time = $create_time + TOKEN_INVALID_TIMEOUT;
        $hash_str = strval($create_time).'-'.strval($uid).'-'.strval($ip).'-'.strval(rand());
        $hash_key = hash('md5', $hash_str);

        $arr_mysql_req = array(
            'uid'           => $uid,
            'hash_key'      => $hash_key,
            'create_time'   => $create_time,
            'invalid_time'  => $invalid_time,
            'ip'            => $ip,
            'is_valid'      => 1,
        );
        $ret = $this->user_token_model->add($arr_mysql_req);
        if (!$ret) {
            log_message('error', __METHOD__.' add user token error, uid='.strval($uid));
            return false;
        }

        return $hash_key;
    }
}
