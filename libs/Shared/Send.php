<?php

namespace Shared;
/**
 * Публикует данные в очередь
 * 
 * @author Vladimir
 */

class Send
{
    private $host;
    private $login;
    private $password;
    private $exchangeName;
    private $exchange;
    private $connect;
    private $channel;

    public function __construct(array $broker)
    {
        $this->host = $broker['host'];
        $this->login = $broker['login'];
        $this->password = $broker['password'];
        $this->exchangeName = 'exchange_message';
        $this->connect = $this->getAMQPConnection();
        $this->channel = $this->getChannel();
        $this->exchange = $this->setExchange();
    }

    // Установка подключения
    private function getAMQPConnection()
    {
        $connect = new \AMQPConnection();
        $connect->setHost($this->host);
        $connect->setLogin($this->login);
        $connect->setPassword($this->password);
        $connect->connect();
        return $connect;
    }

    // Установка подключения к каналу канала
    private function getChannel()
    {
        $channel = new \AMQPChannel($this->connect);
        $channel->qos(0,false);
        return $channel;
    }

    // Подключение к обменнику
    private function setExchange()
    {
        $exchange = new \AMQPExchange($this->channel);
        $exchange->setName($this->exchangeName);
        $exchange->setType(AMQP_EX_TYPE_FANOUT);
        $exchange->declareExchange();
        return $exchange;
    }

    // Отправка сообщений в очередь
    public function sendInQueue($data)
    {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->exchange->publish($jsonData, $this->exchangeName);
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connect->disconnect();
    }
}

