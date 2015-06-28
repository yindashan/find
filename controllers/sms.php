<?php

/**
 * 短信
 */

class Sms extends MY_Controller {

    private $request;
    private $response;
    private $sms_config;
    private $cz_config;

    function __construct() {

        parent :: __construct();

        $this->request = $this->request_array;
        $this->response = $this->response_array;

        $this->_load_config();
        $this->load->model('Sms_model');
        $this->load->library('offclient');

        $this->_set_token_check(false);
    }

    private function _load_config() {
        $this->config->load('sms', TRUE);
        $this->sms_config = $this->config->item('sms', 'sms');
        $this->cz_config = $this->config->item('changzhuo', 'sms');
    }   

    /**
     * 校验发送短信条件是否符合
     */
    private function _check_send_sms($mobile) {
        
        //校验用户一天中发送的次数
        $now_time=getdate(time());
        $ctime_yday = $now_time['year'] . $now_time['yday'];
        $user_sms_count = $this->Sms_model->get_user_sms_count($mobile, $ctime_yday);
        if(false === $user_sms_count) {
            $this->response['errno'] = MYSQL_ERR_SELECT; 
            log_message('error', __METHOD__ . ' user sms count error, mobile['.$mobile.'], errno[' . $this->response['errno']. ']');
            return false;
        }
        $max_sms_count = $this->cz_config['max_count'];
        if($user_sms_count >= $max_sms_count) {
            $this->response['errno'] = ERR_SMS_MAX_COUNT; 
            log_message('error', __METHOD__ . ' max sms count error, mobile['.$mobile.'], user_sms_count['.$user_sms_count.'], errno[' . $this->response['errno']. ']');
            return false;
        }

        //校验两次短信发送时间间隔
        $user_latest_sms = $this->Sms_model->get_user_latest_sms($mobile, $ctime_yday);
        if(false === $user_latest_sms) {
            $this->response['errno'] = MYSQL_ERR_SELECT; 
            log_message('error', __METHOD__ . ' latest sms ctime error, mobile['.$mobile.'], errno[' . $this->response['errno']. ']');
            return false;
        }
        if(is_null($user_latest_sms)) {
            $latest_ctime = 0;
        } else {
            $latest_ctime = $user_latest_sms['ctime'];
        }
        $request_time = $this->cz_config['request_time'];
        if(time() < ($latest_ctime + $request_time * 60)) {
            $this->response['errno'] = ERR_SMS_TIME_INTERVAL; 
            log_message('error', __METHOD__ . ' sms time interval error, errno[' . $this->response['errno']. ']');
            return false;
        }

        return true;

    }   

    /**
     * 发送短信
     */
    public function send() {

        if(!isset($this->request['umobile']) 
            || !isset($this->request['type'])) {
            $this->response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ . ' request error, errno[' . $this->response['errno']. ']');
            goto end;
        }
        $uid = isset($this->request['uid']) ? $this->request['uid'] : 0;
        $umobile = $this->request['umobile'];
        $type= $this->request['type'];

        //验证手机号有效
        if(false === check_mobile($umobile)) {
            $this->response['errno'] = ERR_SMS_MOBILE_ILLEGAL;
            log_message('error', __METHOD__ . ' umobile error, uid['.$uid.'] umobile['.$umobile.'] type['.$type.'] errno[' . $this->response['errno']. ']');
            goto end;
        
        }
        
        //符合条件，发送短信
        if(false !== $this->_check_send_sms($umobile)) {
            $template_arr = $this->cz_config['template'];
            $operate_arr = $this->cz_config['operate'];
            $tpls = $this->sms_config['smstpl'];

            //短信模板不存在
            if(!isset($template_arr[$type]) 
                || empty($template_arr[$type])) {
                $this->response['errno'] = ERR_SMS_TPL;
                log_message('error', __METHOD__ . ' sms tpl error, uid['.$uid.'] umobile['.$umobile.'] type['.$type.'] errno[' . $this->response['errno']. ']');
                goto end;
            }

            //获取短信模板
            $tpl_id = $template_arr[$type];
            $sms_tpl = $tpls[$tpl_id];

            //生成验证码
            $sms_code = random(4);

            //生成短信内容
            $exp_time = $this->cz_config['exp_time'];
            $sms_content = vsprintf($sms_tpl, array($sms_code, $exp_time));
            $operate = $operate_arr[$type];

            //写MySQL
            $sid = $this->_set_sms($uid, $umobile, $sms_code, $operate);
            if(false === $sid) {
                $this->response['errno'] = MYSQL_ERR_INSERT;
                log_message('error', __METHOD__ . ' set sms error, uid['.$uid.'] umobile['.$umobile.'] type['.$type.'] errno[' . $this->response['errno'] .']');
                goto end;
            }

            //发送短信todo
            $this->send_sms($umobile, $sms_content, $operate, $sid);
        }
            
        end:
        $this->renderJson($this->response['errno'], $this->response['data']);
    }   

    private function _set_sms($uid, $mobile, $verifycode, $operate) {

        $now_time = getdate(time());
        $ip = ip();
        $ip_long = myip2long($ip);

        $exp_time = $this->cz_config['exp_time'];
        $ctime_keep = $now_time[0] + 60 * $exp_time + 10;
        $ctime_year = $now_time['year'];
        $ctime_yday = $now_time['year'].$now_time['yday'];
        $ctime = $now_time[0];

        $data = array(
            'uid' => $uid,
            'mobile' => $mobile,
            'verifycode' => $verifycode,
            'operate' => $operate, //短信用途, 1:reg 2:forget
            'valid' => 1,      //默认为1，短信发送失败后更新为0
            'ip' => $ip,
            'ip_long' => $ip_long,
            'ctime_keep' => $ctime_keep,
            'ctime_year' => $ctime_year,
            'ctime_yday' => $ctime_yday,
            'ctime' => $ctime,
        );
        return $this->Sms_model->add($data);
    }   

    private function send_sms($mobile, $content, $operate, $sid) {

        $sms_data = array(
            'mobile' => $mobile,
            'content' => $content,
            'type' => $operate,
            'sid' => $sid,
        );
        $ret = $this->offclient->SendSmsEvent($sms_data);
    }

    public function verify() {

        if(!isset($this->request['mobile']) 
            || !isset($this->request['verifycode'])
            || !isset($this->request['type'])) {
            $this->response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ . ' request error, errno[' . $this->response['errno']. ']');
            goto end;
            }
        $mobile = $this->request['mobile'];
        $verifycode = $this->request['verifycode'];
        $type= $this->request['type'];
        $operate_arr = $this->cz_config['operate'];
        $operate = $operate_arr[$type];

        $sms_info = $this->Sms_model->get_info_by_verifycode($mobile, $verifycode, $operate);
        if(false === $sms_info) {
            $this->response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ . ' get sms info error, mobile['.$mobile.'] verifycode['.$verifycode.'] errno[' . $this->response['errno'] .']');
            goto end;
        }

        //验证码非法
        if(is_null($sms_info)) {
            $this->response['errno'] = ERR_SMS_VERIFYCODE_ILLEGAL;
            log_message('error', __METHOD__ . ' verifycode illegal error, mobile['.$mobile.'] verifycode['.$verifycode.'] errno[' . $this->response['errno'] .']');
            goto end;
        }

        //校验验证码是否失效
        $ctime_keep = $sms_info['ctime_keep'];
        if(time() > $ctime_keep) {
            $this->response['errno'] = ERR_SMS_VERIFYCODE_TIMEOUT;
            log_message('error', __METHOD__ . ' verifycode timeout error, mobile['.$mobile.'] verifycode['.$verifycode.'] errno[' . $this->response['errno'] .']');
            goto end;
        }

        end:
        $this->renderJson($this->response['errno'], $this->response['data']);

    }


}



/* End of file sms.php */
/* Location: ./application/controllers/sms.php */
