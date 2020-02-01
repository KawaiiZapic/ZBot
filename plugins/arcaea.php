<?php
class arcaea_class {
    private $_server;
    public function onActive($sv) {
        if (!file_exists("./plugins/arcaea")) {
            mkdir("./plugins/arcaea");
        }
        if (!file_exists("./plugins/arcaea/accounts.json")) {
            $d = new class {public $by_qq;public $by_aid;};
            $d->by_qq = new class {};
            $d->by_aid = new class {};
            file_put_contents("./plugins/arcaea/accounts.json", json_encode($d));
        }
        $this->_server = $sv;
    }
    public function onCommand($id, $cmd, $args, $msg) {
        if ($cmd == "arcaea") {
            $args[0] = isset($args[0]) ? $args[0] : null;
            switch ($args[0]) {
            case "bind":
                if (!isset($args[1])) {
                    $head = $this->_server->getCommandHead();
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\nArcaea Score Searcher(v0.1-beta):\n{$head}arcaea bind <ArcaeaID> 绑定您的Arcaea到当前QQ"]);
                    break;
                }
                $aid = $args[1];
                $qq = $msg->user_id;
                if (!preg_match('/^[0-9]{9}$/', $aid)) {
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n无效的Arcaea id {$aid}."]);
                    break;
                }
                if ($this->getArcId($qq) !== false) {
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n此QQ帐号已经绑定Arcaea帐号,如需解绑请联系Master."]);
                    break;
                }
                if ($this->getQQid($aid) !== false) {
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n此Arcaea帐号已经与其他QQ帐号绑定,如需申诉解绑请联系Master."]);
                    break;
                }
                $info = $this->getUserinfoById($aid);
                if (!$info) {
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n无效的Arcaea id {$aid}."]);
                } else {
                    $this->addArcId($aid, $qq);
                    $infotext = $this->createInfoText($info);
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\nArcaea ID {$aid}已经与QQ {$msg->user_id}绑定.\n获得到的Arcaea玩家信息:\n{$infotext}"]);
                }
                break;
            case "recent":
                if (isset($args[1])) {
                    if (preg_match('/^\[CQ:at,qq=(\d*))\]$/', $args[1], $matchs)) {
                        $aid = $this->getArcId($matchs[1]);
                    } else {
                        $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\nArcaea Score Searcher(v0.1-beta):\n{$head}arcaea recent [@someone]({$head}recent [@someone]) 查询某人或者自己最近一次游玩记录."]);
                        break;
                    }
                } else {
                    $aid = $this->getArcId($msg->user_id);
                }
                if (!$aid) {
                    $head = $this->_server->getCommandHead();
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n还没有绑定Arcaea ID!\请使用{$head}arcaea bind <Arcaea ID>绑定Arcaea ID."]);
                    break;
                }
                $i = 0;
                $info = false;
                while (!$info && $i < 5) {
                    $info = $this->getUserinfoById($aid);
                    $i++;
                }
                if (!$info) {
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n暂时未能获取到最近游玩信息,请稍后再试."]);
                    break;
                }
                $m = $this->createInfoText($info);
                $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n{$m}"]);
                break;
            case "help":
                $head = $this->_server->getCommandHead();
                $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\nArcaea Score Searcher(v0.1-beta):\n{$head}arcaea bind <ArcaeaID> 绑定您的Arcaea到当前QQ\n{$head}arcaea recent [@someone]({$head}recent [@someone]) 查询某人或者自己最近一次游玩记录.\n{$head}arcaea help 显示本帮助."]);
                break;
            default:
                $head = $this->_server->getCommandHead();
                $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\nArcaea Score Searcher(v0.1-beta):\n使用{$head}arcaea help 获得更多信息"]);
            }
        }
        if ($cmd == "recent") {
            if (isset($args[0])) {
                if (preg_match('/^\[CQ:at,qq=(\d*)\]$/', $args[0], $matchs)) {
                    $aid = $this->getArcId($matchs[1]);
                } else {
                    $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\nArcaea Score Searcher(v0.1-beta):\n{$head}arcaea recent [@someone]({$head}recent [@someone]) 查询某人或者自己最近一次游玩记录."]);
                    return true;
                }
            } else {
                $aid = $this->getArcId($msg->user_id);
            }
            if (!$aid) {
                $head = $this->_server->getCommandHead();
                $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n还没有绑定Arcaea ID!\n请使用{$head}arcaea bind <Arcaea ID>绑定Arcaea ID."]);
                return true;
            }
            $i = 0;
            $info = false;
            while (!$info && $i < 5) {
                $info = $this->getUserinfoById($aid);
                $i++;
            }
            if (!$info) {
                $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n暂时未能获取到最近游玩信息,请稍后再试."]);
                return true;
            }
            $m = $this->createInfoText($info);
            $this->_server->getClientByID($id)->qreply($msg, ["reply" => "\n{$m}"]);
        }
    }

    public function addArcId($aid, $qq) {
        $data = json_decode(file_get_contents("./plugins/arcaea/accounts.json"));
        $data->by_aid->$aid = new class {};
        $data->by_aid->$aid->qq = $qq;
        $data->by_qq->$qq = new class {};
        $data->by_qq->$qq->aid = $aid;
        file_put_contents('./plugins/arcaea/accounts.json', json_encode($data));
    }

    public function getArcId($qq) {
        $data = json_decode(file_get_contents("./plugins/arcaea/accounts.json"));
        return property_exists($data->by_qq, $qq) ? $data->by_qq->$qq->aid : false;
    }

    public function getQQId($aid) {
        $data = json_decode(file_get_contents("./plugins/arcaea/accounts.json"));
        return property_exists($data->by_qq, $aid) ? $data->by_qq->$aid->qq : false;
    }

    private function getUserinfoById($id) {
        $cli = new Co\http\Client("arc.estertion.win", 616, true);
        $ret = $cli->upgrade("/");
        if (!$ret) {return false;}
        $cli->push("{$id} userinfo");
        $i = 0;
        while ($i < 3) {
            $i++;
            $s = $cli->recv();
            if (!$s->data) {break;}
            if ($s->data == "bye") {break;}
            if ($s->data == "queried") {continue;}
            if ($s->data == "invalid id") {$r = false;
                break;}
            if (preg_match("/error/", $s->data)) {$r = false;
                break;}
            $r[] = json_decode(brotli_uncompress($s->data));
        }
        $cli->close();
        return $r;
    }

    public function createInfoText($info) {
        $nick = $info[1]->data->name;
        $songid = $info[1]->data->recent_score[0]->song_id;
        $song = property_exists($info[0]->data->$songid, "jp") ? $info[0]->data->$songid->jp : $info[0]->data->$songid->en;
        $score = $info[1]->data->recent_score[0]->score;
        $pure = $info[1]->data->recent_score[0]->perfect_count;
        $spure = $info[1]->data->recent_score[0]->shiny_perfect_count;
        $far = $info[1]->data->recent_score[0]->near_count;
        $lost = $info[1]->data->recent_score[0]->miss_count;
        $cleartype = $info[1]->data->recent_score[0]->clear_type;
        $play_ptt = round($info[1]->data->recent_score[0]->rating, 2);
        $recenttime = $this->second2duration(time() - round($info[1]->data->recent_score[0]->time_played / 1000));
        $diffl = $info[1]->data->recent_score[0]->difficulty;
        $diffc = $info[1]->data->recent_score[0]->constant;
        $ptt = $info[1]->data->rating / 100;
        $aid = $info[1]->data->user_code;
        switch ($diffl) {
        case 0:
            $diffl = "Past";
            break;
        case 1:
            $diffl = "Present";
            break;
        case 2:
            $diffl = "Future";
            break;
        default:
            $diffl = "Unknown";
        }
        switch ($cleartype) {
        case 0:
            $cleartype = "Track Lost";
            break;
        case 1:
            $cleartype = "Normal Clear";
            break;
        case 2:
            $cleartype = "Full Recall";
            break;
        case 3:
            $cleartype = "Pure Memory";
            break;
        case 4:
            $cleartype = "Easy Clear";
            break;
        case 5:
            $cleartype = "Hard Clear";
        default:
            $cleartype = "Unknown";
        }
        $msg = "用户名: {$nick}\nArcaea ID: {$aid}\nPTT: {$ptt}\n最近一次游玩:\n    时间: {$recenttime}前\n    歌曲: {$song}\n    难度: {$diffl}({$diffc})\n    分数: {$score} ({$cleartype})\n    游玩结果: {$play_ptt}\n    Pure: {$pure} (+{$spure})\n    Far: {$far}\n    Lost: {$lost}";
        return $msg;
    }
    public function second2duration($seconds) {
        $duration = '';
        $seconds = (int) $seconds;
        if ($seconds <= 0) {
            return $duration;
        }
        list($day, $hour, $minute, $second) = explode(' ', gmstrftime('%j %H %M %S', $seconds));
        $day -= 1;
        if ($day > 0) {
            $duration .= (int) $day . '天';
        }
        if ($hour > 0) {
            $duration .= (int) $hour . '小时';
        }
        if ($minute > 0) {
            $duration .= (int) $minute . '分钟';
        }
        if ($second > 0) {
            $duration .= (int) $second . '秒';
        }
        return $duration;
    }
}
