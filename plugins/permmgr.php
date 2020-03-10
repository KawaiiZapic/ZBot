<?php
class permmgr_class{
    public $_level = 9999;
    private $_serv;
    private $_perms;
    private $_datadir;
    public function onActive(&$s){
        $this->_serv = $s;
        $this->loadPerm();
        $this->_datadir = $s->getMainDir()."/data/PermMgr";
        if(!file_exists($this->_datadir)){mkdir($this->_datadir);}
        $this->_serv->getLogger()->log("[Perm Mgr] Plugin actived!");
    }

    private function loadPerm(){
        if (!file_exists($this->_datadir."/groups.json")) {
            $d = new class {public $example_group;public $extend_group;};
            $d->example_group = new class {public $permissions;public $inher;};
            $d->extend = new class {public $permissions;public $inher;};
            $d->example_group->permissions = ['example.use','example.select','msg.*'];
            $d->example_group->inher = ["extend"];
            $d->extend->permissions = ['netwktl.ping'];
            file_put_contents($this->_datadir."/groups.json", json_encode($d));
        }
        if(!file_exists($this->_datadir."/groups.json")){

        }
    }
    public function onCommand($id,$cmd,$args,$msg){

    }
    public function checkPermByID($id){

    }
}