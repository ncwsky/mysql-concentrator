<?php

namespace MySQLConcentrator;

class Log
{
    public $file;
    public $file_path = null;

    function __construct($file_path)
    {
        $this->file_path = $file_path;
        $this->file = fopen($this->file_path, 'a');
        if ($this->file === false) {
            throw new Exception("Unable to open {$this->file_path} for logging.");
        }
    }

    function log($str)
    {
        $timestamp = strftime("%Y-%m-%d %H:%M:%S");
        $this->write("[$timestamp] $str");
    }

    function log_backtrace($message = "")
    {
        ob_start();
        debug_print_backtrace();
        $backtrace = ob_get_contents();
        ob_end_clean();
        $this->log("$message$backtrace");
    }

    function write($str)
    {
        $result = fwrite($this->file, $str);
        if ($result === false) {
            throw new Exception("Unable to write to log file {$this->file_path}.");
        }
        $result = fflush($this->file);
        if ($result === false) {
            throw new Exception("Unable to flush log file {$this->file_path}.");
        }
    }
}
