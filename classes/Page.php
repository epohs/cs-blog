<?php

class Page {

  
  private static $instance = null;
  
  private $config = null;
  
  private $errors = [];
  
  
  
  
  
  
  private function __construct() {

    
    Db::get_instance();
    
    
    // We need to grab any errors that were stashed
    // when our Config class ran at the begining of 
    // the page load, and merge them into our page
    // errors propery.
    $this->config = Config::get_instance();
    
    $config_errors = $this->config->get_errors();
    
    $this->errors = array_merge($this->errors, $config_errors);
    
    
  } // __construct();
  

  
  
  
  
  
  public function get_page_title() {
    
    return $this->config->get('site_name');
    
  } // get_page_title()
  
  
  
  
  
  
  
  
  public function get_partial(string $file, ?string $suffix = null, $args = false)   {  
    
    
    // Build the full path to the partial based 
    // on what was passed.
    $partial_path = ROOT_PATH . '/partials/' . $file;  
    
    
    if ( !is_null($suffix) ):
      
      $partial_path .= '-' . $suffix;  
    
    endif;  
    
    
    $partial_path .= '.php';  
    
    

    // Include the specified partial file only if
    // it is found.
    if ( file_exists($partial_path) ):
   
      // Always make the Page class available.
      $page = Page::get_instance();
      
   
      // If we have args, extract them into variables
      // for more readable code in the partial.
      if ( is_array($args) && ! empty($args) ):
        
        extract($args);  
      
      endif;  
      
      
      include $partial_path;
      
      
    else:
      
      $this->add_error("Partial {$file} not found.", 'warn');

      return false;
    
    endif;  

  
  } // get_partial()

  
  
  
  
  
  
  
  
  
  
  public function add_error($error_msg, $level = null) {


    $acceptable_levels = [
      'info',
      'warn',
      'error'
    ];
    
    $default_level = 'error';
    
    
    // Only allow levels listed in the array above.
    // Default to 'error' if something else is passed.
    if ( !is_null($level) ):
      
      $level = ( in_array($level, $acceptable_levels, true) ) ? $level : $default_level;
      
    else:
      
      $level = $default_level;
      
    endif;
      
    
    
    
    $this->errors[] = ['level' => $level, 'msg' => $error_msg];
    
    
    
  } // add_error()
  
  
  
  
  
  
  
  
  public function has_errors($level = false) {
    
    // @todo add ability to only get errors of a certain level

    // @todo ignore info and warn level msgs when not in debug mode

    return ( is_array($this->errors) && !empty($this->errors) );
    
    
  } // has_errors()
  







  public function get_errors($level = false) {
    
    // @todo add ability to only get errors of a certain level
    // @todo strip out info and warn level msgs when not in debug mode
    

    return $this->errors;
    
    
  } // get_errors()


  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new Page();

    endif;
  
    return self::$instance;
  
  } // get_instance()
  
  
    
} // ::Page
