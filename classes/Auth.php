<?php

class Auth {
    
    
  private static $instance = null;  
  

  
  
  
  private function __construct() {
    

    
  } // __construct()
  
  
  
  
  
  
    
  
  
  
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
    
    echo 'User to log in: ' . var_export($user_to_login, true) . '<br>';
  
    if ( !$user_to_login ):
      
      echo 'no user to log in.<br>';
      
      return false; // User not found
      
    endif;
  
    
    // Check if user is already logged in
    if ( Session::get_key('user_id') === $user_to_login['id'] ):
      
      echo 'user session already exists.<br>';
      
      return true; // User already logged in
    
    endif;
    
  
    // Start the session for the user
    Session::set_key('user_id', $user_to_login['id']);
    Session::set_key('user_role', $user_to_login['role']);  // Store any relevant user data
  
    
    echo 'made it to update last login.<br>';
    
    // Optionally update the last login timestamp
    if ( $update_last_login ):
      
      echo 'Updateing last login.<br>';
      
      $user->update_last_login( $user_to_login['id'] );
    
    endif;
    
  
    return true;
    
  } // login()
  
  
  
  
  
  
  
  
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()
  
  
  

    
} // ::Auth