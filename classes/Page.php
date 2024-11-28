<?php


/**
 * 
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
    
    
    $this->block_direct_access();
    
    
    
    // We need to grab any alerts that were stashed
    // when our Config class ran at the begining of 
    // the page load and merge them into our Page
    // alerts property.
    $this->Config = Config::get_instance();
    
    $config_alerts = $this->Config->get_alerts();
    
    $this->alerts = array_merge($this->alerts, $config_alerts);

    $this->alert_levels = ['info', 'warn', 'error'];
      
    $this->Db = Db::get_instance();
    
    $this->User = User::get_instance();

    $this->Routing = Routing::get_instance();


    
    // Handle error codes passed in the query string
    // @internal Moving this to Routing, after the path
    // is determined.
    //$this->process_page_messages();
    
    

    // @todo Think of a better way to handle this initial
    // logged in check.
    // @internal Maybe this could be a user_init() function
    // instead of an explicit login check?
    $this->User->is_logged_in();
    
    
  } // __construct();
  

  
  






  /**
   * @todo this needs to be rewritten to check whether the page is an
   *        admin route.
   */
  public function is_admin(): bool {

    return $this->User->is_admin();

  } // is_admin()


  
  
  

  
  
  public function site_root(): string {
    
    return rtrim($this->Config->get('site_root'), '/');
    
  } // site_root()
  
  
  
  
  
  
  
  
  public function url_for( string $path ): string {
    
    $path = ( $path === '/' ) ? '' : $path;
    
    $site_root = $this->site_root();
    
    return $site_root . '/' . $path;
    
  } // url_for()
  







  public function get_url(): string|null {

    $return = '';

    $path = $this->Routing->get_path();

    if ( !is_null($path) && array_key_exists('segments', $path) ):

      $joined_path = implode('/', $path['segments']);

      $return = $this->url_for($joined_path);

    endif;


    return $return;

  } // get_url()
  
  
  
  
  
  
  
  
  public function get_page_title(): string {
    
    return $this->Config->get('site_name');
    
  } // get_page_title()
  
  
  
  
  
  
  
  
  
  /**
   * We treat template files the same as partials
   * except instead of being served from the /partials/
   * sub-directory, they're served directly out of
   * the theme root. So, this function is just a thin
   * wrapper around the get_partial() function, but we
   * change the root directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false): void {
    

    $this->get_partial($file, $suffix, $args, '');


    // @todo Reassess this.
    //
    // get_template() should never be called twice, so
    // we can ditch the page_alert session here.
    Session::delete_key('page_alert');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  
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

  
  
  
  
  
  
  public static function validate_nonce(string $nonce, string $action): bool {

    $_ret = false;
    

    if ( Session::key_isset(['nonces', $action]) ):
      
      
      $nonceData = Session::get_key(['nonces', $action]);
      
      
      if ( $nonceData['expires'] >= time() ):
        
        $_ret = true;
        
      endif;


      // Remove the nonce after validation
      Session::delete_key(['nonces', $action]);

  
    endif;

    
    return $_ret;

  } // validate_nonce()










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

  
  
  
  
  
  
  
  
  public function add_alert(mixed $alert_text, ?string $level = null):void {
    

    $default_level = 'error';
    
    
    // Only allow levels listed in the array above.
    // Default to 'error' if something else is passed.
    if ( !is_null($level) ):
      
      $level = ( in_array($level, $this->alert_levels, true) ) ? $level : $default_level;
      
    else:
      
      $level = $default_level;
      
    endif;
      
    
    
    
    $this->alerts[] = ['level' => $level, 'text' => $alert_text];
    
    
    
  } // add_alert()
  
  
  
  
  
  
  
  /**
   * 
   */
  public function has_alerts($level = false): bool {
    
    // @todo add ability to only get alerts of a certain level

    // @todo ignore info and warn level msgs when not in debug mode

    return ( is_array($this->alerts) && !empty($this->alerts) );
    
    
  } // has_alerts()
  







  public function get_alerts($level = false): mixed {
    

    // Strip out info and warn level msgs when not in debug mode
    if ( !$this->Config->get('debug') && !$level ):

      $filtered_alerts = array_filter($this->alerts, function($item) {
        
        return $item['level'] === 'error';
        
      });
      

      // re-index the array to correct gaps
      // left when we filtered.
      return array_values($filtered_alerts);
      
    else:
      
      // If a specific level was requested return only alerts
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


  
  
  
  
  
  
  function process_page_alerts(): bool {
    
    $has_alert = false;
    
      
    // Check if there is an alert code querystring variable.
    if ( isset($_GET['alert']) ) :

      $querystring_code = htmlspecialchars( trim($_GET['alert']) );
      $session_alert = Session::get_key('page_alert');
      $session_code = isset($session_alert['code']) ? $session_alert['code'] : false;
      $session_level = isset($session_alert['level']) ? $session_alert['level'] : 'error';
      $msg_text = null;


      // Redirect if the alert code in our session doesn't match the querystring code.
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
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();

    endif;
  
    return self::$instance;
  
  } // get_instance()
  
  
    
} // ::Page
