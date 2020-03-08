<?php
class netwktl_class{
    private $_serv;
    public function onActive(&$s){
        $this->_serv = $s;
        $s->getLogger()->log("[Network Tool] Plugin actived!");
    }

    public function onCommand($id,$cmd,$args,$msg){
        if($cmd == "netwktl"){
            if(!isset($args[0])){$args[0]="";}
            switch($args[0]){
                case "pscan":
                    if(count($args)<3 || count($args) > 12){
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"Network Tool(v0.1-beta):\n{$head}netwktl pscan <host> <port1> <port2> ... <port10> 扫描目标主机的端口开放状态,最多10个端口."]);
                    break;
                    }
                    $st = microtime(true)*1000;
                    $host = $args[1];
                    $ports = $args;
                    unset($ports[0],$ports[1]);
                    $result = [];
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\n正在扫描..."]);
                    foreach($ports as $port){
                        if(!preg_match("/^[0-9]{1,5}$/",$port) || $port < 0 || $port > 65535){
                            $result[] = ["port"=>$port,"result"=>"无效端口"];
                            continue;
                        }
                        $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
                        $client->connect($host,$port,5);
                        switch($client->errCode){
                            case 0:
                                $result[] = ["port"=>$port,"result"=>'开放'];
                            break;

                            case 110:
                                $result[] = ["port"=>$port,"result"=>'超时'];
                            break;

                            case 111:
                                $result[] = ["port"=>$port,"result"=>'关闭'];
                            break;
                            case 112:
                            case 113:
                                $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\n服务器已关闭或者地址填写错误(err {$client->errCode} at{$host})"]);
                            break 2;
                            default:
                            $result[] = ["port"=>$port,"result"=>'未知'];
                        }
                        $client->close();
                    }
                    $et = round((microtime(true)*1000 - $st) ,2);
                    $r = "\n在{$host}的扫描结果:";
                    foreach($result as $rr){
                        $r .= "\n{$rr['port']} => {$rr['result']}";
                    }
                    $r .="\n耗时:{$et}ms";
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>$r]);
                break;
                case "nslookup":
                    if(count($args) < 2){
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"Network Tool(v0.1-beta):\n{$head}netwktl nslookup <host> [type] 查询指定域名的记录,不指定类型则尝试查询全部."]);
                    break;
                    }
                    $st = microtime(true)*1000;
                    $host = $args[1];
                    $type = isset($args[2]) ? strtoupper($args[2]) : DNS_ALL;
                    switch($type){
                        case DNS_ALL:
                        break;
                        case "A":
                            $type = DNS_A;
                        break;
                        case "CNAME":
                            $type = DNS_CNAME;
                        break;
                        case "HINFO":
                            $type = DNS_HINFO;
                        break;
                        case "MX":
                            $type = DNS_MX;
                        break;
                        case "NS":
                            $type = DNS_NS;
                        break;
                        case "PTR":
                            $type = DNS_PTR;
                        break;
                        case "SOA":
                            $type = DNS_SOA;
                        break;
                        case "TXT":
                            $type = DNS_TXT;
                        break;
                        case "AAAA":
                            $type = DNS_AAAA;
                        break;
                        case "A6":
                            $type = DNS_A6;
                        break;
                        case "SRV":
                            $type = DNS_SRV;
                        break;
                        case "NAPTR":
                            $type = DNS_NAPTR;
                        break;
                        default:
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\n无效的记录类型{$args[2]},记录类型必须是以下的其中一种:A,AAAA,A6,CNAME,MX,NS,PTR,SOA,TXT,SRV,HINFO,NAPTR"]);
                        break 2;
                    }
                    $chn = new Swoole\Coroutine\Channel();
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\n正在查询..."]);
                    go(function($chn,$host,$type){$r = dns_get_record($host,$type);$chn->push($r);},$chn,$host,$type);
                    $r = $chn->pop();
                    if(!$r){
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\n未能查询{$host}的记录,域名无效或者记录尚未生效,请稍后重试."]);
                    break;
                    }
                    $m = "\n在{$host}上查询到以下记录:";
                    foreach($r as $re){
                        $t = $re['type'];
                        $ttl = $re['ttl'];
                        switch($t){
                            case "A":
                            case "AAAA":
                                $rc = $re['ip'];
                                $p = "无";
                            break;
                            case "MX":
                                $rc = $re['target'];
                                $p = $re['pri'];
                            break;
                            case "NS":
                                $rc = $re['target'];
                                $p = "无";
                            break;
                            case "HINFO":
                                $rc = "cpu={$re['cpu']};os={$re['os']}";
                                $p = "无";
                            break;
                            case "SRV":
                                $rc = "{$re['pri']} {$re['weight']} {$re['port']} {$re['target']}";
                                $p = $p = $re['pri'];
                            break;
                            default:
                            continue 2;
                        }
                        $m .="\n类型:{$t} 值:{$rc} 优先级:{$p} TTL:{$ttl}";
                    }
                    $et = round((microtime(true)*1000 - $st) ,2);
                    if(count($r)<=0){
                        $m = "\n未在{$host}上查询到任何记录.";
                    }
                    $m .= "\n耗时:{$et}ms";
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>$m]);
                break;
                case "ping":
                    if(count($args) < 2){
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"Network Tool(v0.1-beta):\n{$head}netwktl ping <host> [timeout] Ping指定的服务器并返回延迟."]);
                    break;
                    }
                    $host = $args[1];
                    $timeout = isset($args[2]) ? $args[2] : 3000;
                    $chn = new Swoole\Coroutine\Channel();
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"\n正在测试..."]);
                    go(function($chn,$t,$host,$timeout){$r = $t->pinghost($host,$timeout);$chn->push($r);},$chn,$this,$host,$timeout);
                    $r = $chn->pop();
                    switch($r['result']){
                        case "ok":
                            $h = $host;
                            $s = $r['info']['seq'];
                            $t = $r['info']['ttl'];
                            $d = $r['info']['time'];
                            $m = "\nPING {$h} 56(84) bytes of data.\n64 bytes from {$h}: icmp_seq=1 ttl={$t} time={$d} ms\n\n--- {$h} ping statistics ---\n1 packets transmitted, 1 received, 0% packet loss, time 0ms\nrtt min/avg/max/mdev = {$d}/{$d}/{$d}/0.000 ms";
                        break;
                        case "timeout":
                            $m = "\nPING {$host} 56(84) bytes of data.\n\n--- {$host} ping statistics ---\n1 packets transmitted, 0 received, 100% packet loss, time 0ms";
                        break;
                        case "fail":
                        default:
                        $m = "\nPING: Network unreachable.";
                    }
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>$m]);
                break;
                case "mcsinfo":
                    if(count($args) < 2){
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"Network Tool(v0.1-beta):\n{$head}netwktl mcsinfo <host> [srv=false] [port=25565] 查看指定Minecraft服务器的状态."]);
                    break;
                    }
                break;
                case "help":
                    if(count($args)<3 || count($args) > 12){
                        $head = $this->_serv->getCommandHead();
                        $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"Network Tool(v0.1-beta):\n{$head}netwktl pscan <host> <port1> <port2> ... <port10> 扫描目标主机的端口开放状态,最多10个端口.\n{$head}netwktl nslookup <host> [type] 查询指定域名的记录,不指定类型则尝试查询全部.\n{$head}netwktl ping <host> [timeout] Ping指定的服务器并返回延迟.\n{$head}netwktl help 显示本帮助."]);
                    break;
                    }
                break;
                default:
                    $head = $this->_serv->getCommandHead();
                    $this->_serv->getClientByID($id)->qreply($msg,["reply"=>"Network Tool(v0.1-beta)\n使用{$head}netwktl help 获得更多信息"]);
            break;
            }
        }
    }

    private function pinghost($host,$timeout=3000){
        $st=microtime(true);
        $data='Z'.'F'.chr(0).chr(211);
        for($i=0;$i<56;$i++){
            $data.=chr(0);
        }
        $package=chr(8).chr(0);
        $package.=chr(0).chr(0);
        $package.=$data;
        $list=unpack('n*',$package);
        $length=strlen($package);
        $sum=array_sum($list);
        if($length%2){
            $tmp=unpack('C*',$package[$length-1]);
            $sum+=$tmp[1];
        }
        $sum=($sum>>16)+($sum&0xffff);
        $sum+=($sum>>16);
        $r=pack('n*',~$sum);
        $package[2]=$r[0];
        $package[3]=$r[1];
        $socket=socket_create(AF_INET,SOCK_RAW,getprotobyname('icmp'));
        if(!$socket){return ['result'=>'fail'];}
        socket_sendto($socket,$package,strlen($package),0,$host,0);
        $read   = array($socket);
        $write  = NULL;
        $except = NULL;
        $select = socket_select($read, $write, $except, 0, $timeout*1000);
        if ($select === NULL){
            socket_close($socket);
            return ['result'=>'fail'];
        }elseif ($select === 0){
            socket_close($socket);
            return ['result'=>'timeout'];
        }
        socket_recvfrom($socket, $recv, 65535, 0, $host, $port);
        $et=microtime(true);
        $t = round(($et - $st)*1000,3);
        $recv = unpack('C*', $recv);
        return ['result'=>'ok','info'=>['seq'=>$recv[28],'ttl'=>$recv[9],'size'=>count($recv)-20,'time'=>$t]];
    }
}