<?php

class Auth {
    
    
  private static $instance = null;  
  

  // @todo move this to a config setting
  private $login_length_days = 30;
  
  private $login_length;
  
  
    
    
  
  private function __construct() {
    
    $this->login_length = $this->login_length_days * 24 * 60 * 60;
    
  } // __construct()
  
  
  
  
  
  
  
  
  
  
  public function is_logged_in(): bool {
    
    
    if ( Session::get_key('user_id') ):
      
      return true;
      
    elseif ( $token = Cookie::get('remember_me') ):
      
      $hashed_token = hash('sha256', $token);
      
      $user = User::get_instance();
      
      $user_to_check = $user->get_by($hashed_token, 'remember_me');
      
      
      
      if ( $user_to_check ):
        
        Session::set_key('user_id', $user_to_check['id']);
        Session::set_key('user_selector', $user_to_check['selector']);
        Session::set_key('user_role', $user_to_check['role']);
        
        return true;
        
      else:
        
        return false;
        
      endif;
      
      
    else:
      
      return false;
      
    endif;
      
    
    
  } // is_logged_in()
  
  
  
  
  
  
  
    
  
  
  
  /**
  * Log in a user based on a user ID or selector string.
  *
  * @param mixed $identifier Can be user ID or selector string.
  * @param bool $update_last_login Whether to update the last login timestamp.
  * 
  * @return bool True on successful login, false otherwise.
  */
  public function login( $identifier, bool $update_last_login = true ): bool {
    
    
    $user = User::get_instance();
    
    $user_identifier_key = ( is_numeric($identifier) ) ? 'id' : 'selector';
    
    // Fetch user based on identifier (user ID or selector)
    $user_to_login = $user->get_by($identifier, $user_identifier_key);
    
  
    if ( !$user_to_login ):
      
      return false; // User not found
      
    endif;
  
    
    // Check if user is already logged in
    if ( Session::get_key('user_id') === $user_to_login['id'] ):
      
      return true; // User already logged in
    
    endif;
    
    
    Session::regenerate();
    
    
    // Generate a random string for remember me cookie
    $token = bin2hex(random_bytes(32)); // 64 chars long
    
    

    
    // Store the token in a cookie for 30 days
    Cookie::set('remember_me', $token, $this->login_length);
        
    
    // Store a hashed version of the token in the database
    // @todo this function returns a bool, verify that it worked
    $user->set_remember_me( $user_to_login['id'], $token );
      
    
  
    // Store critical data in the session for the user
    Session::set_key('user_id', $user_to_login['id']);
    Session::set_key('user_selector', $user_to_login['selector']);
    Session::set_key('user_role', $user_to_login['role']);
  
    
    
    // Optionally update the last login timestamp
    if ( $update_last_login ):
      
      $user->update_last_login( $user_to_login['id'] );
    
    endif;
    
  
    return true;
    
  } // login()
  
  
  
  
  
  
  
  
  
  
  
  
  public function logout( ) {
    
    
    Session::destroy();
    Cookie::delete('remember_me');
    
    
    
  } // logout()
  
  
  
  
  
  
  
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()
  
  
  

    
} // ::Auth