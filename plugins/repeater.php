<?php
class repeater_class{
    private $_serv;
    private $_logger;
    public function onActive($s){
        $this->_serv = $s;
        $this->_logger = $this->_serv->getLogger();
        $this->_logger->log('[Repeater] Plugin Active!');
    }
    public function onFrameRecive($data){

    }
    public function onMessage(){
    }
}