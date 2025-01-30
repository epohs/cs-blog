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
    if ( Session::get_key(['user', 'id']) === $user_to_login['id'] ):
      
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
  
    
    
    // Optionally update the last login timestamp
    if ( $update_last_login ):
      
      $User->update_last_login( $user_to_login['id'] );
    
    endif;
    
    
    $User->set_force_logout($user_to_login['id'], 0);
    
  
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
      
      return $User->set_force_logout($user_id);
      
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
   * Return an instance of this class.
   */
  public static function get_instance(): self {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()
  
  
  

    
} // ::Auth