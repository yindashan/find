<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//短信校验码全局配置
$config['sms']=array(
    'access_token' => 'token_sms',//应用token,用以验证正常页面的传递 array('token'=>'8080234','page'=>'http://www.baidu.com/')
    'smstpl'=>array(//短信模板
        'A'=>'亲爱的用户您好，您的验证码为：%s。请于%s分钟内完成注册。【美院帮】',
        'B'=>'亲爱的用户您好，您正在进行密码找回操作，验证码为%s,本验证码%s分钟内有效。【美院帮】',
        'C'=>'亲爱的用户您好，您正在进行绑定手机号操作，验证码为%s,本验证码%s分钟内有效。【美院帮】',
    ),
);


//畅卓短信服务
$config['changzhuo'] = array(
    /*
    'serverurl' => 'http://sms.chanzor.com:8001/sms.aspx',//接口请求地址
    'configure' => array(
        'userid' => '',//企业ID
        'account' => 'ljjzpingtai',//管理员账号
        'password' => 'YfeYXGhUSdYTy5Sw',//账号密码
    ),
     */  
    'exp_time' => 15,//为验证码过期时间,单位为分钟
    'request_time' => 1,//第二次获取验证码时间间隔,单位为分钟
    'max_count' => 1000,//用户每天允许使用短信验证次数
    'secret' => '',//回调秘钥
    'operate' => array(//短信用途，如：注册,密码找回,绑定等操作
        'reg' => 1,
        'forget' => 2,
        'binding' => 3,
    ),  
    'template'=>array(//短信模板
        'reg'=>'A',
        'forget'=>'B',
        'binding' => 'C',
    )   
);

