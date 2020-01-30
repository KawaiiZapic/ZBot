<?php
class arcaea_class{
    public $_level = 50;
    private $_server;
    public $reply;
    public function onActive($sv){
        if(!file_exists("./plugins/arcaea")){
            mkdir("./plugins/arcaea");
        }
        $this->_server = $sv;
    }
    public function onCommand($id,$cmd,$args,$msg){
	    if($cmd == "recent"){
		    $info = $this->getUserinfoById($args[1]);
		if(!$info){
                    $this->_server->getClientByID($id)->qreply($msg,["reply" => "Invaild Arcaea id."]);
		} else {
		
                     $nick = $info[1]->data->name;
                     $uid = $info[1]->data->user_id;
                     $songid =$info[1]->data->recent_score[0]->song_id;
                     $lastplay = $info[0]->data->$songid->en;
                     $score = $info[1]->data->recent_score[0]->score;
                     $ptt = $info[1]->data->rating / 100;
                     $this->_server->getClientByID($id)->qreply($msg,["reply" => "Arcaea Account info find:\nName: {$nick}({$uid})\nRecent Played:{$lastplay}\nScore: {$score}\nPTT:{$ptt} "]);
                }
        }
    }
    public function onTick(){
    
    }
    
    public function addArcId($aid,$qq){
        if(file_exists("./plugins/arcaea/data.json")){
            $old = json_decode(file_get_contents("./plugins/arcaea/data.json"),true);
            if(!$old){
                 $old = ["by_aid"=>[],"by_qq"=>[]];
            }
        } else {
            $old = ["by_aid"=>[],"by_qq"=>[]];
        }
        $old["by_qq"][$qq] = $aid;
        $old["by_aid"][$aid] = $qq;
        file_put_contents("./plugins/arcaea/data.json",json_encode($old));
        return true;
    }
    
    private function getUserinfoById($id){
        $cli = new Co\http\Client("arc.estertion.win",616,true);
	       $ret = $cli->upgrade("/");
	       if (!$ret) {return false;}
        $cli->push("{$id} userinfo");
        $i=0;
	       while($i<3){
	          $i++;
		         $s = $cli->recv();
		         if(!$s->data){break;}
	          if($s->data == "bye"){break;}
	          if($s->data  == "queried"){continue;}
	          if($s->data == "invalid id"){$r = false; break;}
	          if(preg_match("/error/",$s->data)){$r=false;break;}
	          $r[] = json_decode(brotli_uncompress($s->data)); 
	       }
       $cli->close();	
	       return $r;
    }
}
