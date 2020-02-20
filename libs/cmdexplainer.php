<?php
class Command {
    private $command;
    private $args;
    private $raw;
    public function __construct($raw) {
        $this->raw = $raw;
        $chars = preg_split("//u",$raw,-1,PREG_SPLIT_NO_EMPTY);
        $lowquote = false;
        $highquote = false;
        $reserve = false;
        $splita = [];
        $splitc = 0;
        foreach($chars as $c){
            if(!isset($splita[$splitc])){$splita[$splitc]="";}
            if($reserve){
                $splita[$splitc] .= $c;
                $reserve = false;
                continue;
            }
            if($c == "\\"){
                $reserve = true;
                continue;
            }
            if($c == "'" && !$lowquote){
                if($highquote){
                    $highquote = false;
                }else{
                    $highquote = true;
                }
                continue;
            }
            if($c == '"' && !$highquote){
                if($lowquote){
                    $lowquote = false;
                }else{
                    $lowquote = true;
                }
                continue;
            }
            if($c == " " && (!$lowquote && !$highquote)){
                $splitc++;
                continue;
            }
            $splita[$splitc] .= $c;
        }
        if($lowquote || $highquote || $reserve){
            return false;
        }
        if(count($splita) < 1){
            return false;
        }
        $this->command = $splita[0];
        unset($splita[0]);
        $this->args = [];
        foreach($splita as $arg){
            $this->args[] = $arg;
        }
    }
    public function getArg($index){
        if(count($this->args) - 1 < $index){
            throw new OutOfRangeException();
        }
        return $this->args[$index];
    }
    public function getAllArgs(){
        return $this->args;
    }
    public function getCommand(){
        return $this->command;
    }
    public function getRaw(){
        return $this->raw;
    }
}