<?php

class Page {

  
  private static $instance = null;
  
  private $config = null;
  
  private $errors = [];
  
  private $db = null;
  
  private $partial_root = 'partials';
  
  
  
  private function __construct() {
    
    
    $this->block_direct_access();

    
    // Setup our database
    $this->db = Db::get_instance();
    
    
    // We need to grab any errors that were stashed
    // when our Config class ran at the begining of 
    // the page load and merge them into our Page
    // errors property.
    $this->config = Config::get_instance();
    
    $config_errors = $this->config->get_errors();
    
    $this->errors = array_merge($this->errors, $config_errors);
    
    
    
    
    
    //$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    $request_uri = null;
    
    Routes::get_instance( $this, $request_uri );
    
    // $this->add_error('Test info one', 'info');
    // $this->add_error('Test warn one', 'warn');
    // $this->add_error('Test error one', 'error');
    // $this->add_error('Test Error two', 'error');
    // $this->add_error('Test info two', 'info');    
    
    
    
  } // __construct();
  

  
  
  
  
  
  
  

  
  
  public function site_root() {
    
    return rtrim($this->config->get('site_root'), '/');
    
  } // site_root()
  
  
  
  
  
  
  
  
  
  public function get_page_title() {
    
    return $this->config->get('site_name');
    
  } // get_page_title()
  
  
  
  
  
  
  
  /**
   * We treat template files the same as partials
   * except instead of being served from the /partials/
   * sub-directory, they're served directly out of
   * the theme root. So, this function is just a thin
   * wrapper around the get_partial() function, but we
   * change the root directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false) {
    
    
    $this->get_partial($file, $suffix, $args, true);
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  
  public function get_partial(string $file, ?string $suffix = null, $args = false, $theme_root = false) {  
    
    
    $theme = $this->config->get('theme');
    
    
    // If we're looking for a "template" file, look in the root
    // of the theme. Otherwise, look in the partials directory.
    if ( $theme_root ):
      
      $file_base = '';
      
    else:
      
      $file_base = "{$this->partial_root}/";
      
    endif;
    
    
    // Build the full path to the partial based 
    // on what was passed.
    $partial_path = ROOT_PATH . "themes/{$theme}/" . $file_base . $file;  
    
    
    
    if ( !is_null($suffix) ):
      
      $partial_path .= '-' . $suffix;  
    
    endif;  
    
    
    $partial_path .= '.php';  
    
    

    // Include the specified partial file only if
    // it is found.
    if ( file_exists($partial_path) ):
   
      // Always make the Page class available.
      $page = $this;
      
   
      // If we have args, extract them into variables
      // for more readable code in the partial.
      if ( is_array($args) && ! empty($args) ):
        
        extract($args);  
      
      endif;  
      
      
      include( $partial_path );
      
      
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
    

    // Strip out info and warn level msgs when not in debug mode
    if ( !$this->config->get('debug') && !$level ):

      $filtered_errors = array_filter($this->errors, function($item) {
        
        return $item['level'] === 'error';
        
      });
      

      // re-index the array to correct gaps
      // left when we filtered.
      return array_values($filtered_errors);
      
    else:
      
      // If a specific level was requested return only errors
      // of that level, otherwise return all errors.
      if ( $level ):
        
        $filtered_errors = array_filter($this->errors, function($item) use ($level) {
      
          return $item['level'] === $level;
          
        });
       
        return array_values($filtered_errors);

      else:
        
        return $this->errors;
        
      endif;
      
      
    endif;
    
    
  } // get_errors()


  
  
  
  
  
  
  /**
   * If this class is instantiated outside the proper
   * scope prevent further instantiation.
   */
  private function block_direct_access() {
    
    if ( !defined('ROOT_PATH') ):
      
      die('Class called incorrectly.');
    
    endif;
    
    
  } // block_direct_access()
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new Page();

    endif;
  
    return self::$instance;
  
  } // get_instance()
  
  
    
} // ::Page
