<?php

require_once 'autoload.php';
date_default_timezone_set("Asia/Almaty");
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

use Shared\RabbitClient;
use Shared\Receiver;

$config = new Shared\Config();

if (!$config->getState()) {
    print_r("Ошибка при чтении файла конфигурации");
    die(-1);
}

$queueConfig = $config->getQueueConfig();
$logConfig = $config->getLogConfig();


$settings = array(
    'log_path'=>__DIR__ . '/logs',
    'logs_max_size'=>$logConfig['file_size'],
    'logs_count_files'=>$logConfig['file_count']
);

$broker = array(
    'host' => $queueConfig['server']['host'],
    'login' => $queueConfig['server']['login'],
    'password' => $queueConfig['server']['password']
);


$receiver = new Receiver($settings);

while(true) {
    try {
        if(isset($rabbit)) {
            unset($rabbit);
        }

        $rabbit = new RabbitClient($broker);
       break;
    } catch(AMQPConnectionException $e) {
        $receiver->save(array());
    } catch(Exception $e) {
        $receiver->save(array('host'=>$logConfig['host'], 'module'=>basename(__DIR__), 'type'=>'error', 'code'=>'', 'message'=>$e->getMessage()));
    }
}

while (true) {
    try {
        $data = $rabbit->getMessage();
        $receiver->save($data);
    } catch (AMQPException $e) {
        $receiver->save(array('host'=>$logConfig['host'], 'module'=>basename(__DIR__), 'type'=>'error', 'code'=>'', 'message'=>$e->getMessage()));
    }

}
