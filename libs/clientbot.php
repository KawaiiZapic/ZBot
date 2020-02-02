<?php 
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
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function sendGroupMessage($group,$msg,$escape = false) {
        $data = [
            "action" => "send_group_msg",
            "params" => [
                "group_id" => $group,
                "message" => $msg,
                "auto_escape" => $escape
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function sendDiscussMessage($discuss,$msg,$escape = false) {
        $data = [
            "action" => "send_discuss_message",
            "params" => [
                "discuss_id" => $discuss,
                "message" => $msg,
                "auto_escape" => $escape
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
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
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function deleteMessage($msgid) {
        $data = [
            "action" => "delete_message",
            "params" => [
                "message_id" => $msgid
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function sendLike($id, $times = 1) {
        $data = [
            "action" => "send_like",
            "params" => [
                "user_id" => $id,
                "times" => $times
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
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
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function setGroupBan($group, $id, $duration = 1800) {
        $data = [
            "action" => "set_group_ban",
            "params" => [
                "group_id" => $group,
                "user_id" => $id,
                "duration" => $duration
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function setGroupAnonymousBan($group, $anonymous = null, $flag = "", $duration = 1800) {
        $data = [
            "action" => "set_group_anonymous_ban",
            "params" => [
                "group_id" => $group,
                "anonymous" => $anonymous,
                "flag" => $flag,
                "duration" => $duration
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function setGroupWholeBan($group, $enable = true) {
        $data = [
            "action" => "set_group_whole_ban",
            "params" => [
                "group_id" => $group,
                "enable" => $enable
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function setGroupAdmin($group, $id, $enable = true) {
        $data = [
            "action" => "set_group_admin",
            "params" => [
                "group_id" => $group,
                "user_id" => $id,
                "enable" => $enable
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function setGroupAnonymous($group, $enable = true) {
        $data = [
            "action" => "set_group_anonymous",
            "params" => [
                "group_id" => $group,
                "enable" => $enable
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function setGroupCard($group, $id, $card = "") {
        $data = [
            "action" => "set_group_card",
            "params" => [
                "group_id" => $group,
                "user_id" => $id,
                "card" => $card
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function setGroupLeave($group, $dismiss = false) {
        $data = [
            "action" => "set_group_leave",
            "params" => [
                "group_id" => $group,
                "is_dismiss" => $dismiss
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function setGroupSpecialTitle($group, $id, $title, $duration = -1) {
        $data = [
            "action" => "set_group_special_title",
            "params" => [
                "group_id" => $group,
                "user_id" => $id,
                "special_title" => $title,
                "duration" => $duration
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function setDiscussLeave($discuss) {
        $data = [
            "action" => "set_discuss_leave",
            "params" => [
                "discuss_id" => $discuss
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function setFriendAddRequset($flag, $approve, $remark = "") {
        $data = [
            "action" => "set_friend_add_request",
            "params" => [
                "flag" => $flag,
                "approve" => $approve,
                "remark" => $remark
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function setGroupAddRequest($flag, $type, $approve = true, $reason = "") {
        $data = [
            "action" => "set_group_add_request",
            "params" => [
                "flag" => $flag,
                "type" => $type,
                "approve" => $approve,
                "reason" => $reason
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getLoginInfo() {
        $data = [
            "action" => "get_login_info",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getStrangerInfo($id, $no_cache = false) {
        $data = [
            "action" => "get_stranger_info",
            "params" => [
                "user_id" => $id,
                "no_cache" => $no_cache
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getFriendList() {
        $data = [
            "action" => "get_friend_list",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getGroupList() {
        $data = [
            "action" => "get_group_list",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getGroupInfo($group, $no_cache = false) {
        $data = [
            "action" => "get_group_info",
            "params" => [
                "group_id" => $group,
                "no_cache" => $no_cache
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getGroupMemberInfo($group, $id, $no_cache) {
        $data = [
            "action" => "get_group_member_info",
            "params" => [
                "group_id" => $group,
                "user_id" => $id,
                "no_cache" => $no_cache
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getGroupMemberList($group) {
        $data = [
            "action" => "get_group_member_list",
            "params" => [
                "group-id" => $group
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getCookies($domain = "") {
        $data = [
            "action" => "get_cookies",
            "params" => [
                "domain" => $domain
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getCSRFToken() {
        $data = [
            "action" => "get_csrf_token",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getCredentials($domain = "") {
        $data = [
            "action" => "get_credentials",
            "params" => [
                "domain" => $domain
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getRecord($file, $outFormat, $fullpath = false) {
        $data = [
            "action" => "get_record",
            "params" => [
                "file" => $file,
                "out_format" => $outFormat,
                "full_path" => $fullpath
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getImage($file) {
        $data = [
            "action" => "get_image",
            "params" => [
                "file" => $file
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function canSendImage() {
        $data = [
            "action" => "can_send_image",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function canSendRecord() {
        $data = [
            "action" => "can_send_record",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getStatus() {
        $data = [
            "action" => "get_status",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function getVersionInfo() {
        $data = [
            "action" => "get_version_info",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function setRestartPlugin($delay = 0) {
        $data = [
            "action" => "set_restart_plugin",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function cleanDataDir($data) {
        $data = [
            "action" => "clean_data_dir",
            "params" => [
                "data_dir" => $data
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function cleanPluginLog() {
        $data = [
            "action" => "clean_plugin_log",
            "params" => []
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }
    public function _getFriendList($flat = false) {
        $data = [
            "action" => "_get_friend_list",
            "params" => [
                "flat" => $flat
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _getGroupInfo($group) {
        $data = [
            "action" => "_get_group_info",
            "params" => [
                "group_id" => $group
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _getVIPInfo($id) {
        $data = [
            "action" => "_get_vip_info",
            "params" => [
                "user_id" => $id
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _getGroupNotice($group) {
        $data = [
            "action" => "_get_group_notice",
            "params" => [
                "group_id" => $group
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _sendGroupNotice($group,$title,$content) {
        $data = [
            "action" => "_send_group_notice",
            "params" => [
                "group_id" => $group,
                "title" => $title,
                "content" => $content
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _setRestart($log = false, $cache = false, $event = false) {
        $data = [
            "action" => "_set_restart",
            "params" => [
                "clean_log" => $log,
                "clean_cache" => $cache,
                "clean_event" => $event
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _checkUpdate($auto = false) {
        $data = [
            "action" => ".check_update",
            "params" => [
                "automatic" => $auto
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function _handleQuickOperation($context, $op) {
        $data = [
            "action" => ".handle_quick_operation",
            "params" => [
                "context" => $context,
                "operation" => $op
            ]
        ];
        return $this->serv->requestAPI($this->fd,$data);
    }

    public function qreply($context,$op){
        $this->_handleQuickOperation($context,$op);
    }
}