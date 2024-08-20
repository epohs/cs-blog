<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);



define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);



spl_autoload_register(function ($class) {
    include 'classes/' . $class . '.php';
});
