<?php
class botblocker_class {
    private $_server;
    private $_blocks;
    public $_level = 99999;
    public function onActive(&$sv) {
        if (!file_exists("./plugins/botblock")) {
            mkdir("./plugins/botblock");
        }
        if (!file_exists("./plugins/botblock/blocks.json")) {
            $d = [];
            file_put_contents("./plugins/botblock/blocks.json", json_encode($d));
        }
        $this->_server = $sv;
        $this->_blocks = json_decode(file_get_contents("./plugins/botblock/blocks.json"),true);
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