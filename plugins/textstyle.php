<?php 
class textstyle_class{
    public $_serv;
    private $_u0336;
    public function onActive (&$s){
        $this->_serv = $s;
        $s->getLogger()->log('[Text Style] Plugin actived');
        $this->_u0336 = json_decode('{"i":"\u0336"}',true)['i'];
    }
    public function onMessage($id,$msg){
        $k = $this->_u0336;
        $rp = $msg->message;
        if(preg_match("/~~(.+)~~/u",$rp,$match)){
            $rp = preg_replace("/./u",$k."$0".$k,$match[1]);
            $this->_serv->getClientById($id)->qreply($msg,["reply"=>$rp,"at_sender"=>false]);
        }
    }
}