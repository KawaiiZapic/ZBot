<?php
class police_class{
    private $_serv;
    public function onActive(&$s){
	    $this->_serv=$s;
	    $s->getLogger()->log("[Police] 有困难找警察");
    }
    public function onCommand($id,$cmd,$args,$msg){
        if($cmd == "police"){
            $polices = ["🚨","🚔","👮","🚓"];
            $len = mt_rand(10,100);
            $police = '';
            for($i=0;$i<$len;$i++){
                $witch = mt_rand(0,3);
                $police .= $polices[$witch];
            }
            $this->_serv->getClientById($id)->qreply($msg,["reply"=>$police,"at_sender"=>false]);
	    }
    }
}
