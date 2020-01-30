<?php
class reg_class{
    private $_serv;
    public function onActive($s){
        $this->_serv = &$s;
        $s->getLogger()->log("[Regex Tester] Plugin actived!");
    }
    public function onCommand($id,$cmd,$args,$msg){
        if($cmd == "reg"){
            if(count($args) >= 1){
                switch ($args[0]){
                    case "help":
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\nRegex helper(v0.1-beta)\n {$head}reg match <表达式> <字符串> 使用表达式测试字符串并返回匹配结果.\n{$head}reg replace <表达式> <替换内容> <字符串> 使用表达式测试字符串并返回替换结果.\n{$head}reg test <表达式> 测试表达式是否合法.\n{$head}reg help 显示帮助信息."]);
                    break;
                    case "match":
                        if(count($args) >= 3){
                            $r = preg_match_all($args[1],$args[2],$match);
                            if($r === false){$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n表达式无效({$args[1]})."]);return true;}
                            if($r === 0){$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n没有任何匹配({$args[1]} => {$args[2]})."]);return true;}
                            $p = print_r($match,true);
                            if($r >= 1){$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n匹配到以下内容:\n{$p}"]);return true;}
                        }else{
                            $head = $this->_serv->getCommandHead();
                            $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\nRegex helper(v0.1-beta)\n {$head}reg match <表达式> <字符串> 使用表达式测试字符串并返回匹配结果."]);
                        }
                    break;
                    case "replace":
                        if(count($args) >= 4){
                            $r = preg_replace($args[1],$args[2],$args[3]);
                            if($r === NULL){$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n表达式无效({$args[1]})."]);return true;}else
                            {$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n替换结果:\n{$r}."]);return true;}
                        }else{
                            $head = $this->_serv->getCommandHead();
                            $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\nRegex helper(v0.1-beta)\n{$head}reg replace <表达式> <替换内容> <字符串> 使用表达式测试字符串并返回替换结果."]);
                        }
                    break;
                    case "test":
                        if(count($args) >= 2){
                            $r = preg_match_all($args[1]," ");
                            if($r === false){$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n表达式无效({$args[1]})."]);return true;}else{$this->_serv->getClientByID($id)->qreply($msg,["reply" => "\n表达式有效({$args[1]})."]);return true;}
                        }else{
                            $head = $this->_serv->getCommandHead();
                            $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\nRegex helper(v0.1-beta)\n{$head}reg test <表达式> 测试表达式是否合法."]);
                        }
                    break;
                    default:
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\nRegex helper(v0.1-beta)\n无效的参数\"{$args[0]}\"\n使用{$head}reg help 获得更多信息"]);
                break; 
                }
            }else{
                $head = $this->_serv->getCommandHead();
                $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\nRegex helper(v0.1-beta)\n使用{$head}reg help 获得更多信息"]);
            }
        }
    }
}
