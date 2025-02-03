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



$Alerts = Alerts::get_instance();

// Include the config class.
$Config = Config::get_instance();





// If we're in debug mode display errors
if ( $Config->get('debug') ):
  
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
  
endif;





// Instantiate a few key classes so they are ready later
Database::get_instance();
User::get_instance();




// Set a Session ID variable of a predictable length
// to use for client identification in rate limiting.
if ( !Session::key_isset('id') || (Session::key_isset('id') && strlen(Session::get_key('id')) != 32) ):
  
  $new_session_id = Utils::generate_random_string(32);
  
  Session::set_key('id', $new_session_id);
  
endif;






$theme = $Config->get('theme');

$theme_functions_file = ROOT_PATH . "themes/{$theme}/functions.php";






if ( file_exists($theme_functions_file) ):

  // Include functions.php from the active theme
  // early in the load process to give this file
  // as much access as possible.
  require_once( $theme_functions_file );

endif;








$request_uri = isset($_SERVER['REQUEST_URI']) ? strval($_SERVER['REQUEST_URI']) : null;

$Routing = Routing::get_instance();








/**
 * Basic file based logging.
 */
function debug_log(string $message): void {

  $Config = Config::get_instance();

  if ( $Config->get('debug') ):

    $file = ROOT_PATH. 'debug.log';

    file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

  endif;

} // debug_log()