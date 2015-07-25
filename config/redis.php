<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$cache_redis = array();
// $cache_redis['host'] = 'mhback1';
// $cache_redis['port'] = 8889;
$cache_redis['host'] = 'localhost';
$cache_redis['port'] = 6379;
$config['cache_redis'] = $cache_redis; 

$db_redis = array();
// $db_redis['host'] = 'mhback1';
// $db_redis['port'] = 8888;
$db_redis['host'] = 'localhost';
$db_redis['port'] = 6379;
$config['db_redis'] = $db_redis;
