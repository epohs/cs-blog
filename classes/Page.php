<?php



class Page {

  
  private static $instance = null;
  
  private $config = null;
  
  private $errors = [];
  
  private $db = null;
  
  private $partial_root = 'partials';
  
  
  
  private function __construct( $process_route ) {
    
    
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
    
    
    $request_uri = isset($_SERVER['REQUEST_URI']) ? strval($_SERVER['REQUEST_URI']) : null;
    
    
    
    // Handle error codes passed in the query string
    $this->handle_queryvar_errs();
    
    
    
    // The only time we want to automatically process
    // routes when we instantiate the Page object is
    // during the first page load process.
    if ( $process_route ):
    
      Routes::get_instance( $this, $request_uri );
      
    endif;
    
    // $this->add_error('Test info one', 'info');
    // $this->add_error('Test warn one', 'warn');
    // $this->add_error('Test error one', 'error');
    // $this->add_error('Test Error two', 'error');
    // $this->add_error('Test info two', 'info');    
    
    
    
  } // __construct();
  

  
  
  
  
  
  
  

  
  
  public function site_root(): string {
    
    return rtrim($this->config->get('site_root'), '/');
    
  } // site_root()
  
  
  
  
  
  
  
  
  
  public function get_page_title(): string {
    
    return $this->config->get('site_name');
    
  } // get_page_title()
  
  
  
  
  
  
  
  
  public function url_for( $path ): string {
    
    $site_root = $this->site_root();
    
    return $site_root . '/' . $path;
    
  } // url_for()
  
  
  
  
  
  
  
  
  
  /**
   * We treat template files the same as partials
   * except instead of being served from the /partials/
   * sub-directory, they're served directly out of
   * the theme root. So, this function is just a thin
   * wrapper around the get_partial() function, but we
   * change the root directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false) {
    
    
    $this->get_partial($file, $suffix, $args, '');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  
  public function get_partial(string $file, ?string $suffix = null, $args = false, $partial_root = false) {
    
    
    $config = Config::get_instance();
    
    $theme = $config->get('theme');
    
    
    
    // If $partial_root is false we're looking for a true
    // partial, so look in the theme's partials folder.
    if ( $partial_root === false ):
      
      
      $file_base = "/themes/{$theme}/{$this->partial_root}/";

      
    // If $partial_root is an empty string we're looking for
    // a file in the root of the theme.
    elseif( $partial_root === '' ):
      
      
      $file_base = "/themes/{$theme}/";
    
    
    // If $partial_root has any other value, build the path
    // using the string we passed.
    // This is to accomodate admin pages being served from
    // outside the theme directory.
    else:
    
      
      $file_base = "$partial_root/";
      
      
    endif;
    
    
    
    // Build the full path to the partial based 
    // on what was passed.
    $partial_path = ROOT_PATH . $file_base . $file;  
    
    
    
    if ( !is_null($suffix) ):
      
      $partial_path .= '-' . $suffix;  
    
    endif;  
    
    
    $partial_path .= '.php';  
    
    

    // Include the specified partial file only if
    // it is found.
    if ( file_exists($partial_path) ):
   
      // Make the Page class available inside the included file.
      $page = $this;
      
   
      // If we have args, extract them into variables
      // for more readable code in the partial.
      if ( is_array($args) && ! empty($args) ):
        
        extract($args);  
      
      endif;  
      
      
      include( $partial_path );
      
      
    else:
      
      // @internal This error will never be been in any case where
      // the errors.php partial isn't included.
      $this->add_error("Partial {$file} not found.", 'warn');

      return false;
    
    endif;  

  
  } // get_partial()

  
  
  
  
  
  
  
  
  public static function set_nonce(string $action, int $ttl = 3600): string {
    
    $nonce = bin2hex(random_bytes(16));
    $expires = time() + $ttl;
    
    $nonceData = [
        'nonce' => $nonce,
        'expires' => $expires,
        'action' => $action
    ];
    
    // This origionally had $nonce as the key
    // test to see which method works out better
    $_SESSION['nonces'][$action] = $nonceData;
    
    return $nonce;

  } // set_nonce()

  
  
  
  
  
  
  public static function validate_nonce(string $nonce, string $action): bool {
    
    
    if (isset($_SESSION['nonces'][$action])):
  
      $nonceData = $_SESSION['nonces'][$action];
      
      if ( $nonceData['action'] === $action && $nonceData['expires'] >= time() ):
        
        // Remove the nonce after validation
        unset($_SESSION['nonces'][$action]);
        
        return true;
        
      endif;
  
    endif;
    
    
    return false;

  } // validate_nonce()

  
  
  
  
  
  
  
  
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


  
  
  
  
  
  
  function handle_queryvar_errs(): bool {
    
    $is_error = false;
    $err_code = null;
    $queryvar_err_msg = [];
    
      
    // Check if the 'err' key exists in the query string
    if ( isset($_GET['err']) ) :

      // Sanitize the 'err' value
      $err_code = htmlspecialchars( trim($_GET['err']) );
      
      // Determine the error message based on the 'err' value
      switch ( $err_code ) :
      
        case '001':
          
          $is_error = true;
        
          $this->add_error( 'Timed out. Please try again.' );
          
          break;
          
        default:
          
          // Error code not found so just return false and
          // add no custom error.
          break;
        
      endswitch;
      

    endif;
    
    
    return $is_error;
    
  } // handle_queryvar_errs()

  
  
  
  
  
  
  
  
  
  /**
   * If this class is instantiated outside the proper
   * scope prevent further instantiation.
   *
   * @internal I don't think this method is needed.
   */
  private function block_direct_access() {
    
    if ( !defined('ROOT_PATH') ):
      
      die('Class called incorrectly.');
    
    endif;
    
    
  } // block_direct_access()
  
  
  
  
  
  
  
  
  public static function get_instance( $process_route = false ) {
  
    if (self::$instance === null):
      
      self::$instance = new Page( $process_route );

    endif;
  
    return self::$instance;
  
  } // get_instance()
  
  
    
} // ::Page
