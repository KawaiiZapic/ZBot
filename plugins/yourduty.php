<?php
class yourduty_class{
    private $_serv;
    public function onActive(&$s){
        $this->_serv = $s;
        $s->getLogger()->log("[Your duty] Plugin actived!");
    }
    public function onCommand($id,$cmd,$arg,$msg){
        if($cmd != "duty" || count($arg) < 1){return true;}
        $this->_serv->getClientbyid($id)->qreply($msg,["reply"=>'那是你的事情',"at_sender"=>false]);
        Swoole\Coroutine\System::sleep(1);
        $this->_serv->getClientbyid($id)->qreply($msg,["reply"=>"你是{$arg[0]}","at_sender"=>false]);
    }
}