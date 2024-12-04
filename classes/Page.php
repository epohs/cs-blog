<?php


/**
 * Handles the preparation and display of all pages, including logic for
 * metadata, internal URLs, error display, and template rendering.
 *
 * This class is made public in all templates.
 */

class Page {

  
  private static $instance = null;
  
  private $Config = null;
  
  private $alerts = [];

  private $alert_levels = [];
  
  private $Db = null;

  private $User = null;

  private $Routing = null;
  
  private $partial_root = 'partials';
  
  


  



  private function __construct() {
    
    
    // Grab any alerts that were stashed when the Config 
    // class ran earlier in the process and merge 
    // them into the Page alerts property.
    $this->Config = Config::get_instance();
    
    $config_alerts = $this->Config->get_alerts();
    
    $this->alerts = array_merge($this->alerts, $config_alerts);

    $this->alert_levels = ['info', 'warn', 'error'];
    

    $this->Db = Database::get_instance();
    
    $this->User = User::get_instance();

    $this->Routing = Routing::get_instance();

    

    // @todo Think of a better way to handle this initial
    // logged in check.
    // @internal Maybe this could be a user_init() function
    // instead of an explicit login check?
    $this->User->is_logged_in();
    
    
  } // __construct();
  

  
  




  /**
   * Is the page currently being viewed an admin level page?
   * 
   * @todo this needs to be rewritten to check whether the page is an
   *        admin route.
   */
  public function is_admin(): bool {

    return $this->User->is_admin();

  } // is_admin()


  

  
  

  
  /**
   * Return the URL of this website without a trailing slash.
   * 
   * Uses the value set in our config process to determine the URL.
   */
  public function site_root(): string {
    
    return rtrim($this->Config->get('site_root'), '/');
    
  } // site_root()
  
  
  
  
  

  
  
  /**
   * Take a string representing a route within this website and
   * return a fully formed absolute URL.
   */
  public function url_for( string $path ): string {
    
    $path = ( $path === '/' ) ? '' : $path;
    
    $site_root = $this->site_root();
    
    return $site_root . '/' . $path;
    
  } // url_for()
  







  /**
   * Get the full URL for the current page.
   */
  public function get_url(): string|null {

    $return = '';

    $path = $this->Routing->get_path();

    if ( !is_null($path) && array_key_exists('segments', $path) ):

      $joined_path = implode('/', $path['segments']);

      $return = $this->url_for($joined_path);

    endif;


    return $return;

  } // get_url()
  
  
  
  
  

  
  
  /**
   * Get the page title for the current page.
   * 
   * Mostly used for the HTML <title>.
   */
  public function get_page_title(): string {
    
    return $this->Config->get('site_name');
    
  } // get_page_title()
  
  
  
  

  
  
  
  /**
   * Render the primary HTML template file for a page.
   * 
   * We treat template files the same as partials except instead of being 
   * served from the /partials/ sub-directory, they're served directly out 
   * of the theme root. This function is a thin wrapper around the 
   * get_partial() function, but we change the serving directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false): void {
    

    $this->get_partial($file, $suffix, $args, '');


    // After the page has been viewed, remove page alerts to avoid a
    // situation where multiple alerts are triggered during a page
    // refresh.
    Session::delete_key('page_alert');
    
    
  } // get_template()
  
  
  
  
  

  
  
  /**
   * Render a piece of an HTML template for a page.
   * 
   * This function will look for a file containing the HTML for a partial,
   * first in the active theme, and if the file isn't found it will look
   * in the 'default' theme directory for the same file. This allows for 
   * themes that customize only part of the default theme.
   * 
   * @todo add fallback to default theme.
   */
  public function get_partial(string $file, ?string $suffix = null, $args = false, $partial_root = false): bool {
    
    
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
    // outside the theme directory - primarily admin pages.
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
      $page = Page::get_instance();

      $User = $this->User;
      
   
      // If we have args, extract them into variables
      // for more readable code in the partial.
      if ( is_array($args) && ! empty($args) ):
        
        extract($args);  
      
      endif;
      
      
      include( $partial_path );


      return true;
      
    else:
      
      // @internal This error will never be been in any case where
      // the errors.php partial isn't included.
      $this->add_alert("Partial {$file} not found.", 'warn');

      return false;
    
    endif;  

  
  } // get_partial()

  
  

  
  
  
    
  /**
   * Create a nonce with an action key, and an expiration time in seconds.
   * Save this nonce to the session.
   * 
   * Used for CSRF protection, and repeated form submissions.
   * 
   * @todo Move nonce related functions to the Auth class?
   */
  public static function set_nonce(string $action, int $ttl = 3600): string {
    
    $nonce = Utils::generate_random_string(32);
    $expires = time() + $ttl;
    
    $nonce_data = [
      'nonce' => $nonce,
      'expires' => $expires
    ];
    
    // Store nonce data in the session overriding any
    // existing nonce with this action.
    Session::set_key(['nonces', $action], $nonce_data);
    
    return $nonce;

  } // set_nonce()

  
  
  
  
  


  /**
   * Test whether a nonce for a given action is valid against
   * the nonce saved in the session.
   */
  public static function validate_nonce(string $nonce, string $action): bool {


    $return = false;
    

    if ( Session::key_isset(['nonces', $action]) ):
      
      
      $nonceData = Session::get_key(['nonces', $action]);
      
      
      if ( $nonceData['expires'] >= time() ):
        
        $return = true;
        
      endif;


      // Remove the nonce after validation
      Session::delete_key(['nonces', $action]);

  
    endif;

    
    return $return;

  } // validate_nonce()








  /**
   * Remove any expired nonces from this session.
   */
  public static function remove_expired_nonces(): bool {


    $nonces = Session::get_key('nonces');

    $nonces_changed = false;


    // First, check to see if we have any nonces.
    // If we do, loop through them removing any
    // where the expires timestamp has passed.
    if ( is_array($nonces) && !empty($nonces) ):
      
      
      foreach ($nonces as $action => $nonce_data):

        if ( isset($nonce_data['expires']) && ( $nonce_data['expires'] <= time() ) ):
        
          unset( $nonces[$action] );

          $nonces_changed = true;
        
        endif;
      
      endforeach;


    endif;


    // If the nonce array changed, save over our nonces
    // stored in the session.
    if ( $nonces_changed ):

      Session::set_key('nonces', $nonces);

    endif;


    return $nonces_changed;

  } // remove_expired_nonces()

  
  
  

  
  
  
  /**
   * Add a page alert to be displayed when the page is rendered.
   */
  public function add_alert(string $alert_text, ?string $level = null):void {
    

    $default_level = 'error';
    
    
    // Only allow levels set in the alert_levels class property.
    // Default to 'error' if something else is passed.
    if ( !is_null($level) ):
      
      $level = ( in_array($level, $this->alert_levels, true) ) ? $level : $default_level;
      
    else:
      
      $level = $default_level;
      
    endif;
      
    
    $this->alerts[] = ['text' => $alert_text, 'level' => $level];
    
    
  } // add_alert()
  
  
  
  
  

  
  
  /**
   * Test whether this page load has any alerts.
   */
  public function has_alerts($level = false): bool {
    
    // @todo add ability to only get alerts of a certain level

    // @todo ignore info and warn level msgs when not in debug mode

    return ( is_array($this->alerts) && !empty($this->alerts) );
    
    
  } // has_alerts()
  







  /**
   * Get any alerts for this page that have been added during this page load.
   */
  public function get_alerts($level = false): mixed {
    

    // Strip out info and warn level msgs when not in debug mode.
    if ( !$this->Config->get('debug') && !$level ):


      $filtered_alerts = array_filter($this->alerts, function($item) {
        
        return $item['level'] === 'error';
        
      });
      

      // re-index the array to correct gaps
      // left when we filtered.
      return array_values($filtered_alerts);

      
    else:
      
      // If a specific level was requested, return only alerts
      // of that level, otherwise return all alerts.
      if ( $level ):
        
        $filtered_alerts = array_filter($this->alerts, function($item) use ($level) {
      
          return $item['level'] === $level;
          
        });
       
        return array_values($filtered_alerts);

      else:
        
        return $this->alerts;
        
      endif;
      
      
    endif;
    
    
  } // get_alerts()


  

  
  
  
  
  /**
   * Look for a page alert code in the querystring for this page request
   * and determine if it is a valid page alert.
   * 
   * If it is, add the alert to be displayed when the template is rendered.
   * 
   * All valid alert codes are defined in this function, and compared
   * with codes saved in the session before the redirect to protect against 
   * repeated triggering during page refreshes.
   * 
   * The alert level is defined in the session data, as well as optional 
   * alert text that will override the default alert text defined in this 
   * function.
   * 
   * @return bool True if the alert code was a valid alert.
   */
  function process_page_alerts(): bool {
    

    $has_alert = false;
    
      
    // Check if there is an alert code querystring variable.
    if ( isset($_GET['alert']) ) :


      $querystring_code = htmlspecialchars( trim($_GET['alert']) );
      $session_alert = Session::get_key('page_alert');
      $session_code = isset($session_alert['code']) ? $session_alert['code'] : false;
      $session_level = isset($session_alert['level']) ? $session_alert['level'] : 'error';
      $msg_text = null;


      // Redirect if the alert code in the querystring doesn't match the session code.
      if ( $session_code !== $querystring_code ):

        Routing::redirect_to( $this->get_url() );

      endif;

      
      // Check the alert code in the querystring against a pre-determined
      // set of codes. If it doesn't match one of them it is an invalid message.
      switch ( $querystring_code ):
      
        case '001':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Timed out. Please try again.';
        
          $this->add_alert( $msg_text, $session_level );
          
          break;
      
        case '002':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'User already exists.';
        
          $this->add_alert( $msg_text, $session_level );
          
          break;
      
        case '003':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Password invalid.';
        
          $this->add_alert( $msg_text, $session_level );
          
          break;
      
        case '004':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ??'Incorrect verification code.' ;
        
          $this->add_alert( $msg_text, $session_level );
          
          break;
      
        case '005':
          
          $has_alert = true;
        
          $msg_text = $session_alert['text'] ?? 'Incorrect login info.';

          $this->add_alert( $msg_text, $session_level );
          
          break;

        case '006':

          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Invalid email address';

          $this->add_alert( $msg_text, $session_level );

          break;
      
        case '070':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Something went wrong.';
        
          $this->add_alert( $msg_text, $session_level );
          
          break;
          
        default:
          
          // Error code not found so just return false and
          // add no custom error.
          break;
        
      endswitch;
      

    endif;
    
    
    return $has_alert;
    

  } // process_page_alerts()

  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();

    endif;
  
    return self::$instance;
  
  } // get_instance()
  
  
    
} // ::Page
