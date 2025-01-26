<?php

/**
 * Handle crucial configuration values used throughout this application.
 * 
 * Default values are defined in the Defaults class. User defined
 * values are set in the config.php file in the root of the application
 * and will take precedence over defaults.
 * 
 * This class also catches errors that occur during the early initialization
 * of the app, before the Page class is ready to take over alert handling.
 *
 * @internal Reconsider an Alerts class that loads early and handles all alerts.
 */
class Config {
    
    
  private static $instance = null;  
  
  // Array to hold configuration variables.
  private $config_vars = [];  
  
  // Config has it's own error stash because
  // This file runs before our Page class is
  // ready.  We'll grab these and display them
  // later.
  private $config_alerts = [];
  
  
  
  
  
  
  
  
  private function __construct() {
    
    $this->init();
    
  } // __construct()
  
  
  
  
  
  
  
  
  /**
   * Initialize the configuration by merging the default
   * config variables and those from the config.php file.
   *
   * Values from the config file override defaults.
   */
  public function init(): void {
    

    $defaults = Defaults::get_instance();

    // Start with defaults
    $this->config_vars = $defaults->get();


    $config_path = ROOT_PATH . '/config.php';


    if ( file_exists($config_path) ):
      

      // Include the config file and get its return value.
      $config_file = include($config_path);
      

      // Validate that the included file returns an array.
      if (is_array($config_file)):
        
        // Merge defaults with config file values.
        // Config file values take precedence.
        $this->config_vars = array_merge($this->config_vars, $config_file);

      else:

        $this->add_alert('Config file does not return a valid array.');

      endif;
      

    else:

      $this->add_alert('No config file found.');

    endif;
    

  } // init()
  
 
 
  
  
  
  
  
  /**
   * Get a configuration value by key or return the entire config array.
   */
  public function get(?string $key = null): mixed {

    
    if ( is_null($key) ):
      
      return $this->config_vars;

    elseif ( is_array($this->config_vars) && array_key_exists($key, $this->config_vars) ):
      
      return $this->config_vars[$key];
      
    else:
      
      // @todo this will cause an error of it's own because
      // add_alert() does not accept a level param.
      $this->add_alert(["Config key {$key} not found", 'warn']);
      
      return false;
      
      
    endif;  

    
  } // get()
  
  
  
  
  
  
  
  
  /**
   * Get all stashed alerts.
   */
  public function get_alerts() {
    
    
    return $this->config_alerts;
    
    
  } // get_alerts()
  
  
  
  


  
  
  /**
   * Add an alert to the temporary stash.
   *
   * @todo Add ability to set alert level.
   */
  public function add_alert( array|string $alert ): array {
    

    if ( is_string($alert) ):

      $alert = [$alert, 'error'];

    endif;
    
    
    $this->config_alerts[] = $alert;
    
    return $this->config_alerts;
    
    
  } // add_alert()


  
  
  
  

  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::Config