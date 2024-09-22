<?php

define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);


date_default_timezone_set('UTC');


session_start();


// Include the autoloader for our Composer packages.
require_once(ROOT_PATH . 'vendor/autoload.php');



// Auto-include files in the /classes/ directory
// when a class is referenced.
spl_autoload_register(function ($class) {

  include 'classes/' . $class . '.php';

});



// Include the config class
// If errors happen in this class they probably
// won't show in the browser even when in debug
// mode because haven't set error reporting yet.
$config = Config::get_instance();




// If we're in debug mode display errors
if ( $config->get('debug') ):
  
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
  
endif;






function debug_log(string $message): void {

  $config = Config::get_instance();

  if ( $config->get('debug') ):

    $file = ROOT_PATH. 'debug.log';

    file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

  endif;

} // debug_log()






// Include functions.php from the active theme
// require_once();