<?php

class Page {

  
  private static $instance = null;
  
  
  private function __construct() {

    Db::get_instance();
    
  }
  
  
  public static function get_instance() {
  
    if (self::$instance === null) {
      self::$instance = new Page();
    }
  
    return self::$instance;
  
  } // get_instance()

  
  
  
  
  
  function get_partial(string $file, ?string $suffix = null, $args = false)   {  
    
    
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

      return false;
    
    endif;  

  
  } // get_partial()

  
  
  
  
    
} // ::Page
