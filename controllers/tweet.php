<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tweet extends MY_Controller {

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
        $this->load->model('Tweet_action_model');

        if (in_array($this->uri->segment(2), array('detail','detail_v2'))) {
            $this->_set_login_check(false);
        }
    }

    /**
     * 获取作品详情数据
     */
    function detail() {
    
        $request = $this->request_array;
        $response = $this->response_array;

        if(!isset($request['tid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $tid = $request['tid'];

        if(empty($tid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }

        $result = $this->get_tweet_detail($tid);

        //获取tweet失败
        if(false === $result) {
            $response['errno'] = STATUS_ERR_RESPONSE;
            log_message('error', __METHOD__ .':'.__LINE__.' tweet response error, tid['.$tid.'] uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        //tweet不存在
        if(empty($result)) {
            $response['errno'] = ERR_TWEET_NOT_EXIST;
            log_message('error', __METHOD__ .':'.__LINE__.' tweet not exist, tid['.$tid.'] uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        //tweet已删除
        if(isset($result['is_del']) && ($result['is_del'] == 1)) {
            $response['errno'] = ERR_TWEET_IS_DEL;
            log_message('error', __METHOD__ .':'.__LINE__.' tweet is del, tid['.$tid.'] uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        //获取点赞人
        $praise_user_list = array();
        $praise_uids = $this->Cache_model->get_zan_list($tid);
        if(false !== $praise_uids && !empty($praise_uids)) {
            foreach($praise_uids as $puid) {
                $user_info= $this->get_user_detail_by_uid($puid, array('sname', 'avatar'));
                if(false === $user_info['sname']) {
                    $user_info['sname'] = '';
                }
                if(false === $user_info['avatar']) {
                    $user_info['avatar'] = '';
                }
                $praise_user_list[] = $user_info;
            }
        }

        $result['praise']['user'] = $praise_user_list; 

         //获取点赞标识
        if (isset($request['uid'])) {
            $uid = $request['uid'];
            $zan_dict = $this->Tweet_action_model->get_tid_dianzan_dict($uid, array($tid));
            $praise_flag = $zan_dict[$tid];
            $result['praise']['flag'] = $praise_flag;
        }


        //封装整体数据
        $response['data']['content'] = $result;
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    /**
     * 获取用户帖子列表
     *
     */
    function usertweet() {

        $request = $this->request_array;
        log_message('debug', 'usertweet_request:'.json_encode($request));
        $response = $this->response_array;

        if (!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__.':'.__LINE__.' request error, key [uid] not exist. errno[' . $response['errno'] .']');
            goto end;
        }
        $uid = $request['uid'];         // 用户id
        $rn = USER_TWEET_LIST_COUNT;           // 一页返回数量, 默认20条
        $type = isset($request['type']) ? $request['type'] : 'new'; // type = 'new'新页, 'next'翻页
        //if(empty($uid) || empty($type)) {
        if(empty($type)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }

        //获取帖子ID列表
        if ('new' == $type) {
            // 首页
            $res_tid = $this->Tweet_model->get_tid_list_by_uid($uid, $rn);
        } else if ('next' == $type) {
            // 翻页
            if (!isset($request['last_tid'])) {
                $response['errno'] = STATUS_ERR_REQUEST;
                log_message('error', __METHOD__ .':'.__LINE__.' request error, key [last_tid] not exist. errno[' . $response['errno'] .']');
                goto end;
            }
            $tid = $request['last_tid'];
            $res_tid = $this->Tweet_model->get_next_tid_list_by_uid($uid, $tid, $rn);
        } else {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, key type['.$type.'] not valid. errno[' . $response['errno'] .']');
            goto end;
        }
        //获取失败
        if (false === $res_tid) {
            $response['errno'] = MYSQL_ERR_SELECT;
            log_message('error', __METHOD__ .':'.__LINE__.' get user tid list error. uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        //没有数据
        if(empty($res_tid)) {
            log_message('error', __METHOD__ .':'.__LINE__.' user tid list empty. tid['.$tid.'] uid['.$uid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        // 获取详情
        $res_content = array();
        foreach($res_tid as $item_tid) {
            $ret = $this->get_tweet_detail($item_tid['tid']);
            if(empty($ret) || empty($ret['imgs'])) {
                continue;
            }
            if (count($ret['imgs']) > 0) {
                $ret['imgs'] = $ret['imgs'][0];
            }
            if ($ret && 0 == intval($ret['is_del'])) {
                $res_content[] = $ret;  
            }   
        }

        $response['data'] = array(
            'content' => $res_content,
        );
        end:
        $this->renderJson($response['errno'], $response['data']);
    }

    private function tweet_new() {
        $request = $this->request_array;
        $response = $this->response_array;
        $result_arr = array();
        if (!isset($request['uid'])) {
            $response['errno'] = STATUS_ERR_REQUEST; 
            log_message('error', __METHOD__ .':'.__LINE__
                .' request error, key [uid] not exist. errno[' . $response['errno'] .']');
            goto end;
        }

        $uid = $request['uid'];

        //处理tweet内容中的url
        if(isset($request['content'])) {
            $content = $this->shorten_url($request['content']);
        }

        // 获取tweet id
        $tid = strval($this->uidclient->get_id());
        
        if (!$tid) {
            $response['errno'] = STATUS_ERR_UIDCLIENT;
            log_message('error', __METHOD__.':'.__LINE__
                .' get tid error, uid['.$uid.'] errno['.$response['errno'].']');
            goto end;
        }

        //发表tweet
        $data = array(
            'tid'       => $tid,
            'uid'       => $uid,
            'content'   => isset($request['content']) ? $request['content'] : "",        
            'ctime'     => time(),
            'lon'       => isset($request['lon']) ? $request['lon'] : 0,
            'lat'       => isset($request['lat']) ? $request['lat'] : 0,
            'current_poi_name'=> isset($request['current_poi_name']) ? $request['current_poi_name'] : "",
        );

        $resource = array();
        $rids = array();
        if(isset($request['imgs']) && !empty($request['imgs'])) {
            $imgs = $request['imgs'];
            $resource_imgs = json_decode($imgs, true);
            if(is_array($resource_imgs)) {
                foreach($resource_imgs as $rimg) {
                    $rid = strval($this->uidclient->get_id('resource'));
                    $rids[] = $rid;
                    $description = $rimg['content'];
                    unset($rimg['content']);
                    $resource[] = array(
                        'rid' => $rid,
                        'img' => json_encode($rimg),
                        'description' => $description,
                    );
                }    
            }
            $data['resource'] = $resource;
            
        }

        //tweet mapping
        if(!empty($rids)) {
            $data['resource_id'] = implode(',', $rids);
        }else {
            $data['resource_id'] = '';
        }

        //操作redis
        $ret = $this->Tweet_model->add($data);
        if (false === $ret) {
            $response['errno'] = REDIS_HSET_ERR;
            log_message('error', __METHOD__.':'.__LINE__.' add tweet error, tid['.$tid.'] uid['.$uid.'] errno['.$response['errno'].']');
            goto end;
        }

        //用户tweet数加1
        $this->Tweet_model->tweet_add($uid);

        // 更新用户队列
        $arr_tweet_info = array(
            'tid'   => $tid,
            'uid'   => $uid,
            'msg_type'  => 0,   // new tweet
            'timestamp' => time(),
        );
        $this->offclient->UpdateFriendQueue($arr_tweet_info);

        //请求离线模块
        $res = $this->offclient->SendNewPost($data);

        $result_arr = array('tid' => $tid);
        $response['data'] = $result_arr;
        end:
            $this->renderJson($response['errno'], $response['data']);
    }

    function tweet_share() {
        $request = $this->request_array;
        $response = $this->response_array;

        $uid = $request['uid'];
        if (empty($uid)) {//不传uid的话，没必要入库
            log_message('error', __METHOD__ .':'.__LINE__.'no uid');
            goto end;
        }
        $tid = intval($request['tid']);

        if (empty($tid)) {
            $response['errno'] = STATUS_ERR_REQUEST;
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }

        $ret_tweet_info = $this->Tweet_model->get_tweet_info($tid);
        if($ret_tweet_info) {
            $owneruid = $ret_tweet_info['uid'];
        }else {
            $owneruid = 0;
        } 

        $data = array(
            'uid' => $uid,
            'tid' => $tid,
            'action_type' => 3,
            'ctime' => time(),
            'owner_id' => $owneruid,
        );

        $ret = $this->Tweet_action_model->add($data);

        if (false === $ret) {
            $response['errno'] = MYSQL_ERR_INSERT;
            log_message('error', __METHOD__.':'.__LINE__. ' zan add error, uid['.$uid.'] tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;

        }
        end:
            $this->renderJson($response['errno'], $response['data']);

    }

    //未使用
    private function tweet_forward() {
        $request = $this->request_array;
        $response = $this->response_array;
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
        $result_arr = array('tid' => $online_tid);

        end:
            $this->renderJson($response['errno'], $result_arr);
    }

    private function tweet_delete() {
        $request = $this->request_array;
        $response = $this->response_array;
        $result_arr = array();
        if (!isset($request['tid'])) {
            $response['errno'] = STATUS_ERR_REQUEST; 
            log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
            goto end;
        }
        $tid = $request['tid'];

        $tweet = $this->Tweet_model->get_tweet_info($tid);
        if (false === $tweet) {
            $response['errno'] = REDIS_ERR_OP; 
            log_message('error', __METHOD__ .':'.__LINE__.' get tweet info error, tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if (1 == intval($tweet['is_del'])) {
            log_message('error', __METHOD__ .':'.__LINE__.' tweet already del, tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }
        if($this->_uid !== $tweet['uid']) {
            $response['errno'] = ERR_USER_ILLEGAL; 
            log_message('error', __METHOD__ .':'.__LINE__.' user illegal, uid['.$this->_uid.'] tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        }

        $data = array(
            'is_del' => 1,    
        );
        //更新库里is_del字段
        $res = $this->Tweet_model->update_by_tid_uid($tid, $uid, $data);

        if (false === $res) {
            $response['errno'] = MYSQL_ERR_UPDATE; 
            log_message('error', __METHOD__ .':'.__LINE__.' tweet delete error, tid['.$tid.'] errno[' . $response['errno'] .']');
            goto end;
        } else if (0 < $res) {
            //操作redis,设置删除状态
            $this->Tweet_model->tweet_del($tid);

            //操作redis,用户tweet减一
            $this->Tweet_model->tweet_cancel($tweet['uid']);
        }

        end:
            $this->renderJson($response['errno']);
    }

    /**
     * 用户作品相关
     */
    function operate() {
        $request = $this->request_array;
        $type = $request['type'];
        if ('new' === $type) {
            $this->tweet_new();
        } elseif($type == 'forward') {
            $this->tweet_forward();
        } elseif($type == 'delete') {
            $this->tweet_delete();
        }
    }
    
    
    /**
     * 获取用户相册列表
     *
     */
    function phototweet() {
    
    	$request = $this->request_array;
    	log_message('debug', 'phototweet_request:'.json_encode($request));
    	$response = $this->response_array;
    
    	if (!isset($request['uid'])) {
    		$response['errno'] = STATUS_ERR_REQUEST;
    		log_message('error', __METHOD__.':'.__LINE__.' request error, key [uid] not exist. errno[' . $response['errno'] .']');
    		goto end;
    	}
    	$uid = $request['uid'];         // 用户id
    	$rn = USER_TWEET_LIST_COUNT;           // 一页返回数量, 默认20条
    	$type = isset($request['type']) ? $request['type'] : 'new'; // type = 'new'新页, 'next'翻页
    	//if(empty($uid) || empty($type)) {
    	if(empty($type)) {
    		$response['errno'] = STATUS_ERR_REQUEST;
    		log_message('error', __METHOD__ .':'.__LINE__.' request error, errno[' . $response['errno'] .']');
    		goto end;
    	}
    
    	//获取帖子ID列表
    	if ('new' == $type) {
    		// 首页
    		$res_tid = $this->Tweet_model->get_tid_list_by_uid($uid, $rn);
    	} else if ('next' == $type) {
    		// 翻页
    		if (!isset($request['last_tid'])) {
    			$response['errno'] = STATUS_ERR_REQUEST;
    			log_message('error', __METHOD__ .':'.__LINE__.' request error, key [last_tid] not exist. errno[' . $response['errno'] .']');
    			goto end;
    		}
    		$tid = $request['last_tid'];
    		$res_tid = $this->Tweet_model->get_next_tid_list_by_uid($uid, $tid, $rn);
    	} else {
    		$response['errno'] = STATUS_ERR_REQUEST;
    		log_message('error', __METHOD__ .':'.__LINE__.' request error, key type['.$type.'] not valid. errno[' . $response['errno'] .']');
    		goto end;
    	}
    	//获取失败
    	if (false === $res_tid) {
    		$response['errno'] = MYSQL_ERR_SELECT;
    		log_message('error', __METHOD__ .':'.__LINE__.' get user tid list error. uid['.$uid.'] errno[' . $response['errno'] .']');
    		goto end;
    	}
    
    	//没有数据
    	if(empty($res_tid)) {
    		log_message('error', __METHOD__ .':'.__LINE__.' user tid list empty. tid['.$tid.'] uid['.$uid.'] errno[' . $response['errno'] .']');
    		goto end;
    	}
    
    	// 获取详情
    	$res_content = array();
    	foreach($res_tid as $item_tid) {
    		$ret = $this->get_tweet_detail($item_tid['tid']);
    		if(empty($ret) || empty($ret['imgs'])) {
    			continue;
    		}
    		if (count($ret['imgs']) > 0) {
    			$ret['imgs'] = $ret['imgs'][0];
    		}
    		if ($ret && 0 == intval($ret['is_del'])) {
    			// 日期：2015年07月11日
    			$day_ts = date("Y年m月d日", $ret['ctime']);
    			if (!isset($res_content[$day_ts])) {
    				$res_content[$day_ts] = array();
    			}
    			$res_content[$day_ts][] = array(
											'uid' => $ret['uid'],
											'tid' => $ret['tid'],
											'sname' => $ret['sname'],
											'avatar' => $ret['avatar'],
											'resource_id' => $ret['resource_id'],
											'ctime' => $ret['ctime'],
											'intro' => $ret['intro'],
											'content' => $ret['content'],
											'img_url' => $ret['imgs']['n']['url'],
										);
    		}
    	}
    
    	$response['data'] = array(
			'content' => $res_content,
    	);
    	end:
    	$this->renderJson($response['errno'], $response['data']);
    }

}



/* End of file tweet.php */
/* Location: ./application/controllers/tweet.php */  
