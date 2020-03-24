<?php
class el_class{
    private $_serv;
    private $_groups;
    private $_els=["氢","氦","锂","铍","硼","碳","氮","氧","氟","氖","钠","镁","铝","硅","磷","硫","氯","氩","钾","钙","钪","钛","钒","铬","锰","铁","钴","镍","铜","锌","镓","锗","砷","硒","溴","氪","铷","锶","钇","锆","铌","钼","锝","钌","铑","钯","银","镉","铟","锡","锑","碲","碘","氙","铯","钡","镧","铈","镨","钕","钷","钐","铕","钆","铽","镝","钬","铒","铥","镱","镥","铪","钽","钨","铼","锇","铱","铂","金","汞","铊","铅","铋","钋","砹","氡","钫","镭","锕","钍","镤","铀","镎","钚","镅","锔","锫","锎","锿","镄","钔","锘","铹","鐪","钅杜","钅喜","𬭛","钅黑","钅麦","鐽","錀","鎶","鈤","鈇","镆","鉝","钿","Uuo"];
    public function onActive(&$s){
        $this->_serv = $s;
        $s->getLogger()->log("[Elements] Plugin actived!");
    }
    public function onGroupMessage($id,$msg){
        if(!in_array($msg->message,$this->_els)){return true;}
        if(!isset($this->_groups[$msg->group_id])){
            $this->_groups[$msg->group_id] = new class{public $sttime;public $lastindex;};
            $this->_groups[$msg->group_id]->sttime = time();
            $this->_groups[$msg->group_id]->lastindex = -1;
        }
        $length = count($this->_els);
        if($this->_groups[$msg->group_id]->lastindex >= 0 && $msg->message == $this->_els[$this->_groups[$msg->group_id]->lastindex]){return true;}
        if($msg->message == $this->_els[$this->_groups[$msg->group_id]->lastindex+1]){
            if($this->_groups[$msg->group_id]->lastindex + 1 >= $length){
                $ntime = time()-$this->_groups[$msg->group_id]->sttime;
                $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"恭喜,接龙已完成!\n耗时: {$ntime}秒","at_sender"=>false]);
                $this->_groups[$msg->group_id]->lastindex = -1;
            } else {
                if($this->_groups[$msg->group_id]->lastindex == -1){$this->_groups[$msg->group_id]->sttime = time();}
                $this->_groups[$msg->group_id]->lastindex++;
            }
        } else {
            if($this->_groups[$msg->group_id]->lastindex == -1){return true;}
            $ntime = time() - $this->_groups[$msg->group_id]->sttime;
            $finish = $this->_groups[$msg->group_id]->lastindex + 1;
            $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"接龙被打断...\n已完成: {$finish}/{$length}\n耗时: {$ntime}秒\n下一个元素应该是: {$this->_els[$this->_groups[$msg->group_id]->lastindex+1]}","at_sender"=>false]);
            $this->_groups[$msg->group_id]->lastindex = -1;
        }
    }
    public function onCommand($id,$cmd,$args,$msg){
        if($cmd == "eltip"){
            if(!isset($this->_groups[$msg->group_id])){
                $this->_groups[$msg->group_id] = new class{public $sttime;public $lastindex;};
                $this->_groups[$msg->group_id]->sttime = time();
                $this->_groups[$msg->group_id]->lastindex = -1;
            }
            $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"{$this->_els[$this->_groups[$msg->group_id]->lastindex+1]}","at_sender"=>false]);
            $this->_groups[$msg->group_id]->lastindex++;
        }
    }
}