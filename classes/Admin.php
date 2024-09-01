<?php




class Admin {

  
  private static $instance = null;
  
  

  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
  
  
  
  /**
   * Determine which template will handle our  request,
   * and prepare the data for thatpage.
   *
   * @internal I don't like that this method is public
   *
   */
  public function serve_route( $path ) {


    if (
      is_countable($path['segments']) && 
      ( count($path['segments']) == 1 ) &&
      ( $path['segments'][0] == 'login' )
    ):
    
    
      $this->get_template( 'login', null, false );
      
      
    elseif ( $path['segments'][0] == 'signup' ):
      
      
      $this->get_template( 'signup' );
      
      
    else:
    
      
      $page = Page::get_instance();
      
      $page->get_template( '404' );
      
      
    endif;
    
    
    
  } // serve_route()
  
  
  
  
  
  
  
  
  
  
  
  /**
   * We treat template files the same as partials
   * except instead of being served from the /partials/
   * sub-directory, they're served directly out of
   * the theme root. So, this function is just a thin
   * wrapper around the get_partial() function, but we
   * change the root directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false) {
    
    
    $page = Page::get_instance();
    
    $page->get_partial($file, $suffix, $args, 'admin');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Admin
