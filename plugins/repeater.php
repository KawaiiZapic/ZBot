<?php
class repeater_class{
    private $_serv;
    private $_repeat;
    private $_repeattime = 3;
    private $_logger;
    public function onActive(&$s){
        $this->_serv = $s;
        $this->_repeat = new class{};
        $this->_logger = $this->_serv->getLogger();
        $this->_logger->log('[Repeater] Plugin actived!');
    }
    public function onGroupMessage($id,$msg){
        $group = $msg->group_id;
        $repmsg = $msg->message;
        if(!property_exists($this->_repeat,$id)){
            $this->_repeat->$id = new class{};
        }
        if(!property_exists($this->_repeat->$id,$group)){
            $this->_repeat->$id->$group = new class{public $times = 0;public $last = null;};
        }
        if($this->_repeat->$id->$group->last !== $repmsg){
            $this->_repeat->$id->$group->times = 1;
            $this->_repeat->$id->$group->last = $repmsg;
        }else{
            $this->_repeat->$id->$group->times++;
        }
        if($this->_repeat->$id->$group->times == $this->_repeattime){
            $this->_serv->getClientByID($id)->sendGroupMessage($group,$repmsg);
        }
    }
}