<?php
class messageLogger_class{
    public $_level = 9999;
    private $_serv;
    private $_logger;

    public function onActive(&$s){
        $this->_serv = $s;
        $this->_logger = $s->getLogger();
    }

    public function onMessage($id,$msg){
        switch ($msg->message_type){
            case "private":
                $this->_logger->log("[私聊][{$msg->user_id}({$msg->sender->nickname})] {$msg->message}");
            break;

            case "group":
                $card = $msg->sender->card == "" ? $msg->sender->nickname : $msg->sender->card;
                $this->_logger->log("[群 {$msg->group_id}][{$msg->user_id}({$card})] {$msg->message}");
            break;

            case 'discuss':
                $this->_logger->log("[讨论组 {$msg->discuss_id}][{$msg->user_id}({$msg->sender->nickname})] {$msg->message}");
            break;
        }
    }
}