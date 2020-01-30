<?php
class ping_class{
    private $_serv;
    
    public function onActive($s){
        $this->_serv = $s;
        $s->getLogger()->log("[Ping] Plugin actived!");
    }

    public function onCommand($id,$command,$args,$msg){
        if($command == "ping"){
            $reply = new class{
                public $reply;
            };
            $reply->reply = "pong!";
            $this->_serv->getClientByID($id)->qreply($msg,$reply);
        }
    }
}
