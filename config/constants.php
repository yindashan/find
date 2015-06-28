<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');



define('STATUS_OK', 0);
define('STATUS_ERR_REQUEST', 1);
define('STATUS_ERR_RESPONSE', 2);

define('ERR_DEVICE_TOKEN', -1);

//图片服务相关
define('ERR_IMG_REQUEST', 10);
define('ERR_IMG_RESPONSE', 11);

//通用错误码
define('ERR_BAD_URI', 10001);
define('ERR_BAD_SIGN', 10002);
define('ERR_TOKEN_MISS', 10003);
define('ERR_TOKEN_NOT_FOUND', 10004);
define('ERR_TOKEN_EXPIRED', 10005);
define('ERR_TOKEN_FORBIDDEN', 10006);
define('ERR_USER_NOT_VERIFIED', 10007);
define('ERR_TWEET_IS_DEL', 10008);
define('ERR_OSS', 10009);
define('ERR_TWEET_NOT_EXIST', 10010);

//MYSQL操作
define('MYSQL_ERR_CONNECT', 101);
define('MYSQL_ERR_SELECT', 102);
define('MYSQL_ERR_INSERT', 103);
define('MYSQL_ERR_UPDATE', 104);
define('MYSQL_ERR_DELETE', 105);

//REDIS操作
define('REDIS_ERR_CONNECT', 201);
define('REDIS_ERR_OP', 202);
define('REDIS_HSET_ERR', 203);

//离线模块
define('STATUS_ERR_OFFCLIENT', 301);
define('STATUS_ERR_UIDCLIENT', 302);

//消息队列
define('STATUS_ERR_MSCLIENT', 401);
define('ACTION_TYPE_AT', 0);   //at推送系统消息类型

//权限
define('STATUS_ERR_SIGN', 501);
define('STATUS_ERR_AUTH', 502);

//帖子相关
define('TWEET_FORWARD_REPEAT', 601);
define('TWEET_CACHE_SECONDS', 172800);
define('TWEET_LIST_CHARACTER_SIZE', 140);
define('TWEET_COMMUNITY_LIST_COUNT', 20);
define('TWEET_HOTNEWS_LIST_COUNT', 20);

//crash相关
define('CRASH_ERR_UPLOAD_FILE', 701);

//私信相关
define('TALK_MSG_QUEUE_SIZE', 500);
define('TALK_LIST_SIZE', 50);
define('K_TALK_MSG_QUEUE', 'talk_msg_queue_');
define('K_TALK_LIST', 'talk_list_');
define('K_TALK_SESSION', 'talk_session_');

define('REC_MSG_QUEUE_KEY', 'rec_msg_queue');
define('USER_MSG_POSTFIX', 'msg_queue_');
define('USER_MSG_MAX_NUM', 10);
define('MAX_SNAME_SEED', 10000);
define('MSG_BRIEF_LEN', 50);

define('K_HOT_USER', 'rec_hot_user');

define('K_ONLINE_REC', 'online_rec_');

define('K_HOT_STREAM', 'hot_stream');
define('K_NEW_STREAM', 'fresh_stream');
define('K_TWEET', 'tweet');

define('TWEET_IMG_COUNT', 4);


//用户相关
define('USER_EXT_CACHE_SECONDS', 172800);
define('USER_ERR_UPLOAD_DATA', 801);
define('USER_ERR_PASS', 802);
define('USER_EXIST', 803);
define('USER_TWEET_LIST_COUNT', 20);
define('USER_PHONE_EXIST', 804);
define('USER_SNAME_EXIST', 805);
define('USER_PHONE_NOT_EXIST', 806);
define('USER_NOT_EXIST', 807);
define('USER_DETAIL_LACK', 808);

//短信相关
define('ERR_SMS_MAX_COUNT', 10010);
define('ERR_SMS_TIME_INTERVAL', 10011);
define('ERR_SMS_TPL', 10012);
define('ERR_SMS_VERIFYCODE_ILLEGAL', 10013);
define('ERR_SMS_VERIFYCODE_TIMEOUT', 10014);

define('ERR_SMS_MOBILE_ILLEGAL', 10020);

// token
define('TOKEN_INVALID_TIMEOUT', 604800);    // A week: 1week * 7day * 24hour * 60minute * 60second
define('TOKEN_CACHE_TIMEOUT', 691200);      // A week and a day
define('TOKEN_CREATE_ERR', 850);    

//关系相关
define('NO_FOLLOW', 0);
define('ONE_WAY_FOLLOW', 1);
define('MUTUAL_FOLLOW', 2);
define('R_ONE_WAY_FOLLOW', 3);

define('NO_FRIEND', 0);
define('IS_FRIEND', 1);

define('PRODUCT_ERR_UPLOAD', 901);

define('ACTION_TYPE_MSG', 1);   //私信推送系统消息类型

//点赞相关
define('PRAISE_USER_COUNT', 10);
define('PRAISE_LIST_COUNT', 20);
define('K_ZAN_LIST', 'zanlist_');

//评论相关
define('COMMENT_LIST_COUNT', 50);

//choose_type
define('CHOOSE_TYPE_MATERIAL', 1);
define('CHOOSE_TYPE_TWEET', 2);
define('CHOOSE_TYPE_HOT', 3);
define('CHOOSE_TYPE_FRESH', 4);
define('CHOOSE_TYPE_FOLLOW', 5);

//前缀
define('TWEET_PREFIX', 'tweet_');
define('TWEET_RESOURCE_PREFIX', 'tweet_resource_');
define('TWEET_MAPPING', 'tweet_mapping_');
define('USER_PREFIX', 'user_');
define('USER_DETAIL_PREFIX', 'user_detail_');
define('USER_INDUSTRY_PREFIX', 'user_industry_');
define('USER_EXT_PREFIX', 'userext_');
define('USER_TOKEN_PREFIX', 'usertoken_');
define('ZAN_USER_PREFIX', 'zan_user_');
define('COMMENT_USER_PREFIX', 'comment_user_');

//URL
define('TWEET_DETAIL_LANDING_PAGE', 'http://182.92.212.76/page/share');

/* End of file constants.php */
/* Location: ./application/config/constants.php */
