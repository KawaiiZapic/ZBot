# ZBot #  

## An plugins & event-driven bot powerd by Swoole & CQHttp ##  

一个插件与事件驱动的酷Q机器人后端,基于Swoole与CQHttp开发.

-------------------

## 特性 ##

* 事件驱动
* 插件拓展
* Swoole驱动
* 极度精简
* 多机器人支持

## 使用指南 ##

此程序需要Swoole v4 & PHP 7支持.  
与酷Q机器人通信使用CQHttp插件的反向WebSocket,支持对机器人进行Token鉴权.  
`config.json`:

```json
{
    "bot_token":[
        "251114153": "Token"
    ]
}
```

## 插件编写 ##

### 基础框架 ###

插件直接丢进plugins文件夹,程序会按照文件名尝试加载插件.  
`example.php`:  

```php
<?php
class example_class{

}
```

指定_level,程序将按照优先级触发插件的事件,默认为50,数值越高优先级越高.

```php
<?php
class example_class{
    public $_level= 200;
}
```

### 事件 ###

程序包括所有CQHttp插件上报的事件类型,以及自带插件激活/接收到上报数据/定时Tick事件.  
监听事件十分简单,只需简单的在插件类里添加方法即可.  
Example:

```php
<?php
class example_class{
    public function onActive($serv){
        $serv->getLogger()->log("[Example] Plugin actived!");
    }
    public function onCommand($id,$cmd,$args,$msg){

    }
    public function onFrameRecive($fd,$frame){

    }
}
```

更多监听类型请查阅源码.

在事件中返回`false`即可阻止事件冒泡.  

```php
public function onCommand($id,$cmd,$args,$msg){
    if(in_array($this->blacklist,$msg->user_id)){
        return false;
    }
}
```

### API ###

获得机器人实例后即可按照CQHttp文档调用机器人API,可以非阻塞接收返回值.
Example:

```php
<?php
class example_class{
    private $_serv;
    public function onActive(&$serv){
        $this->_serv = $serv;
    }
    public function onMessage($id,$msg){
        if($msg->message == "ljyys"){
            $respond = $this->_serv->getClientByID($id)->qreply($msg,['reply'=>'nmsl']);
            var_dump($respond);
        }
    }
}
```

详细使用方法请参考CQHttp文档+翻阅源码.

# 关于 #

这东西本来就tm是自用的所以写的很屎,但是有什么建议你提一下也没有关系的咕~  
插件也不要偷啦,基本都是自用的(