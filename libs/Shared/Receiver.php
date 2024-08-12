<?php

namespace Shared;
/**
 * Принимает данные из очереди
 * 
 * @author Vladimir
 */
class Receiver
{
    private $connect;
    private $channel;
    private $queue;
    private $exchange;
    private $queueName;
    private $exchangeName;
    private $host;
    private $login;
    private $password;

    public function __construct(array $broker)
    {
        $this->host = $broker['host'];
        $this->login = $broker['login'];
        $this->password = $broker['password'];
        $this->queueName = $this->generationQueueName();
        $this->exchangeName = 'exchange_fanout';
        $this->connect = $this->getAMQPConnection();
        $this->channel = $this->getChannel();
        $this->exchange = $this->setExchange();
        $this->queue = $this->setQueue();
    }

    //Генерация имени для очереди
    private function generationQueueName() 
    {
        return bin2hex(random_bytes(10)) . '_queue';
    }

    //Установка подключения
    private function getAMQPConnection()
    {
        $connect = new \AMQPConnection();
        $connect->setHost($this->host);
        $connect->setLogin($this->login);
        $connect->setPassword($this->password);
        $connect->connect();
        return $connect;
    }

    //Установка канала
    private function getChannel()
    {
        $channel = new \AMQPChannel($this->connect);
        $channel->qos(0,false);
        return $channel;
    }

    //Подключение к обменнику
    private function setExchange()
    {
        $exchange = new \AMQPExchange($this->channel);
        $exchange->setName($this->exchangeName);
        $exchange->setType(AMQP_EX_TYPE_FANOUT);
        $exchange->declareExchange();
        return $exchange;
    }

    //Создание очереди
    private function setQueue()
    {
        $queue = new \AMQPQueue($this->channel);
        $queue->setName($this->queueName);
        $queue->setFlags(AMQP_IFUNUSED | AMQP_DURABLE | AMQP_AUTODELETE);
        $queue->declareQueue();
        $queue->bind($this->exchangeName, $this->queueName);
        return $queue;
    }

    //Получение данных из очереди
    public function getMessage()
    {
        $response = null;
        $this->queue->consume(function ($data) use (&$response) {
            $response = json_decode($data->getBody(),true);
            return false;
        });
        return $response;
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connect->disconnect();
    }

}

