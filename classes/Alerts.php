<?php

/**
 * Deal with any errors, warnings or feedback messages for the user.
 * 
 * Any messages displayed to the user will be reffered to througout 
 * this app as a Page Alert.
 * 
 * Alert HTML is handled in page-alerts.php for both admin templates
 * and user themes.
 */
class Alerts {
    
    
  private static $instance = null;
  
  private $alerts = [];

  private $alert_levels = [];
  
  
  
  
  
  
  
  
  private function __construct() {

    $this->alert_levels = ['info', 'warn', 'error', 'debug'];
    
  } // __construct()
  
  
  

  
  
  

  /**
   * Add a page alert that will be displayed when the page is rendered.
   */
  public function add(string $alert_text, ?string $level = null):void {
    

    $default_level = 'error';
    
    
    // Only allow levels set in the alert_levels class property.
    // Default to 'error' if something else is passed.
    if ( !is_null($level) ):

      $level = ( in_array($level, $this->alert_levels, true) ) ? $level : $default_level;
      
    else:
      
      $level = $default_level;
      
    endif;
      
    
    $this->alerts[] = ['text' => $alert_text, 'level' => $level];
    
    
  } // add()
  
  
  
  
  

  
  
  /**
   * Test whether this page load has any alerts.
   */
  public function has_alerts($level = false): bool {
    
    $Config = Config::get_instance();
    
    // Make a copy of the alerts array to avoid
    // manipulating it if we filter.
    $alerts = $this->alerts;
    
    
    // If a specific level was requested, take only alerts
    // of that level into account.
    if ( $level ):
      
      
      $alerts = array_filter($alerts, function($item) use ($level) {
    
        return $item['level'] === $level;
        
      });
      
      
    elseif ( !$Config->get('debug') ):
      

      $alerts = array_filter($alerts, function($item) {
    
        return $item['level'] !== 'debug';
        
      });
      
      
    endif;
    

    return ( is_array($alerts) && !empty($alerts) );
    
    
  } // has_alerts()
  







  /**
   * Get any alerts for this page that have been added during this page load.
   */
  public function get($level = false): mixed {
    

    $Config = Config::get_instance();


    // Strip out info and warn level msgs when not in debug mode.
    if ( !$Config->get('debug') && !$level ):


      $filtered_alerts = array_filter($this->alerts, function($item) {
        
        return $item['level'] !== 'debug';
        
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
    
    
  } // get()


  

  
  
  
  
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
  function process(): bool {
    

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

        $Page = Page::get_instance();

        Routing::redirect_to( $Page->get_url() );

      endif;

      
      // Check the alert code in the querystring against a pre-determined
      // set of codes. If it doesn't match one of them it is an invalid message.
      switch ( $querystring_code ):
      
        case '000':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'You are no longer welcome here.';

          break;
      
        case '001':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Timed out. Please try again.';

          break;
      
        case '002':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'User already exists.';
          
          break;
      
        case '003':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Password invalid.';
          
          break;
      
        case '004':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Incorrect verification code.';
          
          break;
      
        case '005':
          
          $has_alert = true;
        
          $msg_text = $session_alert['text'] ?? 'Incorrect login info.';
          
          break;

        case '006':

          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Invalid email address.';

          break;

        case '007':

          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Invalid password reset key.';

          break;

        case '008':

          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'At least one user must exist. The first user will be an Admin user.';

          break;
      
        case '070':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Something went wrong.';
          
          break;
      
        // Alerts in the 100 range are successes.
        case '101':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Password updated.';

          break;
          
        case '102':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'New post added.';

          break;
          
        case '103':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Post updated.';

          break;
          
        case '104':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Post deleted.';

          break;
          
        case '106':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'User updated.';

          break;
        
        // Alerts in the 200 range are related to Posts.
        case '200':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Bad post selector.';

          break;
        
        // Alerts in the 300 range are related to Users.
        case '300':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Bad user selector.';

          break;
          
        case '301':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'Errors editing user.';

          break;
        
        case '304':
          
          $has_alert = true;

          $msg_text = $session_alert['text'] ?? 'User deleted.';

          break;
          
        default:
          
          // Error code not found so just return false and
          // add no custom error.
          break;
        
      endswitch;


      if ( $has_alert ):

        $this->add( $msg_text, $session_level );

      endif;
      

    endif;
    
    
    return $has_alert;
    

  } // process()
  
  
  
  
  

  
  
  
  

  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::Alerts