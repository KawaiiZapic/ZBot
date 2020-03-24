<?php
class mcinfo_class{

}

class ServerInfo{
    private $conn;
    public $ip;
    public $port;
    public $server;
    public function __construct($ip,int $port = 25565,$srv = null){
        $this->ip = $ip;
        $this->port = $port;
        
    }

    public static function connHostDirect($host,$port){
        $conn = fsockopen($host,$port);
        if(!$conn){throw new Exception("Failed to connect to host {$host}:{$port}");}
        return $conn;
    }  

    public static function connHostSRV($domain){
        $result = dns_get_record("_minecraft._tcp.".$domain,DNS_SRV);
        if(!$result){throw new Exception("Failed to look up SRV record.");}
        $host = null;
        $port = null;
        if(isset($result[0]['target'])){$host = $result[0]['target'];}
        if(isset($result[0]['port'])){$port = $result[0]['port'];}
        if(!$host || !$port){throw new Exception("Invaild DNS record recived.");}
        $conn = fsockopen($host,$port);
        if(!$conn){throw new Exception("Failed to connect to host {$host}:{$port}");}
        return $conn;
    }
}