<?php
class hash_class{
    private $_serv;
    public function onActive(&$s){
        $this->_serv = $s;
        $s->getLogger()->log("[Hash]Plugin actived!");
    }
    public function onCommand($id,$cmd,$args,$msg){
        if($cmd != "hash"){return true;}
        $head = $this->_serv->getCommandHead();
        $args[0] = isset($args[0]) ? $args[0] : "";
        switch($args[0]){
            case "list":
                $ltext = '';
                $list = hash_algos();
                foreach($list as $method){
                    $ltext .= $method." "; 
                }
                $this->_serv->getClientById($id)->qreply($msg,['reply' => "\nHash (v0.1-beta) 可用hash列表:\n".$ltext]);
            break;
            case "help":
                $this->_serv->getClientById($id)->qreply($msg,['reply' => "\nHash (v0.1-beta) 帮助:\n{$head}hash [algos] [text] 使用指定方法哈希一段文字.\n{$head}hash list 列出可用的哈希算法.\n{$head}hash help 显示本帮助."]);
            break;
            default:
                $list = hash_algos();
                $args[0] = strtolower($args[0]);
                if(!in_array($args[0],$list)){
                    $this->_serv->getClientById($id)->qreply($msg,['reply' => "\nHash (v0.1-beta) \n使用{$head}hash help获得更多帮助."]);
                }else{
                    $algos = strtoupper($args[0]);
                    if(!isset($args[1])){$this->_serv->getClientById($id)->qreply($msg,['reply' => "\nHash (v0.1-beta) \n使用{$head}hash {$args[0]} [text] 使用{$algos}哈希一段文字."]);break;}
                    $hashtext = hash($args[0],$args[1]);
                    $this->_serv->getClientById($id)->qreply($msg,['reply' => "\n{$algos}结果: \n{$hashtext}"]);
                }
        }
        return true;
        
    }
}