<?php

require_once 'autoload.php';

use Shared\Send;
use Share\Log;

$config = new Shared\Config();

if (!$config->getState()) {
    print_r("Ошибка при чтении файла конфигурации");
    die(-1);
}

$queueConfig = $config->getQueueConfig();
$logConfig = $config->getLogConfig();

//Настройки для работы с очередью RabbitMq
$broker = [
    'host' => $queueConfig['server']['host'],
    'login' => $queueConfig['server']['login'],
    'password' => $queueConfig['server']['password']
];

$log = new Shared\Log($broker, $logConfig['host'], basename(__DIR__), $logConfig['message_length'], '', $logConfig['mode']);

//Пример работы с логами
$request = json_decode(array('name'=>'test', 'surname'=>'test'));
$log->notice(array("Input data: " , $request)); //Отправка логов

$log->error(array("Exception to execute object proccess: ", "Error message"), "error_intrl_KRL008", "index.php", 56); //Отправка логов ошибки
