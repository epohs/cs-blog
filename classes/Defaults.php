<?php

/**
 * Set the default values of certain config variables.
 *
 * Each of the variables here can be overridden in the
 * config.php file in the root of the application.
 */
class Defaults {

  
  private static $instance = null;

  private array $defaults;


  
  
  
  
  
  private function __construct() {

    $this->defaults = [
  
      'debug' => true,
      
      'public' => false,
      
      'site_name' => 'My Blog',
        
      'site_root' => $this->get_base_url(),
        
      'theme' => 'default',
      
      'timezone' => 'America/New_York',  
      
      'date_format' => 'F j, Y, g:i a',
      
      // Number of DAYS the remember_me token should last.
      'remember_me_age' => 30,

      'password_min_length' => 8,
      
      // Number of MINUTES a password reset request will remain active.
      'password_reset_age' => 30,

      'send_email' => false,
        
      'POSTMARK_SERVER_TOKEN' => null,
      
      'POSTMARK_SENDER_SIGNATURE' => null
    
    ];    
      
  } // __construct()
  
  
  




  /**
   * Get a value from the defaults array by it's key.
   *
   * If no key is passed return the entire array of defaults.
   */
  public function get( ?string $key = null ): mixed {

    // Return the full array if no key is provided
    if ( $key === null ):

      return $this->defaults;
    
    endif;


    // Return the value if the key exists, or null otherwise
    return array_key_exists($key, $this->defaults) ? $this->defaults[$key] : null;

  } // get()








  /**
   * Get the root URL that the application is being served on.
   */
  private function get_base_url(): string {

    // Determine the protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

    // Get the host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Combine protocol and host
    $base_url = $protocol . $host;

    // Ensure no trailing slash
    return rtrim($base_url, '/');

  } // get_base_url()
  







  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Defaults