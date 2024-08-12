<?php

function autoload($className)
{
    // базовая диретория, которая является корнем автозагрузки
    $baseDir = __DIR__. DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR;
    $className = ltrim($className, '\\');
    $fileName  = '';
    $fileName .= $baseDir;
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($fileName)) {
        require $fileName;
    } else {
        throw new Exception('File not found '.$fileName);
    }
}
 
// регистрируем функцию автозагрузки
spl_autoload_register('autoload'); 