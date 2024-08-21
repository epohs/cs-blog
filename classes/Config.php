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

    $config_path = ROOT_PATH . '/config.php';
    
    if ( file_exists($config_path) ):
      
      // Use output buffering to strip PHP code from config file
      ob_start();
      
      require_once($config_path);
      
      $file_content = ob_get_clean();
      
      
      $json_content = json_decode($file_content, true);
      
      
      // Test whether the json file is valie
      if ( json_last_error() === JSON_ERROR_NONE ):
      
        $this->config_vars = $json_content;
      
      endif;
        
    endif; 
    
  } // init()
 
 
 
  
  
  // Get a configuration value by key or return the entire config array
  public function get(?string $key = null) {

    if ( is_null($key) ):
      
      return $this->config_vars;

    elseif ( is_array($this->config_vars) && array_key_exists($key, $this->config_vars) ):
      
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