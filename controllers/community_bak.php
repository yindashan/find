<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Community extends MY_Controller {

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

        //$this->load->model('Community_model');
        //$this->load->model('user_model');
        //$this->load->model('Mis_topic_model');
        //$this->load->model('cache_model');
        //$this->load->model('Zan_model');
        //$this->load->model('short_url_model');

        $this->request_array['pn'] = isset($this->request_array['pn']) ? $this->request_array['pn'] : 0;
        $this->request_array['rn'] = isset($this->request_array['rn']) ? $this->request_array['rn'] : 5;
	}

    function test(){
        $content = '@sname 我是 @lanjing cheshi@123';
        $this->handle_at($content);
    }

    /**
     * 讨论区列表
     */
    function home() 
    {
        log_message('error', 'community_home');
        //log_message('error', 'class' . __CLASS__ .',method'.__METHOD__);
        $this->load->model('message_queue_model');

        $request = $this->request_array;
        $response = $this->response_array;

        log_message('error', 'request:'.json_encode($request));
        $result_arr = array();
        $res_content = array();

        if(!isset($request['type']) || !isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            goto end;
        }
        $type = $request['type'];  
        $uid = $request['uid'];
        
        $pn = $request['pn'];
        $rn = $request['rn'];
        $industry = 0;

        $condition = array();
        if(isset($request['industry']) && !empty($request['industry'])) {
            $industry = $request['industry'];
            $industry_ids = $this->get_indus_by_str($industry);
            log_message('error', 'community_home_industry_id:'.json_encode($industry_ids));
        }
        log_message('error', 'uid:'.$uid);
        $topics = $this->message_queue_model->get_user_message($uid);
        log_message('error', 'community_home_topics:'.json_encode($topics));
        $count = 0;
        if(false === $topics) {
            $response['errno'] = MYSQL_ERR_SELECT;
            goto end;
        } elseif(!empty($topics)) {
            if($type == 'new') {
                //下拉刷新取最新帖子
                foreach($topics as $key => $topic) {
                    $tmp = explode('|', $topic);
                    $tid = $tmp[0];

                    //行业筛选
                    if(isset($industry_ids) && (false !== $industry_ids)) {
                        $industry_id = $industry_ids[0];
                        $topic_industry = $tmp[1];
                        $topic_industries = explode(',', $topic_industry);
                        log_message('error', 'industry_id:'.$industry_id);
                        log_message('error', 'industries:'.json_encode($topic_industries));
                        //if(intval($industry) !== 0 && intval($industry) !== intval($tmp[1])) {
                        if(intval($industry_id) !== 0 && !in_array($industry_id, $topic_industries)) {
                            log_message('error', 'continuecontinuecontinuecontinuecontinue');
                            continue;
                        }
                    }
                    //过滤已删除的帖子
                    $fields = array('is_del');
                    $fields_arr = $this->cache_model->get_tweet_fields($tid, array('is_del'));
                    log_message('error', 'fields_arr:'.json_encode($fields_arr));
                    $is_del = isset($fields_arr['is_del']) ? $fields_arr['is_del'] : 0;
                    log_message('error', 'is_del:'. $is_del);
                    if(intval($is_del) === 1) {
                        log_message('error', 'is_del:'. $is_del);
                        continue;
                    }

                    $tid_list[] = $tid;
                    if(count($tid_list) == 10) break;
                }
                /*一期只返回new
                if ($tid_list) {
                 
                //判断是增量加载还是全部加载，
                //当前最新帖子如果在获取到的帖子列表中，则为增量加载，加载的帖子为当前展示的最新帖子之后；
                //反之则全量加载(一期只返回new)
                   
                    if (isset($request['first_tid'])) {
                        $first_tid = $request['first_tid'];
                        if(false !== array_search($first_tid, $tid_list)) {
                            $key = array_search($first_tid, $tid_list);
                            log_message('error', 'key:'.$key);
                            $tid_list = array_slice($tid_list, 0, $key);
                            //$type = 'append';
                            $type = 'new';
                        }
                    }
                }
                 */
                $this->msclient->clear_red_by_uid($uid, 2, 0);
            }elseif($type == 'next') {
                //上拉获取更多帖子
                $last_tid = $request['last_tid'];
                foreach($topics as $key => $topic) {
                    $tmp = explode('|', $topic);
                    $tid = $tmp[0];
                    if((intval($industry) !== 0) 
                        && (intval($industry) !== intval($tmp[1]))
                        || (intval($last_tid) <= intval($tid))) {
                        continue;
                    }
                    //过滤已删除的帖子
                    $fields = array('is_del');
                    $fields_arr = $this->cache_model->get_tweet_fields($tid, array('is_del'));
                    log_message('error', 'fields_arr:'.json_encode($fields_arr));
                    $is_del = isset($fields_arr['is_del']) ? $fields_arr['is_del'] : 0;
                    log_message('error', 'is_del:'. $is_del);
                    if(intval($is_del) === 1) {
                        log_message('error', 'is_del:'. $is_del);
                        continue;
                    }
                    $tid_list[] = $tid;
                    if(count($tid_list) == 10) break;
                }
            }
            if(!empty($tid_list)) {
                log_message('error', 'tid_list:'.json_encode($tid_list));

                //获取点赞标识
                $zan_dict = $this->Zan_model->get_tid_dianzan_dict($uid, $tid_list);
                log_message('error', 'zan_dict:'.json_encode($zan_dict));
                
                foreach($tid_list as $k => $tid) {
                    $content = $this->get_topic_detail($tid);
                    $praise_flag = $zan_dict[$tid];
                    $content['praise']['flag'] = $praise_flag;

                    //拼接转发url
                    $forward_url = "http://app.lanjinger.com/community/detail?tid=" . $tid;
                    //$content['forward']['url'] = $this->short_url_model->generate_url($forward_url);
                    $content['forward']['url'] = $forward_url;
                    
                    //log_message('error', 'content_prise:'.json_encode($content['praise']));
                    log_message('error', 'content:'.json_encode($content));

                    $res_content[] = $content;
                    if(isset($request['industry'])) {
                        $result_arr['industry'] = $request['industry'];
                    }
                }
            }
            $response['data'] = array(
                'content' => $res_content,
                'type' => $type,    
            );
        } 
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    /**
     * 获取讨论详情数据
     */
    function detail() {
    
        $request = $this->request_array;
        $response = $this->response_array;
        log_message('error', 'req_detail:'.json_encode($request));
        $result_arr = array();

        $tid = $request['tid'];
        $uid = $request['uid'];


        //$result = $this->Community_model->get_detail_by_tid($tid);
        $result = $this->get_topic_detail($tid);
        log_message('error', 'community_detail:'.json_encode($result));
        if(isset($result['is_del']) && ($result['is_del'] == 1)) {
            //$response['errno'] = ERR_TWEET_IS_DEL;
            //goto end;
        }
        log_message('error', 'result:'.json_encode($result));
        //$result_array = $result->result_array();
        $result_array = $result;

        if(!empty($result_array)) {

            //获取点赞人
            $praise_user_list = array();
            $praise_user = $this->Zan_model->get_user_list($tid, PRAISE_USER_COUNT);
            if($praise_user) {
                foreach($praise_user as $user) {
                    $praise_user_list[] = $user['username'];
                }
            }
            log_message('error', 'praise_user:'. json_encode($praise_user));
            $result['praise']['user'] = $praise_user_list; 

            //获取点赞标识
            log_message('error', '$this->_uid:'.$this->_uid);
            $zan_dict = $this->Zan_model->get_tid_dianzan_dict($uid, array($tid));
            log_message('error', 'zan_dict:'.json_encode($zan_dict));
            $praise_flag = $zan_dict[$tid];
            $result['praise']['flag'] = $praise_flag;

            //拼接转发落地页链接
            $forward_url = "http://app.lanjinger.com/wap/community/detail?tid=" . $tid;
            //$result['forward']['url'] = $this->short_url_model->generate_url($forward_url);
            $result['forward']['url'] = $forward_url;


            //封装整体数据
            //$result_arr['content'] = $result;
            $response['data']['content'] = $result;
            end:
            $this->renderJson($response['errno'], $response['data']);
        }
    }

    /**
     * 获取用户帖子列表
     *
     */
    function usertopic() {

        $request = $this->request_array;
        log_message('error', 'usertopic_request:'.json_encode($request));
        $response = $this->response_array;

        $uid = $request['uid'];
        log_message('error', 'usertopic_uid:'.$uid);
        $pn = $request['pn'];
        $rn = $request['rn'];

        //获取用户数据
        $user_info = array();
        $user_data = $this->get_user_by_uid($uid);
        $sname = $user_data['sname'];
        $avatar= $user_data['avatar'];
        $user_info = array(
            'uid' => $uid,
            'sname' => $sname,
            'avatar' => $avatar,    
            'ukind' => isset($user_data['ukind']) ? $user_data['ukind'] : 0 ,    
            'company_job' => isset($user_data['company_job']) ? $user_data['company_job'] : '',    
        );

        //获取帖子ID列表
        if(isset($request['type']) && $request['type'] == 'next') {
            $tid = $request['last_tid'];
            $res_tid = $this->Community_model->get_next_tid_list_by_uid($uid, $tid, $rn);
        }else {
            $res_tid = $this->Community_model->get_tid_list_by_uid($uid, $rn);
        }
        log_message('error', 'usertopic_tids:'.json_encode($res_tid));
        if(empty($res_tid)) {
            goto end;
        }
        foreach($res_tid as $tids) {
            $tid = $tids['tid'];
            $res = $this->get_topic_detail($tid); 
            /*
            //过滤已删除的帖子
            if(isset($res['is_del']) && $res['is_del'] == 1) {
                continue;
            }
             */

            $res_content[] = $res;
        }

        $response['data'] = array(
            'content' => array(
                'user_info' => $user_info,
                'topic' => $res_content,
            ),    
        );
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    private function tweet_new() {
        echo 111;exit;
        $request = $this->request_array;
        log_message('error', 'tweet_new_request:'.json_encode($request));
        $response = $this->response_array;
        $result_arr = array();
 //           log_message('error', json_encode($_FILES));
        if (!isset($request['uid']) 
            || !isset($request['content'])) {
            $response['errno'] = STATUS_ERR_REQUEST; 
            goto end;
        }
        $uid = $request['uid'];

        //压测特殊处理
        if(!isset($request['press'])) {
        $img_arr = array();
        if (isset($_FILES['img'])) {
            $this->load->library('oss');
            $img = $_FILES['img'];
            for ($i = 0; $i < count($img['name']); $i++) {
                if (0 !== $img['error'][$i]) {
                    $response['errno'] = ERR_IMG_REQUEST;
                    $result_arr['err_msg'] = 'img '.$i.' upload error';
                    goto end;
                }
            }
            for ($i = 0; $i < count($img['name']); $i++) {
                $ret = $this->oss->upload_tweet_pic($img['tmp_name'][$i], 
                                                      $this->get_uuid().'.'.pathinfo($img['name'][$i], PATHINFO_EXTENSION));
                if (false !== $ret && 200 === $ret->status) {
                    $img_arr[] = $ret->header['_info']['url']; 
                } else {
                    $response['errno'] = ERR_IMG_RESPONSE; 
                    $result_arr['err_msg'] = 'img '.$i.' reupload error';
                    goto end;
                }
            }
        }
        }

        //处理帖子内容中的url
        $content = $this->shorten_url($request['content']);

        //获取行业信息
        $industry = $this->cache_model->get_user_industry($uid);
        log_message('error', 'industry:'.json_encode($industry));

        //用户发表讨论
        $data = array(
            //'uid' => $request['uid'],
            'uid' => $uid,
            'title' => isset($request['title']) ? $request['title'] : '',            
            'content' => $content,        
            'img' => isset($request['press']) ? $request['img'] : json_encode($img_arr),    
            //'industry' => $request['industry'],    
            'industry' => $industry,    
            //'catalog' => isset($request['catalog']) ? $request['catalog'] : "",    
            'ctime' => time(),    
        );  
            
        //操作线上数据库
        $online_tid = $this->Community_model->add($data);
        log_message('error', 'online_tid:'.$online_tid);
        if(!$online_tid) {
            $response['errno'] = MYSQL_ERR_INSERT;
            goto end;
        }

        //如果帖子内容包含@，推送到消息中心
        $at_sname = $this->analyse_at($content);
        if(!empty($at_sname)) {
            log_message('error', 'at_uname:-------------------------'.json_encode($at_uname));
            $to_uids = array();
            foreach($at_sname as $sname) {
        
                $user_id = $this->user_model->get_uid_by_uname($sname);
                if(!empty($user_id)) {
                    foreach($user_id as $user) {
                        $to_uids[] = $user['id'];
                    }
                }
                log_message('error', 'uids:'.json_encode($to_uids));
            }
        log_message('error', 'uid:'.json_encode($uid));
        $this->msclient->send_system_msg($uid, ACTION_TYPE_AT, $to_uids, $online_tid);
        }


        $this->cache_model->tweet_add($request['uid']);

        //请求离线模块
        $data['tid'] = $online_tid;
        $res = $this->offclient->SendNewPost($data);
        if (!$res || 0 !== $res->err_no) {
            $response['errno'] = STATUS_ERR_OFFCLIENT;
            log_message('error', 'class' . __CLASS__ .',method'.__METHOD__.'send to offhub error, tid['.$online_tid.'] msg['.$res->err_msg.']');
        }
        $result_arr = array('tid' => $online_tid);
        $response['data'] = $result_arr;
    end:
        $this->renderJson($response['errno'], $response['data']);
    }


    private function tweet_forward() {
        $request = $this->request_array;
        $response = $this->response_array;
        log_message('error', 'tweet_forward_request:'.json_encode($request));
        $result_arr = array();
        if (!isset($request['uid']) || !isset($request['parent_tid'])) {
            $response['errno'] = STATUS_ERR_REQUEST; 
            goto end;
        }
        $res = $this->Community_model->get_tweet($request['parent_tid'], 'origin_tid, catalog, industry');
        if (!$res) {
            $response['errno'] = STATUS_ERR_RESPONSE;
            goto end;
        }

        //用户转发讨论
        $data = array(
            'uid' => $request['uid'],
            'title' => isset($request['title']) ? $request['title'] : "",            
            'content' => isset($request['content']) ? $request['content'] : "",        
            'industry' => isset($request['industry']) ? $request['industry'] : $res['industry'],    
            'catalog' => isset($request['catalog']) ? $request['catalog'] : $res['catalog'],    
            'ctime' => time(),    
            'parent_tid' => $request['parent_tid'],    
            'origin_tid' => 0 == $res['origin_tid'] ? $request['parent_tid'] : $res['origin_tid'], 
        );  
        $online_tid = $this->Community_model->add($data);
        if(false === $online_tid) {
            $response['errno'] = MYSQL_ERR_INSERT; 
            goto end;
        }

        //更新cache中帖子转发数
        $this->cache_model->forward_add($request['parent_tid']);
        $this->cache_model->tweet_add($request['uid']);

        $data['tid'] = $online_tid;
        $res = $this->offclient->SendNewPost($data);
        if (!$res || 0 !== $res->err_no) {
            log_message('error', 'send to offhub error, tid['.$online_tid.']msg['.$res->err_msg.']');
            $response['errno'] = STATUS_ERR_OFFCLIENT;
        }
        $result_arr = array('tid' => $online_tid);

    end:
        $this->renderJson($response['errno'], $result_arr);
    }

    private function tweet_delete() {
        $request = $this->request_array;
        $response = $this->response_array;
        $result_arr = array();
        if (!isset($request['tid']) && !isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST; 
            goto end;
        }
        $tid = $request['tid'];
        $uid = $request['uid'];
        /*todo
        if($this->uid !== $uid) {
            $response['errno'] = ERR_USER_NOT_VERIFIED;
            goto end;
        }
         */
        log_message('error', 'delete_tid:'.$tid);
        $data = array(
            'is_del' => 1,    
        );
        //更新库里is_del字段
        $res = $this->Community_model->update_by_tid_uid($tid, $uid, $data);
        if (!$res) {
            $response['errno'] = MYSQL_ERR_UPDATE; 
            goto end;
        }
        $tweet = $this->cache_model->get_tweet_info($tid);
        log_message('error', 'commuinty_tweet_del_tweet:'.json_encode($tweet));

        //设置redis帖子删除字段
        if(isset($tweet['is_del']) && $tweet['is_del'] == 0) {
            log_message('error', 'commuinty_tweet_del-----------------');
            $this->cache_model->tweet_del($tweet['tid']);
        }
        //设置redis用户帖子数
        if ($tweet['uid']) {
            $this->cache_model->tweet_cancel($tweet['uid']);
        }
    end:
        $this->renderJson($response['errno']);
    }

    /**
     * 用户讨论相关
     */
    function topic() {
        $request = $this->request_array;
        log_message('error', json_encode($request));
        $type = $request['type'];
        if ('new' === $type) {
            $this->tweet_new();
        } elseif($type == 'forward') {
            $this->tweet_forward();
        } elseif($type == 'delete') {
            $this->tweet_delete();
        }
    }

}
