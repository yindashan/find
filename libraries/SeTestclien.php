<?php
require_once __DIR__."/Thrift/ClassLoader/ThriftClassLoader.php";

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol as TBinaryProtocol;  
use Thrift\Transport\TSocket as TSocket;  
use Thrift\Transport\TFramedTransport as TFramedTransport;  

class SeTestclient{

    private $socket = null;
    private $transport = null;
    private $protocol = null;
    private $client = null;

    function __construct() {
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Thrift', __DIR__);
        $loader->registerDefinition('se', realpath(__DIR__.'/..').'/service/' );
        $loader->register();
    }

    function __destruct() {
        //$this -> dis_connect();
    }

    /**
    * @Theme  : 
    * @Return : boolean
    */
    public function connect() {
        $this->socket = new TSocket('mhback1', 9529);
        $this->socket->setSendTimeout(1000);
        $this->socket->setRecvTimeout(1000);

        $this->transport = new TFramedTransport($this->socket);
        $this->protocol = new TBinaryProtocol($this->transport);
        $this->client = new \se\SeServerClient($this->protocol); 
        $this->transport->open(); 
    }
    public function dis_connect() {
        $this->transport->close();
        $this->client = null;
        $this->protocol = null;
        $this->transport = null;
        $this->socket = null;
    }

    public function search($para) {
        try {
            $request = new \se\SeRequest();
            $request->query = isset($para['wd']) ? $para['wd'] : '';
            $request->pn = isset($para['pn']) ? $para['pn'] : 0;
            $request->rn = isset($para['rn']) ? $para['rn'] : 0;
            $request->type = isset($para['type']) ? $para['type'] : 1;
            $request->catalog = isset($para['catalog']) ? $para['catalog'] : 0;
            $request->tag = is_array($para['tag']) ? $para['tag'] : array();
            $this->connect();     
            $res = $this->client->search($request);
            $this->dis_connect();
            return $res;
        } catch (Exception $e) {
            log_message('error', 'call search service error, msg['.$e-> getMessage().']');
            return false;
        }
    }

}


