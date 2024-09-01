<?php


use League\HTMLToMarkdown\HtmlConverter;

class Routes {

  
  private static $instance = null;
  
  private $page = null;
  
  private $path = null;









  
  private function __construct( $page, $request_uri ) {

    
    $this->block_direct_access();
    
    
    $this->page = $page;

    
    $this->path = $this->process_path( $request_uri );
    
    
    $this->serve_route( $this->path );
    
    
  } // __construct()
  
  
  
  
  
  
  
  
  
  /**
   * Determine which template will handle our
   * request, and prepare the data for that 
   * page.
   *
   * @todo clean up segment parsing
   */
  private function serve_route( $path ) {

    
    
    if ( isset($path['segments'][0]) &&
      ( $path['segments'][0] !== 'signup' ) ):
      
      $db = Db::get_instance();
    
      // Ensure that we have at least one admin user
      if ( !$db->row_exists('users', 'role', 'admin') ):
        
        self::redirect_to('signup');
        
      endif;
      
    endif;
    
    
    
    

    if (
      is_countable($path['segments']) && 
      ( count($path['segments']) == 1 ) &&
      ( $path['segments'][0] == '' )
    ):
    
    
      $this->page->get_template( "index" );
    
    
    // If we're serving a andmin route we need to use
    // the the route handling from the Admin class.
    elseif ( ($path['segments'][0] == 'admin') ||
      ($path['segments'][0] == 'signup') ||
      ($path['segments'][0] == 'login') ||
      ($path['segments'][0] == 'logout') ):
      
      
      $admin = Admin::get_instance();
    
      $admin->serve_route( $path );
      
      
    elseif ( $path['segments'][0] == 'post' ):
      
  
      $converter = new HtmlConverter(array('strip_tags' => true));
      
      $this->page->get_template( 'post', null, ['converter' => $converter] );
      
      
    else:
      
      
      $this->page->get_template( "404" );
      
      
    endif;
    
    
    
  } // serve_route()
  
  
  
  
  
  
  
  
  
  private function process_path( $request_uri ) {
    
    $parsed_request = [
      'segments' => [],
      'query_str' => []
    ];
    
    
    // If request_uri is null we can't figure out what
    // page we need to serve so log an error and bail.
    if ( is_null($request_uri) ):
      
      $this->page->add_error('Bad REQUEST_URI.');
      
      return $parsed_request;
      
    endif;
    
    
    // Remove all characters except letters, digits and 
    // $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=.
    $request_uri = filter_var($request_uri, FILTER_SANITIZE_URL);

    
    // Parse the URL to get the path and query string vars
    $parsed_url = parse_url($request_uri);
    
    $path = $parsed_url['path'];
    
    // Remove the trailing slash if present
    $path = rtrim($path, '/');
    
    
    
    // Break the path into segments
    $segments = explode('/', trim($path, '/'));
    
    
    // Parse the query string into an associative array
    $query_str_raw = $parsed_url['query'] ?? '';
    
    parse_str($query_str_raw, $query_str);
    
    
    $parsed_request = [
      'segments' => $segments,
      'query_str' => $query_str
    ];
    
    
    return $parsed_request;
    
    
  } // process_path()
  
  
  
  
  
  
  
  
  
  
  public static function redirect_to(string $url, int $statusCode = 302): void {
    
    if ( !headers_sent() ):
      
      http_response_code($statusCode);
      header("Location: " . $url);
      exit();
    
    else:
      
      // Handle the case where headers have already been sent
      echo "<script type='text/javascript'>window.location.href='{$url}';</script>";
      exit();
    
    endif;
    
  } // redirect_to()

  
  
  
  
  
  
  
  
  /**
   * If this class is instantiated outside the proper
   * scope prevent further instantiation.
   */
  private function block_direct_access() {
    
    if ( !defined('ROOT_PATH') ):
      
      die('Class called incorrectly.');
    
    endif;
    
    
  } // block_direct_access()
  
  
  
  
  
  
  
  
  
  
  
  
  public static function get_instance( $page, $request_uri ) {
  
    if (self::$instance === null):
      
      self::$instance = new Routes( $page, $request_uri );
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()

    
} // ::Routes
