<?php

/**
 * 
 *
 */
class Cookie {

  
  private static $instance = null;
  
  

  
  
  
  
  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
  
  
  
  
  /**
   * @todo turn args after $expiry into generic $args array
   * with defaults
   */
  public static function set($name, $value, $expiry = 3600, array $args = []): void {
    
    
    $defaults = [
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'http_only' => true,
    ];

    // Merge passed arguments with defaults
    $args = array_merge($defaults, $args);
    
    
    
    setcookie($name, $value, time() + $expiry, $args['path'], $args['domain'], $args['secure'], $args['http_only']);
  
  } // set()
  
  
  
  
  
  
  public static function get($name) {
    
    return $_COOKIE[$name] ?? null;
    
  } // get()
  
  
  
  
  
  
  public static function delete($name): void {
    
    setcookie($name, '', time() - 3600, '/');
    unset($_COOKIE[$name]);
    
  } // delete()
  
  
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Cookie
