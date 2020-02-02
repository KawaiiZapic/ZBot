<?php
print_r("Zapic's bot backend v0.0.1,Now loading libraries...\n");
require_once "./libs/log4p.php";
require_once "./libs/clientbot.php";
class Main {
    private $_server;
    private $_config;
    private $_logger;
    private $_plugins;
    private $_clients;
    private $_responds;

    /**
     * 构造函数,初始化服务器以及加载插件
     *
     */
    public function __construct() {
        if (!file_exists("./config.json")) {
            file_put_contents("./config.json", json_encode(["server_ip" => "0.0.0.0", "server_port" => 57901,"command_head"=>"/", "bot_token" => []]));
        }
        $this->_logger = new log4p();
        $this->_logger->log("Now loading plugins...");
        $this->loadPlugins();
        $this->_logger->log("Loaded " . count($this->_plugins) . " plugin(s)");
        $this->_clients = new class
            {
            public $by_id = [];
            public $by_fd = [];
        };
        $this->_config = json_decode(file_get_contents("./config.json"));
        $this->_server = new Swoole\WebSocket\Server($this->_config->server_ip, $this->_config->server_port);
        $this->_server->on('open', function (Swoole\WebSocket\Server $server, $request) {
            $this->connectHandler($server, $request);
        });
        $this->_server->on('message', function (Swoole\WebSocket\Server $server, $frame) {
            $this->frameHandler($server, $frame);
        });
        $this->_server->on('close', function ($ser, $fd) {
            $this->closeHandler($ser, $fd);
        });
        $this->_server->on('request', function ($request, $response) {
        });
        $this->_server->on('start', function () {
            $this->_logger->log("Start to listen connect from {$this->_config->server_ip}:{$this->_config->server_port}");
        });
        $this->_server->start();
        $this->_server->tick(1000, function () {
            $this->tickHandler();
        });
    }

    /**
     * 连接事件处理
     *
     * @param Swoole\WebSocket\Server Swoole服务器实例
     * @param Swoole\Http\Request 请求实例
     *
     * @return boolean 客户端是否连接成功
     *
     */
    private function connectHandler($serv, $req) {
        $id = $req->header["x-self-id"];
        $fd = $req->fd;
        $auth = isset($req->header["Authorization"]) ? $req->header["Authorization"] : null;
        if (!is_null($auth)) {
            $t = isset($this->_config->bot_token->$id) ? $this->_config->bot_token->$id : null;
            if ("Token " . $t != $auth) {
                $this->_logger->log("{$id} authorization failed,connection closed.");
                $serv->close($fd);
                return false;
            }
        }
        $bot = new botClient($fd,$id,$this);
        $this->_logger->log("{$id} connected to server successfully with fd {$fd}.");
        $this->_clients->by_id[$id] = [
            "fd" => $fd,
            "client" => $bot
        ];
        $this->_clients->by_fd[$fd] = [
            "id" => $id,
            "client" => $bot
        ];
    }

    /**
     * 客户端断开连接事件处理
     *
     * @param Swoole\WebSocket\Server Swoole服务器实例
     * @param int 客户端连接标识符
     *
     * @return null
     *
     */
    private function closeHandler($serv, $fd) {
        $this->_logger->log("{$this->_clients->by_fd[$fd]['id']} lost connection to server.");
        unset($this->_clients->by_id[$this->_clients->by_fd[$fd]['id']]);
        unset($this->_clients->by_fd['id']);
    }

    /**
     * 数据帧处理
     *
     * @param Swoole\Websocket\Server Swoole服务器实例
     * @param Swoole\Websocket\Frame 数据帧对象
     *
     * @return null
     *
     */
    private function frameHandler($serv, $frame) {
        $this->pluginsTrigger("onFrameRecive", $frame);
        $data = json_decode($frame->data);
        $fd = $frame->fd;
        $id = $this->_clients->by_fd[$fd]['id'];
        if (!property_exists($data, "post_type")) {
            $this->respondHandler($serv,$id, $data);
        } else {
            $head = $this->_config->command_head == "/" ? "\/" : quotemeta($this->_config->command_head);
            if(property_exists($data,"message")){
                if(preg_match("/^{$head}(.+)\$/",$data->message,$match)){
                    $this->commandHandler($serv,$id,$match,$data);
                }else{
                    $this->eventHandler($serv, $id,$data);
                }
            }else{
                $this->eventHandler($serv, $id,$data);
            }
        }
    }

    /**
     * 事件上报处理
     *
     * @param Swoole\WebSocket\Server $serv Swoole服务器实例
     * @param Object 数据对象
     *
     * @return null
     *
     */
    private function eventHandler($serv, $id, $data) {
        $this->pluginsTrigger("onEvent", $id,$data);
        switch ($data->post_type) {
        case "message":
            $this->pluginsTrigger("onMessage",$id, $data);
            switch ($data->message_type) {
            case "private":
                $this->pluginsTrigger("onPrivateMessage",$id, $data);
                break;

            case "group":
                $this->pluginsTrigger("onGroupMessage",$id, $data);
                break;

            case "discuss":
                $this->pluginsTrigger("onDiscussMessage", $id,$data);
                break;
            }
            break;

        case "notice":
            $this->pluginsTrigger("onNotice",$id, $data);
            switch ($data->notice_type) {
            case "group_upload":
                $this->pluginsTrigger("onGroupUpload", $id,$data);
                break;

            case "group_admin":
                $this->pluginsTrigger("onGroupAdmin",$id, $data);
                break;

            case "group_decrease":
                $this->pluginsTrigger("onGroupDecrease",$id, $data);
                break;

            case "group_increase":
                $this->pluginsTrigger("onGroupIncrease",$id, $data);
                break;

            case "group_ban":
                $this->pluginsTrigger("onGroupBan",$id, $data);
                break;

            case "friend_add":
                $this->pluginsTrigger("onFriendAdd",$id, $data);
                break;
            }
            break;

        case "request":
            $this->pluginsTrigger("onRequest",$id, $data);
            switch ($data->request_type) {
            case "friend":
                $this->pluginsTrigger("onFriendRequest",$id, $data);
                break;

            case "group":
                $this->pluginsTrigger("onGroupRequest", $id,$data);
                break;
            }
            break;
        }
    }

    /**
     * API调用响应处理
     *
     * @param Swoole\WebSocket\Server Swoole服务器实例
     * @param Object 数据对象
     *
     * @return null
     *
     */
    private function respondHandler($serv, $id,$data) {
        $this->pluginsTrigger("onRespond",$id, $data);
        if (!property_exists($data, "echo")) {
            return false;
        }
        $echo = $data->echo;
        $this->_responds[$echo] = $data;
    }

    private function commandHandler($serv,$id,$match,$data){
        //$data->message = html_entity_decode($data->message);
        $this->_logger->log("{$data->user_id} executed command {$data->message}");
		$com = explode(" ",$match[1]);
        if(count($com) <= 0){
            $com = [$match[1]];
        }
        $command = $com[0];
        unset($com[0]);
        $args = [];
        foreach($com as $a){
            $args[] = $a;
        }
        $this->pluginsTrigger("onCommand",$id,$command,$args,$data);
    }
    /**
     * 循环Tick处理
     *
     * @return null
     */
    private function tickHandler() {
        $this->pluginsTrigger("onTick");
    }

    /**
     * 触发所有插件的某个方法
     *
     * @param string 方法名
     * @param mixed 参数
     *
     * @return null
     */
    private function pluginsTrigger($func) {
        $args = func_get_args();
        for ($i = 1; $i < count($args); $i++) {
            $arr[] = &$args[$i];
        }
        foreach ($this->_plugins as $name => $plugin) {
            if (method_exists($plugin, $func)) {
                $r = call_user_func_array([$plugin, $func], $arr);
                if ($r === false) {return false;}
            }
        }
        return true;
    }

    /**
     * 载入所有插件
     *
     * @return null
     *
     */
    private function loadPlugins() {
        if (!file_exists("./plugins")) {
            mkdir("./plugins");
            $this->_plugins = [];
            return true;
        }
        $plfiles = scandir("./plugins");
        $plugins = [];
        $_plugins = [];
        foreach ($plfiles as $plfile) {
            if (preg_match("/(.*)\.php$/", $plfile, $m)) {
                try {
                    include "./plugins/{$plfile}";
                    $class = $m[1] . "_class";
                    if (!class_exists($class)) {
                        throw new Error("Invaild plugin.(Class Not Found)");
                    }
                    $pl = new $class();
                    $level = property_exists($pl, "_level") ? (is_numeric($pl->_level) ? $pl->_level : 100) : 100;
                    $plugins[$level][$m[1]] = $pl;
                } catch (Error | Exception | ParseError $e) {
                    $this->_logger->log("Unable to load plugin \"{$m[1]}\".", LOG_LEVEL_ERROR);
                    $this->_logger->log($e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString(), LOG_LEVEL_ERROR);
                }
            }
        }
        krsort($plugins);
        foreach ($plugins as $level) {
            foreach ($level as $plname => $plugin) {
                $_plugins[$plname] = $plugin;
            }
        }
        $this->_plugins = $_plugins;
        $this->pluginsTrigger("onActive",$this);
    }

    /**
     * 获得日志记录器
     *
     * @return object
     *
     */
    public function getLogger() {
        return $this->_logger;
    }

    /**
     * 获得API返回消息
     *
     * @param any ECHO代码
     *
     * @return any 返回消息
     */
    public function getRespond($echo) {
        if (!isset($this->_responds[$echo])) {return false;}
    }

    public function getClientByID($id) {
        return $this->_clients->by_id[$id]['client'];
    }

    public function getClientByFD($fd){
        return $this->_clients->by_fd[$fd]['client'];
    }

    /**
     * 向指定客户端发生数据
     * 
     * @param int 客户端连接标识符
     * @param any 要发送的数据
     * 
     * @return boolean 发送结果
     * 
     */
    public function sendRawData($fd,$data){
        return $this->_server->push($fd,$data);
    }

    /**
     * 调用API
     * 
     * @param int 连接标识符
     * @param any 调用请求结构体
     * @param any 自定义echo,不指定则随机生成
     * @param int 指定接到回复的超时时间
     * 
     * @return any API返回数据
     */
    public function requestAPI($fd,$data,$token = null,$timeout = 3000){
        if(!$this->pluginsTrigger("onRequestAPI",$fd,$data)){
            return false;
        }
        $token = $token===null ? $this->newToken(16) : $token;
        $data['echo'] = $token;
        $data = json_encode($data);
        $this->sendRawData($fd,$data);
        return $this->fetchRespond($token,$timeout);
    }
    
    /**
     * 获得API返回数据
     * 
     * @param any 要获得的返回数据的echo
     * @param int 指定接到回复的超时时间,单位ms,默认为3000ms
     * @param boolean 是否接收清除缓存,默认为true
     * 
     * @return any 查询返回
     */
    public function fetchRespond($token,$timeout = 3000 ,$destory = true){
        $repeats = ceil($timeout / 100);
        $i = 0;
        for($i=0;$i<$repeats;$i++){
            if(!isset($this->_responds[$token])){
                Swoole\Coroutine\System::sleep(0.1);
            } else {
                $respond = $this->_responds[$token];
                if($destory){
                    unset($this->_responds[$token]);
                }
                return $respond;
            }
        }
        return false;
    }

    /**
     * 生成随机token
     * 
     * @param 指定长度,默认为8位
     * 
     * @return string 返回生成的token
     */
    public function newToken(int $length = 8){
        if($length < 0){
            return false;
        }
        return substr(bin2hex(random_bytes(ceil($length/2))),0,$length);
    }

    public function getCommandHead(){
        return $this->_config->command_head;
    }
}

$s = new Main();
