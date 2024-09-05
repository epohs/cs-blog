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
  public function set($name, $value, $expiry = 3600, $path = '/', $domain = '', $secure = true, $httpOnly = true): void {
    
    setcookie($name, $value, time() + $expiry, $path, $domain, $secure, $httpOnly);
  
  } // set()
  
  
  
  
  
  
  public function get($name) {
    
    return $_COOKIE[$name] ?? null;
    
  } // get()
  
  
  
  
  
  
  public function delete($name): void {
    
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
