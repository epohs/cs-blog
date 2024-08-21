<?php

class Config {
    
    
  private static $instance = null;  
  
  // Array to hold configuration variables
  private $config_vars = [];  
  
  
  
  
  
  
  
  private function __construct() {
    
    $this->init();
    
  } // __construct()
  
  
  
  
  
  // Initialize the configuration by loading the config.json file
  public function init() {

    $config_path = ROOT_PATH . '/config.json';
    
    if (file_exists( $config_path) ):
      
      $json_content = file_get_contents($config_path);
      
      $this->config_vars = json_decode($json_content, true);

    else:
        
      // Handle error if config file does not exist
      $this->config_vars = [];
        
    endif; 
    
  } // init()
 
 
 
  
  
  // Get a configuration value by key or return the entire config array
  public function get(?string $key = null) {

    if ( is_null($key) ):
      
      return $this->config_vars;

    elseif ( array_key_exists($key, $this->config_vars) ):
      
      return $this->config_vars[$key];
      
    else:
      
      return false;
      
    endif;  

  } // get()
  
  
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

    
} // ::Config