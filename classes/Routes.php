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

    
    
    if ( !$this->is_route('signup', $path) &&
         !$this->is_route('admin/form-handler', $path) ):
    
      $db = Db::get_instance();
    
      // Ensure that we have at least one admin user
      if ( !$db->row_exists('Users', 'role', 'admin') ):
        
        $auth = Auth::get_instance();
        
        // This really should never matter in real world
        // scenerios, but if the database is deleted while
        // a user is logged in they could retain cookie and
        // session data and confuse the is_logged_in() function
        // so we clear it out.
        $auth->logout();
        
        self::redirect_to('signup');
        
        
      endif;
      
      
    endif;
    
    
    

    // Homepage
    if ( $this->is_route('/', $path) ):
    
    
      $this->page->get_template( "index" );
    
    
    // If we're serving a andmin route we need to use
    // the the route handling from the Admin class.
    elseif (
            $this->is_route('signup', $path) ||
            $this->is_route('verify', $path) ||
            $this->is_route('login', $path) ||
            $this->is_route('admin/dash', $path) ||
            $this->is_route('admin/profile', $path) ||
            $this->is_route('admin/form-handler', $path)
          ):
    
      
      $admin = Admin::get_instance();
    
      $admin->serve_route( $path );
      
      
    elseif ( $this->is_route('post', $path) ):
      
  
      $converter = new HtmlConverter(array('strip_tags' => true));
      
      $this->page->get_template( 'post', null, ['converter' => $converter] );
      
      
    elseif ( $this->is_route('profile', $path) ):
      
      
      
      $this->page->get_template( 'profile' );
      
      
      
    elseif ( $this->is_route('logout', $path) ):
      

      $auth = Auth::get_instance();
    
      $auth->logout( Session::get_key('user_id') );
      
      self::redirect_to( $this->page->url_for('/') );
    
    
    // If all of the other route checks failed
    // serve a 404.
    else:
      
      
      $this->page->get_template( '404' );
      
      
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
    
    $path = ( isset($parsed_url['path']) ) ? $parsed_url['path'] : '';
    
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
  
  
  
  
  
  
  
  
  
  
  
  
  public static function is_route(string $url, array $path): bool {
    
    $return = false;
    
    
    // If eithier the string $url, or the array $path
    // are empty, or if If the 'segments' key doesn't 
    // exist in $path something is wrong so we return
    // false without the need to look further.
    if ( 
        ( !empty($url) && !empty($path) ) &&
        array_key_exists('segments', $path) 
      ):
      
      
      $url_arr = explode('/', trim($url, '/'));
      
      // We're not concerned with the query string when
      // figuring out the route to serve.
      $path = $path['segments'];
      

      return ( $url_arr === $path );
      
      
    endif;
    
    
    return $return;
    
    
  } // is_route()
  
  
  
  
  
  
  
  
  
  
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
  
  
  
  
  
  
  
  
  
  public static function nonce_redirect(string $nonce, string $action, string $redir_path, ?string $err = '001'): void {
    
    
    if ( !Page::validate_nonce($nonce, $action) ):
      
      $page = Page::get_instance();
      
      $redir_path = $page->site_root() . '/' . $redir_path;
      
      self::redirect_to( $redir_path . '?err=' . rawurlencode($err) );
      
    endif;
    
    
    
  } // nonce_redirect()
  

  
  
  
  
  
  
  
  public static function clean_post_vars(array $post): array {
    
    $sanitized = [];
    
    foreach ($post as $key => $value) :
    
      if (is_array($value)):
        
        // Recursively sanitize arrays
        $sanitized[$key] = $this->clean_post_vars($value);
        
      else:
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove HTML tags and encode special characters
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        // Add to the sanitized array
        $sanitized[$key] = $value;
        
      endif;
      
    endforeach;
    
    return $sanitized;
    
  } // clean_post_vars()
  
  
  
  
  
  
  
  
  
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
