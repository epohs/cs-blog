<?php

class Auth {
    
    
  private static $instance = null;  
  

  
  
  
  private function __construct() {
    

    
  } // __construct()
  
  
  
  
  
  
    
  
  
  
  
  
  
  
  
  
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()
  
  
  

    
} // ::Auth