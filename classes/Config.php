<?php

class Config {
    
    
  private static $instance = null;  
  
  // Array to hold configuration variables
  private $config_vars = [];  
  
  // Config has it's own error stash because
  // This file runs before our Page class is
  // ready.  We'll grab these and display them
  // later.
  private $config_errors = [];
  
  
  
  
  
  private function __construct() {
    
    $this->init();
    
  } // __construct()
  
  
  
  
  
  
  
  
  
  // Initialize the configuration by loading the config.json file
  public function init() {

    $defaults = Defaults::get_instance();

    // Start with defaults
    $this->config_vars = $defaults->get();


    $config_path = ROOT_PATH . '/config.php';


    if ( file_exists($config_path) ):

      // Include the config file and get its return value
      $config_data = include($config_path);

      // Validate that the included file returns an array
      if (is_array($config_data)):
        
        // Merge defaults with config values (config values take precedence)
        $this->config_vars = array_merge($this->config_vars, $config_data);

      else:

        $this->add_error('Config file does not return a valid array.');

      endif;

    else:

      $this->add_error('No config file found.');

    endif;

  } // init()
  
 
 
 
  
  
  
  
  
  // Get a configuration value by key or return the entire config array
  public function get(?string $key = null) {

    if ( is_null($key) ):
      
      return $this->config_vars;

    elseif ( is_array($this->config_vars) && array_key_exists($key, $this->config_vars) ):
      
      return $this->config_vars[$key];
      
    else:
      
      
      $this->add_error(["Config key {$key} not found", 'warn']);
      
      return false;
      
      
    endif;  

  } // get()
  
  
  
  
  
  
  public function get_errors() {
    
    
    return $this->config_errors;
    
    
  } // get_errors()
  
  
  
  


  public function add_error( array|string $error ): array {

    if ( is_string($error) ):

      $error = [$error, 'error'];

    endif;
    
    $this->config_errors[] = $error;
    
    return $this->config_errors;
    
    
  } // add_error()


  
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

    
} // ::Config