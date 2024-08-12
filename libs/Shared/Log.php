<?php

namespace Shared;
/**
 * Формирует и отправляет логи в очередь
 * 
 * @author Vladimir 
 */

class Log
{
    private $file;
    private $module;
    private $host;
    private $filter;
    private $send;
    private $allTypes = ['error', 'warning', 'notice', 'trace'];
    private $length;

    public function __construct(array $broker, string $host, string $module, $length = 0, string $file='', string $messageType = 'notice')
    {
        $this->send = new Send($broker);
        $this->file = $file;
        $this->module = $module;
        $this->host = $host;
        $this->length = $length;
        $this->filter = in_array($messageType, $this->allTypes) ? $messageType : 'notice';
    }

    // Фильтруем по типу сообщения
    private function shouldLog($type)
    {
        return in_array($type, array_slice($this->allTypes, array_search($this->filter, $this->allTypes)));
    }

    // Обрезает массивы
   private function trimArray(&$arr, $length) 
    {
        foreach ($arr as &$val) {
            if (is_array($val)) {
                $this->trimArray($val, $length);
            } elseif (is_string($val) && mb_strlen($val) > $length) {
                $val = mb_substr($val, 0, $length) . "...";
            } elseif (is_int($val) || is_double($val ) || is_float($val)) {
                $val = strval($val);
                if (mb_strlen($val)>$length) { 
                    $val = mb_substr($val, 0, $length) . "...";
                }
            }
        }

        return $arr;
    }

    // Обрезает строковые значения массива, также строки
   private function trimData($data, $length)
    {
                    
        if (is_string($data)) {
            $jsonData = json_decode($data, true);
            if ($jsonData == null) {
                if (mb_strlen($data) > $length) {
                    $data = mb_substr($data, 0, $length) . "...";
                }
            } else {
                $data = $jsonData;
            }
        }
        
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            $data = json_encode($this->trimArray($data, $length));
           
        }
        
        return $data;
    }
    
    
    
    private function collectMessage($data, $length) 
    {
        $msg = "";
        if ($length) {
            if (is_array($data)) {
                foreach($data as &$value){
                    $value = $this->trimData($value, $length);
                    $msg .= $value;
                }
            } else {
                $msg = $this->trimData($data, $length) . " ";
            }
        } 
        return $msg;
    }




    // Сохраняем данные в очередь
    public function save(string $type, $message, $code = '', string $file = '', $line = null)
    {
        if ($this->shouldLog($type)) {
            $data = [
                'timestamp'=>date('Y-m-d H:i:s'),
                'host' => $this->host,
                'module' => $this->module,
                'type' => $type,
                'message' => $message,
                'code' => $code,
                'file' => empty($file) ? $this->file : $file,
                'line' => $line
            ];

            // Отправляем в очередь
            $this->send->sendInQueue($data);
        }
    }

    
    
    
    public function trace($data, string $code = '', $file = '', $line = null)
    {   
        $collectMessage = $this->collectMessage($data, $this->length);
        $this->save('trace', $collectMessage, $code, $file, $line);
    }

    public function notice($data, string $code = '', $file = '', $line = null)
    {
        $collectMessage = $this->collectMessage($data, $this->length); 
        $this->save('notice', $collectMessage, $code, $file, $line);
    }

    public function warning($data, string $code = '', string $file = '', $line = null)
    {
        $collectMessage = $this->collectMessage($data, $this->length); 
        $this->save('warning', $collectMessage, $code, $file, $line);
    }

    public function error($data, string $code = '', string $file = '', $line = null)
    {
        $collectMessage = $this->collectMessage($data, $this->length); 
        $this->save('error', $collectMessage, $code, $file, $line);
    }

}