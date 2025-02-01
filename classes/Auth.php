<?php

/**
 * Handle User authentication.
 */

class Auth {
    
    
  private static $instance = null;  
  
  
  private $remember_me_length;
  
  
    
    
  
  
  
  
  private function __construct() {
    
    $Config = Config::get_instance();
    
    $this->remember_me_length = $Config->get('remember_me_length') * 24 * 60 * 60;
    
  } // __construct()
  
  
  
  
  
  
  
  
  /**
  * Log in a user based on a user ID or selector string.
  *
  * @param mixed $identifier Can be user ID or selector string.
  * @param bool $update_last_login Whether to update the last login timestamp.
  * @param bool $remember_me
  * 
  * @return bool True on successful login, false otherwise.
  */
  public function login( $identifier, bool $update_last_login = true, bool $remember_me = false ): bool {
    
    
    $User = User::get_instance();
    
    $user_identifier_key = ( is_numeric($identifier) ) ? 'id' : 'selector';
    
    // Fetch user based on identifier (user ID or selector)
    $user_to_login = $User->get_by($user_identifier_key, $identifier);
    
  
    if ( !$user_to_login ):
      
      return false; // User not found
      
    endif;
  
    
    // Check if user is already logged in
    if (
        Session::get_key(['user', 'id']) === $user_to_login['id'] &&
        Session::get_key(['user', 'login_token']) === $user_to_login['login_token']
       ):
      
      return true; // User already logged in
    
    endif;
    
    
    Session::regenerate();
    
    
    // Generate a random string for remember me cookie
    $token = Utils::generate_random_string(64);
    

    if ( $remember_me ):
      
      Cookie::set('remember_me', $token, $this->remember_me_length);
      
      // Store a hashed version of the token in the database
      $User->set_remember_me( $user_to_login['id'], $token );

    endif;
    

    $user_role = ( intval($user_to_login['is_verified']) == 1 ) ? $user_to_login['role'] : 'null';

  
    // Store critical data in the session for the user
    Session::set_key(['user', 'id'], $user_to_login['id']);
    Session::set_key(['user', 'selector'], $user_to_login['selector']);
    Session::set_key(['user', 'role'], $user_role);
    Session::set_key(['user', 'login_token'], $user_to_login['login_token']);
  
    
    
    // Optionally update the last login timestamp
    if ( $update_last_login ):
      
      $User->update_last_login( $user_to_login['id'] );
    
    endif;
    
  
    return true;
    
  } // login()
  
  
  
  
  
  
  
  
  /**
   * Log out the given user, or the current user
   * if no $user_id is passed.
   */
  public function logout( ?int $user_id = null ): bool {
    
    
    $return = false;
    

    if ( $user_id ):
      
      $User = User::get_instance();
      
      // This will force the user to log in again on
      // every device that they are currently logged
      // in on.
      return $User->reset_login_token($user_id);
      
    else:
      
      
      // If the visitor has a remember_me token
      // remove it from their User row in the database.
      if ( $remember_me = Cookie::get('remember_me') ):
  
        // First we check whether the token even matches a 
        // user in the database.
        $hashed_token = hash('sha256',  $remember_me);
        
        $User = User::get_instance();
        
        $user_to_logout = $User->get_by('remember_me', $hashed_token);
  
        
        if ( isset($user_to_logout['id']) ):
  
          // Found a valid User, so remove this token from their row
          $User->delete_remember_me_token($user_to_logout['id'], $remember_me);
  
        endif;
  
      endif;
  
  
      // Clear session and delete the remember me cookie
      Session::destroy();
      Cookie::delete('remember_me');
      
      $return = true;
      
      
    endif;
    
    
    return $return;
    
    
  } // logout()
  
  





  
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
   * Return an instance of this class.
   */
  public static function get_instance(): self {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()
  
  
  

    
} // ::Auth