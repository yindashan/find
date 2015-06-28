<?php

class Appmsg {

    public function call_msg($data) {

        $callback_url = 'http://api.lanjinger.com/private/message/index';

        $ch  =  curl_init (); 
        curl_setopt ( $ch ,  CURLOPT_URL ,  $callback_url );
        curl_setopt ( $ch ,  CURLOPT_HEADER ,  0 );
        curl_setopt ( $ch ,  CURLOPT_POST ,  1 );
        curl_setopt ( $ch ,  CURLOPT_POSTFIELDS ,  $data );
        curl_setopt ( $ch ,  CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec ( $ch );
        curl_close ( $ch );

        return $content;

    }


} 
