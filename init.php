<?php
/**
 * Setup crucial foundations for other classes to be able to do their thing.
 * 
 */


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



// Include the config class.
$config = Config::get_instance();




// If we're in debug mode display errors
if ( $config->get('debug') ):
  
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
  
endif;




if ( !Session::key_isset('id') || (strlen(Session::get_key('id') != 32) ) ):

  $new_session_id = Utils::generate_random_string(32);
  
  Session::set_key('id', $new_session_id);
  
endif;




$theme = $config->get('theme');

$theme_functions_file = ROOT_PATH . "themes/{$theme}/functions.php";




if ( file_exists($theme_functions_file) ):

  // Include functions.php from the active theme
  // early in the load process to give this file
  // as much access as possible.
  require_once( $theme_functions_file );

endif;










/**
 * Basic file based logging.
 */
function debug_log(string $message): void {

  $config = Config::get_instance();

  if ( $config->get('debug') ):

    $file = ROOT_PATH. 'debug.log';

    file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

  endif;

} // debug_log()





