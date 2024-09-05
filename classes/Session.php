<?php

/**
 * 
 *
 */
class Session {

  
  private static $instance = null;
  
  

  
  
  
  
  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
  
  

  public function set_key($key, $value): void {
  
    $_SESSION[$key] = $value;
  
  } // set_key()
  
  
  
  
  
  
  public function get_key($key) {
  
    return $_SESSION[$key] ?? null;
  
  } // get_key()
  
  
  
  
  
  
  public function delete_key($key): void {
  
    unset($_SESSION[$key]);
  
  } // delete_key()
  
  
  
  
  
  
  public function destroy(): void {
  
    session_unset();
    session_destroy();
  
  } // destroy()
  
  
  
  
  
  
  public function regenerate(): void {
  
    session_regenerate_id(true);
  
  } // regenerate()


  

  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Session
