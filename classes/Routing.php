<?php

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Logic to determine valid routes and handling which 
 * classes should manage requests.
 *
 * Public routes are handled by Routes.
 * Admin routes are handled by AdminRoutes.
 * Forms are handled by FormHandler.
 */
class Routing {

  
  private static $instance = null;
  
  private $Alerts = null;
  
  private $Page = null;

  private $Routes = null;

  private $AdminRoutes = null;
  
  private $path = null;
  
  private static $route_vars = null;

  private $is_admin_route = null;









  
  private function __construct() {

    $this->Alerts = Alerts::get_instance();
    
  } // __construct()
  
  
  
  
  
  
  
  
  
  /**
   * Determine which template will handle our
   * request, and prepare the data for that 
   * page.
   *
   * @todo clean up segment parsing
   */
  public function serve_route( string $request_uri ): bool {

    
    $this->Page = Page::get_instance();

    $this->path = $this->process_path( $request_uri );
    
    
    // Handle error codes passed in the query string
    $this->Alerts->process();

    $this->Routes = Routes::get_instance( $this->Page );

    $this->AdminRoutes = AdminRoutes::get_instance();

    
    $this->first_run_check( $this->path );
    
    $all_routes = $this->Routes->get_routes();

    $valid_route = false;
    

    
    // @todo Think of a more efficient way to find the current route
    //       rather than looping through each of them.
    // @internal I think I could parse the $path and then limit the $all_routes array
    //           to only the keys that match the first segment, then loop through
    //           those. I would need to handle the site root so that / matches.
    foreach( $all_routes as $route_key => $handler ):

      if ( $this->is_route($route_key, $this->path) ):

        $handler_class = $handler[0];

        $this->is_admin_route = ( $handler_class instanceof AdminRoutes );
        
        debug_log('shouldve set admin route to: ' . var_export($this->is_admin_route, true));

        $valid_route = $this->Routes->serve($route_key);

        break;

      endif;

    endforeach;
    
    
    
    // If all of the other route checks failed serve a 404.
    if ( !$valid_route ):

      $this->Routes->serve('404');

    endif;
    
    
    return $valid_route;
    
    
  } // serve_route()
  
  





  /**
   * Handle the first time the application is run.
   *
   * Before an admin user is created every request
   * redirects to the signup page.
   */
  private function first_run_check( array $path ): void {


    if ( !$this->is_route('signup', $path) &&
         !$this->is_route('form-handler', $path) ):
    
      $Db = Database::get_instance();
    
      // Ensure that we have at least one admin user
      if ( !$Db->row_exists('Users', 'role', 'admin') ):
        
        
        // This really should never matter in real world
        // scenerios, but if the database is deleted while
        // a user is logged in they could retain cookie and
        // session data and confuse the is_logged_in() function
        // so we clear it out.
        Session::delete_key('user');

        self::redirect_with_alert( $this->Page->url_for('signup'), ['code' => '008'] );
        
        
      endif;
      
      
    endif;


  } // first_run_check()
  
  
  
  
  
  
  
  
  /**
   * Process the requested URI and return an array containing
   * the URL segments and any querystring paramaters that may exist.
   */
  private function process_path( $request_uri ): array {
    
    $parsed_request = [
      'segments' => [],
      'query_str' => []
    ];
    
    
    // If request_uri is null we can't figure out what
    // page we need to serve so log an error and bail.
    if ( is_null($request_uri) ):
      
      $this->Page->add('Bad REQUEST_URI.');
      
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
  
  
  
  
  
  
  
  
  /**
   * Test whether the current URL is a specific route.
   *
   * Certain URL segments contain variables. They are marked
   * in the route with surrounding curly brackets {}.
   * They are called route vars in this application and they
   * are parsed in this function.
   *
   * @internal It seems like route var parsing could be better
   *           handled in process_path.
   *
   * @param string $url the URL that we are testing.
   * @param array $path URL currently requested.
   *
   * @return True if 
   */
  public static function is_route(string $url, array $path): bool {
    
    
    // If either of our parameters are empty, or if
    // the 'segments' key doesn't exist in $path then
    // our comparisons will fail so we can return
    // false without the need to look further.
    if ( empty($url) || empty($path) || !isset($path['segments']) ):
      
      return false;
      
    else:
      
      
      // Explode the URL into segments.
      $url_segments = explode('/', trim($url, '/'));
      $path_segments = $path['segments'];
      
      
      // If the number of URL segments passed is less than the
      // requested path segments it is an automatic fail.
      if ( count($url_segments) < count($path_segments) ):
        
        return false;
          
      endif;
      

      self::$route_vars = []; // Initialize route variables
      

      // Loop through each URL segment and path segment.
      foreach ($url_segments as $i => $url_segment):
        
    
        $path_segment = isset($path_segments[$i]) ? $path_segments[$i] : null;
        
        // Check if the current URL segment contains a named parameter (e.g., {param} or {param?}).
        if ( preg_match('/\{(\w+)\??\}/', $url_segment, $matches) ):
          
          // Extract the parameter name
          $param_name = $matches[1];
          
          // Check if the parameter is optional
          $is_optional = substr(trim($url_segment, '{}'), -1) === '?'; 
          
        
          // If the path segment exists, store it as a named parameter.
          if ( !empty($path_segment) ):
            
            self::$route_vars[$param_name] = $path_segment;
              
          elseif ( !$is_optional ):
            
            // If the parameter is required but not provided, return false.
            return false;
          
          endif;
          
          
        else:
          
          // Non-parameter URL segments must match exactly with the corresponding path segment.
          if ($url_segment !== $path_segment):
            
            return false;
              
          endif;
          
        endif;
        
      
      endforeach;


      // If all segments matched, return true.
      return true;
        
      
    endif;
    
    
  } // is_route()
  
  
  
  
  
  
  
  
  /**
   * Get a route variable by it's key.
   *
   * @todo Should return value be 'mixed'?
   */
  public static function get_route_vars( ?string $key = '' ) {
    
    
    if ( array_key_exists($key, self::$route_vars) ):
      
      return self::$route_vars[$key];
      
    elseif ( !empty($key) && !array_key_exists($key, self::$route_vars) ):
      
      return false;
      
    else:
      
      return self::$route_vars;
      
    endif;
    
    
  } // get_route_vars()
  
  






  /**
   * Get the processed path from this class' property.
   */
  public function get_path() {

    return $this->path;

  } // get_path()

  
  
  
  
  
  

  /**
   * Redirect to a given URL with an optional header status code.
   */  
  public static function redirect_to(string $url, int $status_code = 302): void {
    
    
    if ( !headers_sent() ):
      
      http_response_code($status_code);
      header("Location: " . $url);
      exit();
    
    else:
      
      // Handle the case where headers have already been sent
      echo "<script type='text/javascript'>window.location.href='{$url}';</script>";
      exit();
    
    endif;
    
    
  } // redirect_to()








  /**
   * Redirect with an alert that will be shown to the user
   * on the target page.
   */
  public static function redirect_with_alert( string $url, array $alert_arr, ?int $status_code = 0 ): void {


    $default_alert_arr = [
      'level' => 'info',
      'code' => null,
      'text' => null
    ];

    // Merge passed arguments with defaults
    $alert = array_merge($default_alert_arr, $alert_arr);
    
    Session::set_key('page_alert', $alert);

    
    if ( $status_code ): 

      self::redirect_to($url . "?alert={$alert['code']}", $status_code);
      
    else:
      
      self::redirect_to($url . "?alert={$alert['code']}");
      
    endif;


  } // redirect_with_alert()
  
  
  
  
  
  
  
  
  /**
   * If the nonce given isn't valid, redirect to the given
   * path with an alert.
   */
  public static function nonce_redirect(string $nonce, string $action, ?string $redir_path = '', ?string $err = '001'): void {
    
    
    if ( !Auth::validate_nonce($nonce, $action) ):
      
      $Page = Page::get_instance();
      
      $redir_path = ( $redir_path !== '' ) ? $redir_path : $action;
      
      $redir_path = $Page->site_root() . '/' . $redir_path;
      
      self::redirect_with_alert( $redir_path, ['code' => rawurlencode($err)] );
      
    endif; 
    
    
  } // nonce_redirect()








  /**
   * Returns the is_admin_route class property.
   * 
   * This property is set to true by the serve_route method if the 
   * handler class is AdminRoutes.
   */
  function get_is_admin_route(): bool|null {

    return $this->is_admin_route;

  } // get_is_admin_route()

  
  
  
  
  
  
  /**
   * Loop through all variables in the $_POST array and 
   * apply some basic sanitization.
   */
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
   * Return an instance of this class.
   */
  public static function get_instance(): self {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  

    
} // ::Routing
