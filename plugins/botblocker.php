<?php
class botblocker_class {
    private $_server;
    private $_blocks;
    public $_level = 99999;
    private $_datadir;
    public function onActive(&$sv) {
        $this->_datadir = $sv->getMainDir()."/botblocker";
        if (!file_exists($this->_datadir."")) {
            mkdir($this->_datadir."");
        }
        if (!file_exists($this->_datadir."/blocks.json")) {
            $d = [];
            file_put_contents($this->_datadir."/blocks.json", json_encode($d));
        }
        $this->_server = $sv;
        $this->_blocks = json_decode(file_get_contents($this->_datadir."/blocks.json"),true);
        $this->_server->getLogger()->log('[Bot blocker] Plugin actived!');
    }

    public function onCommand($id,$cmd,$args,$msg){
        if(in_array($msg->user_id,$this->_blocks)){
            return false;
        }
    }

    public function onMessage($id,$msg){
        if(in_array($msg->user_id,$this->_blocks)){
            return false;
        }
    }
}