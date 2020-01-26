<?php
print_r("Zapic's bot backend v0.0.1,Now loading libraries...\n");
require_once "./libs/log4p.php";
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
            file_put_contents("./config.json", json_encode(["server_ip" => "0.0.0.0", "server_port" => 57901, "bot_token" => []]));
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
        $this->_logger->log("{$id} connected to server successfully with fd {$fd}.");
        $this->_clients->by_id[$id] = [
            "fd" => $fd,
        ];
        $this->_clients->by_fd[$fd] = [
            "id" => $id,
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
        if (!property_exists($data, "post_type")) {
            $this->respondHandler($serv, $data);
        } else {
            $this->eventHandler($serv, $data);
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
    private function eventHandler($serv, $data) {
        $this->pluginsTrigger("onEvent", $data);
        switch ($data->post_type) {
        case "message":
            $this->pluginsTrigger("onMessage", $data);
            switch ($data->message_type) {
            case "private":
                $this->pluginsTrigger("onPrivateMessage", $data);
                break;

            case "group":
                $this->pluginsTrigger("onGroupMessage", $data);
                break;

            case "discuss":
                $this->pluginsTrigger("onDiscussMessage", $data);
                break;
            }
            break;

        case "notice":
            $this->pluginsTrigger("onNotice", $data);
            switch ($data->notice_type) {
            case "group_upload":
                $this->pluginsTrigger("onGroupUpload", $data);
                break;

            case "group_admin":
                $this->pluginsTrigger("onGroupAdmin", $data);
                break;

            case "group_decrease":
                $this->pluginsTrigger("onGroupDecrease", $data);
                break;

            case "group_increase":
                $this->pluginsTrigger("onGroupIncrease", $data);
                break;

            case "group_ban":
                $this->pluginsTrigger("onGroupBan", $data);
                break;

            case "friend_add":
                $this->pluginsTrigger("onFriendAdd", $data);
                break;
            }
            break;

        case "request":
            $this->pluginsTrigger("onRequest", $data);
            switch ($data->request_type) {
            case "friend":
                $this->pluginsTrigger("onFriendRequest", $data);
                break;

            case "group":
                $this->pluginsTrigger("onGroupRequest", $data);
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
    private function respondHandler($serv, $data) {
        $this->pluginsTrigger("onRespond", $data);
        if (property_exists($data, "echo")) {
            return false;
        }
        $echo = $data->echo;
        $this->_responds[$echo] = $data;
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
            $arr[] = $args[$i];
        }
        foreach ($this->_plugins as $name => $plugin) {
            if (method_exists($plugin, $func)) {
                $r = call_user_func_array([$plugin, $func], $arr);
                if ($r === false) {break;}
            }
        }
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
                    method_exists($pl, "onActive") ? $pl->onActive($this) : null;
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

    public function newToken(int $length = 8){
        if($length < 0){
            return false;
        }
        return substr(bin2hex(random_bytes(ceil($length/2))),0,$length);
    }
}

class botClient {
    private $qq;
    private $fd;
    private $serv;

    public function __construct($fd, $qq, $serv) {
        $this->fd = $fd;
        $this->qq = $qq;
        $this->serv = $serv;
    }

    public function sendPrivateMessage($qq, $msg, $escape = false) {
        $data = [
            "action" => "send_priavte_msg",
            "params" => [
                "user_id" => $qq,
                "message" => $msg,
                "auto_escape" => $escape
            ]
        ];
        
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function sendGroupMessage($group,$msg,$escape) {
        $data = [
            "action" => "send_group_msg",
            "params" => [
                "group_id" => $group,
                "message" => $msg,
                "auto_escape" => $escape
            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data); 
    }
    public function sendDiscussMessage($discuss,$msg,$escape) {
        $data = [
            "action" => "send_discuss_message",
            "params" => [
                "discuss_id" => $discuss,
                "message" => $msg,
                "auto_escape" => $escape
            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function sendMessage($type,$user,$group,$discuss,$msg,$escape=false) {
        switch($type){
            case "private":
                if(isset($user)){
                    $ty = "user_id";
                    $id = $user;
                }else{
                    return false;
                }
            break;
            
            case "group":
                if(isset($group)){
                    $ty = "group_id";
                    $id = $group;
                }else{
                    return false;
                }
            break;

            case "discuss":
                if(isset($discuss)){
                    $ty = "discuss_id";
                    $id = $discuss;
                }else{
                    return false;
                }
            break;

            default:
                if(isset($user)){
                    $type = "private";
                    $ty = "user_id";
                    $id = $user;
                    break;
                } elseif (isset($group)){
                    $type = "group";
                    $ty = "group_id";
                    $id = $group;
                    break;
                } elseif (isset($discuss)){
                    $type = "discuss";
                    $ty = "discuss_id";
                    $id = $discuss;
                    break;
                }
                return false;
        }
        $data = [
            "action" => "send_message",
            "params" => [
                "type"=>$type,
                $ty => $id,
                "message" => $msg,
                "auto_escape" => $escape
            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function deleteMessage($msgid) {
        $data = [
            "action" => "delete_message",
            "params" => [
                "message_id" => $msgid
            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function sendLike($id, $times = 1) {
        $data = [
            "action" => "send_like",
            "params" => [
                "user_id" => $id,
                "times" => $times
            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupKick($group, $id, $reject = false) {
        $data = [
            "action" => "set_group_kick",
            "params" => [
                "group_id" => $group,
                "user_id" => $id,
                "reject_add_request" => $reject
            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupBan($group, $id, $duration = 1800) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupAnonymousBan($group, $anonymous = null, $flag = "", $duration = 1800) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupWholeBan($group, $enable = true) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupAdmin($group, $id, $enable = true) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupAnonymous($group, $enable = true) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function setGroupCard($group, $id, $card = "") {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function setGroupLeave($group, $dismiss = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function setGroupSpecialTitle($group, $id, $title, $duration = -1) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function setDiscussLeave($discuss) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function setFriendAddRequset($flag, $approve, $remark = "") {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function setGroupAddRequest($flag, $type, $approve = true, $reason = "") {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getLoginInfo() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getStrangerInfo($id, $no_cache = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getFriendList() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getGroupList() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getGroupInfo($group, $no_cache = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getGroupMemberInfo($group, $id, $no_cache) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getGroupMemberList($group) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getCookies($domain = "") {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getCSRFToken() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getCredentials($domain = "") {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getRecord($file, $outFormat, $fullpath = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getImage($file) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function canSendImage() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function canSendRecord() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getStatus() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function getVersionInfo() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function setRestartPlugin($delay = 0) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function cleanDataDir($data) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function cleanPluginLog() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }
    public function _getFriendList($flat = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _getGroupInfo($group) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _getVIPInfo($user) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _getGroupNotice($groupNotice) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _sendGroupNotice() {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _setRestart($log = false, $cache = false, $event = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _checkUpdate($auto = false) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

    public function _handleQuickOperation($context, $op) {
        $data = [
            "action" => "",
            "params" => [

            ]
        ];
        $data = json_encode($data);
        $this->serv->sendRawData($this->fd,$data);
    }

}
$s = new Main();
