<?php

class Auth {
    
    
  private static $instance = null;  
  

  
  
  
  private function __construct() {
    

    
  } // __construct()
  
  
  
  
  
  
  

  public function set_session_key($key, $value): void {
  
    $_SESSION[$key] = $value;
  
  } // set_session_key()
  
  
  
  
  public function get_session_key($key) {
  
    return $_SESSION[$key] ?? null;
  
  } // get_session_key()
  
  
  
  
  public function delete_session_key($key): void {
  
    unset($_SESSION[$key]);
  
  } // delete_session_key()
  
  
  
  
  public function destroy_session(): void {
  
    session_unset();
    session_destroy();
  
  } // destroy_session()
  
  
  
  
  public function regen_session(): void {
  
    session_regenerate_id(true);
  
  } // regen_session()


  
  
  
  
  
  
  
  
  
  
  
  
  
  /**
   * @todo turn args after $expiry into generic $args array
   * with defaults
   */
  public function set_cookie($name, $value, $expiry = 3600, $path = '/', $domain = '', $secure = true, $httpOnly = true): void {
    
    setcookie($name, $value, time() + $expiry, $path, $domain, $secure, $httpOnly);
  
  } // set_cookie()
  
  
  
  
  
  
  public function get_cookie($name) {
    
    return $_COOKIE[$name] ?? null;
    
  } // get_cookie()
  
  
  
  
  
  
  public function delete_cookie ($name): void {
    
    setcookie($name, '', time() - 3600, '/');
    unset($_COOKIE[$name]);
    
  } // delete_cookie()
  
  
  
  
  
  
  
  
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()
  
  
  

    
} // ::Auth