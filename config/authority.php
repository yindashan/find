<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//所有用户可以访问的
$authority['all'] = array(
    "hotnews",    
);

$config['user_auth'] = $authority;
