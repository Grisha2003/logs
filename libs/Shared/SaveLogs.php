<?php

namespace Shared;
/**
 * Пишет логи в файл
 * 
 * @author Vladimir
 */
class SaveLogs
{
    private $file;
    private $maxSize;
    private $dirLogs;
    private $countFiles;

    public function __construct(array $settings)
    {
        $this->dirLogs = $settings['log_path'];
        $this->maxSize = $settings['logs_max_size'];
        $this->countFiles = $settings['logs_count_files'];
        if (!is_dir($this->dirLogs)) {
            mkdir($this->dirLogs);
        } 
    }

    //Сохраняет логи в файл
    public function save($data)
    {
        $pathAndName = $this->generatePath($data);
        $log = $this->generateLog($data);
        $this->write($pathAndName['filepath'], $log, $pathAndName['filename']);
    }

    //Генерирует имя для файла логов
    private function generateName($data)
    {
        $host = $data['host'];
        $module = $data['module'];
        $type = $data['type'];
        return "{$host}_{$module}.log";
    }

    //Генерирует путь к файлу для записи логов
    private function generatePath($data)
    {
        $filename = $this->generateName($data);
        $filepath = $this->dirLogs . '/' . $filename;
        return array('filename' => $filename, 'filepath'=>$filepath);
    }

    //Генерирует сообщение логов
    private function generateLog($data)
    {
        $timestamp = date('Y-m-d H:i:s', strtotime($data['timestamp']));
        $resultLog = sprintf("%s | %s | %s %s %s %s\n",
            $timestamp,
            $data['type'],
            $data['code'],
            $data['file'],
            $data['line'],
            is_string($data['message']) ? $data['message'] : serialize($data['message'])
        );
        return $resultLog;
    }

    //Выполняет запись в файл, если размер файла равен максимальному, вызывает функцию rotateLogs()
    private function write($filepath, $log, $filename)
    {
        $result = true;
        try {

            if (!is_resource($this->file)) {
                $this->file = fopen($filepath, 'a+');
            } 
            if (filesize($filepath) > $this->maxSize) {
                fclose($this->file);
                $this->rotateLogs($this->dirLogs, $this->countFiles, $filename);
            } else {
                if ($this->file !== false) {
                    fwrite($this->file, $log);
                    fclose($this->file);
                }
            }
        } catch (\Exception $e) {
            $result = false;
        }
        return $result;
    }
    
    //Выполняет сжатие файлов
    private function compress($sourceFile, $compressFile)
    {
        $compress = gzopen($compressFile, 'wb');
        $file = fopen($sourceFile, 'rb');
        while (!feof($file)) {
            gzwrite($compress, fread($file, 8192));
        }
        fclose($file);
        gzclose($compress);
        unlink($sourceFile);
    }

    //Выполняет ротацию логов
    private function rotateLogs($logFilePath, $maxLogFiles, $filename)
    {
        $files = array_diff(scandir($logFilePath), array('.', '..'));
        $file = "{$logFilePath}/{$filename}";
        //смещаем на +1
        for ($i = (sizeof($files) - 1); $i >= 0; $i--) {
            $ss = $i ? "." . $i . ($i > 2 ? ".tar.gz" : "") : "";
            $ds = $i > 2 ? ".tar.gz" : "";
            if (file_exists($file . $ss)) {
                rename($file . $ss, $file . "." . ($i + 1) . $ds);
            }
        }
        //Удаляем лишний файл с конца
        if (sizeof($files) >= $maxLogFiles && file_exists($file . "." . ($maxLogFiles + 1) . ".tar.gz")) {
            unlink($file . "." . ($maxLogFiles + 1) . ".tar.gz");
        }
        //Сжимаем 3-й файл
        if (file_exists($file . "." . 3)) {
            $this->compress($file . ".3", $file . ".3.tar.gz"); //Всё что выше 3 уже сжато
        }
        //Создаем файл пустышку для новых логов
        touch($file);
    }

    public function __destruct()
    {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
    }

}