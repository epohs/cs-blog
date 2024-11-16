<?php

/**
 * 
 *
 */
class Defaults {

  
  private static $instance = null;

  private array $defaults;


  
  
  
  
  
  private function __construct() {

    $this->defaults = [
  
      "debug" => true,
      
      "public" => false,
      
      "site_name" => "My Blog",
        
      "site_root" => $this->get_base_url(),
        
      "theme" => 'default',
        
      "POSTMARK_SERVER_TOKEN" => null,
      
      "POSTMARK_SENDER_SIGNATURE" => null
    
    ];    
      
  } // __construct()
  
  
  




  /**
   * 
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
   * 
   */
  private function get_base_url(): string {

    // Determine the protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

    // Get the host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Combine protocol and host
    $baseUrl = $protocol . $host;

    // Ensure no trailing slash
    return rtrim($baseUrl, '/');

  } // get_base_url()
  







  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Defaults