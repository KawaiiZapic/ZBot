<?php
interface ZBotPlugin{
	public function onMessage($message);
	public function onTick();
	public function onActive($serv);
}
class respond{
 public $data;
 public $id;
}