<?php define("LOG_LEVEL_INFO", 1); define("LOG_LEVEL_NOTICE", 2); define("LOG_LEVEL_WARN", 3); define("LOG_LEVEL_ERROR", 4); 
define("LOG_LEVEL_DEBUG", 0); class log4p {
    private $file;
    private $file_f;
    private $display_level;
    public function __construct($level=1,$file = NULL) {
        if (is_null($file)) {
            !file_exists("logs") ? mkdir("logs") : null;
            $i=1;
            while(file_exists("logs/" . date("Y-m-d") . 
"-{$i}.log")){
                $i++;
            }
            $this->file = "logs/" . date("Y-m-d") . "-{$i}.log";
        } else {
            $this->file = $file;
        }
        $this->file_f = fopen($this->file, "w");
        if (!$this->file_f) return false;
        $this->display_level = $level;
        set_error_handler (array(&$this,"err_repo_handler"));
        set_exception_handler(array(&$this,"exp_handler"));
    }
    public function err_repo_handler($errno, $errstr, $errfile, 
$errline){
        switch ($errno) {
            case E_USER_ERROR:
                $this->log("{$errstr} in 
{$errfile}:{$errline}",LOG_LEVEL_ERROR);
                return false;
            case E_USER_WARNING:
                $this->log("{$errstr} in 
{$errfile}:{$errline}",LOG_LEVEL_WARN);
                break;
            case E_USER_NOTICE:
                $this->log("{$errstr} in 
{$errfile}:{$errline}",LOG_LEVEL_NOTICE);
                break;
            default:
                $this->log("{$errstr} in 
{$errfile}:{$errline}",LOG_LEVEL_WARN);
                break;
        }  
        return true;
    }
    public function exp_handler($exp){
        $this->log("Uncaught Exception 
occurred:{$exp->getMessage()}.\n{$exp->getTraceAsString()}",LOG_LEVEL_ERROR);
    }
    public function log($msg, $type = LOG_LEVEL_INFO) {
        $resetcolor = $color = chr(27).'[0m';
        $typeid = $type;
        $time = date("H:i:s");
        switch ($type) {
            case LOG_LEVEL_INFO:
                $color = chr(27).'[0m';
                $type = "INFO";
                break;
            case LOG_LEVEL_WARN:
                $color = chr(27).'[33m';
                $type = "WARN";
                break;
            case LOG_LEVEL_NOTICE:
                $color = chr(27).'[33m';
                $type = "NOTICE";
                break;
            case LOG_LEVEL_ERROR:
                $color = chr(27).'[31m';
                $type = "ERROR";
                break;
            case LOG_LEVEL_DEBUG:
                $color = chr(27).'[0m';
                $type = "DEBUG";
                break;
            default:
                $color = chr(27).'[0m';
                $type = "INFO";
                break;
        }
        if(is_array($msg)) $msg = print_r($msg,true);
        $output = "[" . $time . "][".$type."] " . $msg;
        if($typeid >= $this->display_level){
            print_r($color.$output.$resetcolor.PHP_EOL);
        }
        fwrite($this->file_f,$output.PHP_EOL);
    }
}
