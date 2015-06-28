
<?php

require_once dirname(__FILE__).'/sdk.class.php';

class OSS {

    const HOST = 'oss-cn-beijing.aliyuncs.com';
    const IMG_BUCKET = 'myb-img';
    const INTERNAL_BUCKET = 'myb-internal';
    const DOMAIN = 'http://img.tianyi2000.com/';

    private $_serivce;

    function __construct(){
        $this->_service = new ALIOSS(null, null, self::HOST); 
        $this->_service->set_debug_mode(false);
    }

    private function _get_upload_name($type, $name) {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $uuid = strtoupper(md5(uniqid(rand(), true)));
        return $type.'/'.date("Y-m-d").'/'.$uuid.'.'.pathinfo($name, PATHINFO_EXTENSION);
    }

    function upload($bucket, $file, $name) {
        try {
            $ret = $this->_service->upload_file_by_file($bucket, $name, $file);
            if (false === $ret || 200 !== $ret->status) {
                return false;
            }
            return $ret->header['_info']['url'];
        } catch (Exception $e) {
            log_message('error', 'oss exception: msg['.$e->getMessage().']'); 
            return false;
        }
    } 

    function upload_img($bucket, $file, $name) {
        $url = $this->upload($bucket, $file, $name); 
        if (!$url) {
            return false; 
        }
        $ch = curl_init();
        $url = self::DOMAIN.'/'.$name;
        curl_setopt($ch, CURLOPT_URL, $url.'@info');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        if (false === $output) {
            log_message('error', 'oss: get img info error, url['.$url.']'); 
            return false;
        }
        return array('url'=>$url, 'info'=>json_decode($output, true));
    }

    function upload_tweet_pic ($file, $name) {
        $TINY_LIMIT = 200;
        $SMALL_LIMIT = 800;
        $ret = $this->upload_img(self::IMG_BUCKET, 
                                 $file, 
                                 $this->_get_upload_name('tweet', $name));  
        if (false === $ret) {
            return $ret; 
        }
        $result = array();
        $height = $ret['info']['height'];
        $width = $ret['info']['width'];
        $normal = array('url' => $ret['url'], 'w' => $width, 'h' => $height);
        $result['n'] = $normal;
        if ($height > $TINY_LIMIT) {
            $result['t'] = array(
                'url' => $ret['url'].'@'.$TINY_LIMIT.'h',
                'h' => $TINY_LIMIT,
                'w' => intval($TINY_LIMIT * $width / $height)
            ); 
        } else {
            $result['t'] = $normal; 
        }
        if ($height > $SMALL_LIMIT) {
            $result['s'] = array(
                'url' => $ret['url'].'@'.$SMALL_LIMIT.'h',
                'h' => $SMALL_LIMIT,
                'w' => intval($SMALL_LIMIT * $width / $height)
            ); 
        } else {
            $result['s'] = $normal; 
        }
        return $result;
    }

    function upload_user_pic ($file, $name) {
        $SMALL_LIMIT =  125;
        $ret = $this->upload_img(self::IMG_BUCKET, 
                                 $file, 
                                 $this->_get_upload_name('user', $name));  
        if (false === $ret) {
            return $ret;  
        }
        $result = array();
        $height = $ret['info']['height'];
        $width = $ret['info']['width'];
        $normal = array('url' => $ret['url'], 'w' => $width, 'h' => $height);
        $result['n'] = $normal;
        if ($height > $SMALL_LIMIT) {
            $result['s'] = array(
                'url' => $ret['url'].'@'.$SMALL_LIMIT.'h',
                'h' => $SMALL_LIMIT, 
                'w' => intval($SMALL_LIMIT * $width / $height)
            ); 
        } else {
            $result['s'] = $normal;
        }
        return $result;
    }

    function upload_mis_pic ($file, $name) {
        $ret = $this->upload_img(self::IMG_BUCKET, 
                                 $file, 
                                 $this->_get_upload_name('mis', $name));  
        if (false === $ret) {
            return $ret; 
        }
        $result = array();
        $height = $ret['info']['height'];
        $width = $ret['info']['width'];
        $normal = array('url' => $ret['url'], 'w' => $width, 'h' => $height);
        $result['n'] = $normal;
        return $result;
    }

    function upload_crash($file, $name) {
        return $this->upload(self::INTERNAL_BUCKET, 
                             $file, 
                             $this->_get_upload_name('crash', $name));  
    }

    function upload_log($file, $name) {
        return $this->upload(self::INTERNAL_BUCKET, 
                             $file, 
                             $this->_get_upload_name('statistic', $name));  
    }

}
