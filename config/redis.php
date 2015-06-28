<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$cache_redis = array();
$cache_redis['host'] = 'mhback1';
$cache_redis['port'] = 8889;
$config['cache_redis'] = $cache_redis; 

$db_redis = array();
$db_redis['host'] = 'mhback1';
$db_redis['port'] = 8888;
$config['db_redis'] = $db_redis;
