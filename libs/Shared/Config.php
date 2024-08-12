<?php

namespace Shared;

class Config {
    private $libPath,
            $rabbit,
            $rabbitNames,
            $log,
            $state;
    
    public function __construct() 
    {
        $configFile = 'config.ini';
        $config = [];
        $this->state = true;
        if (file_exists($configFile)) {
            $config = parse_ini_file($configFile, true, INI_SCANNER_TYPED);
            
        }
        if (sizeof($config)) {
            if (isset($config['general']['sharedlib'])) {
                $this->libPath = $config['general']['sharedlib'];
            } else {
                $this->state = false;
            }
            if (isset($config['queue_server']) 
                    && isset($config['queue_server']['host'])
                    && isset($config['queue_server']['port'])
                    && isset($config['queue_server']['timeout'])
                    && isset($config['queue_server']['login'])
                    && isset($config['queue_server']['password'])) {
                $this->rabbit = $config['queue_server'];
            } else {
                $this->state = false;
            }
            if (isset($config['log'])
                && isset($config['log']['host'])
                && isset($config['log']['mode'])) { 
                $this->log = $config['log'];
                if (isset($this->log['file_size'])
                && isset($this->log['file_count'])) {
                    $this->log['file_size'] = 1024*1024;
                    $this->log['file_count'] = 9;
                 }
            } else {
                $this->state = false;
            }
        } else {
            $this->state = false;
        }
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function getQueueConfig()
    {
        return ["server"=>$this->rabbit];
    }
    public function getLogConfig()
    {
        return $this->log;
    }
}
