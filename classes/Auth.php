<?php

/**
 * Handle User authentication.
 */

class Auth {
    
    
  private static $instance = null;  
  

  // @todo move this to a config setting
  private $login_length_days = 30;
  
  private $login_length;
  
  
    
    
  
  
  
  
  private function __construct() {
    
    $this->login_length = $this->login_length_days * 24 * 60 * 60;
    
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
    
    
    $user = User::get_instance();
    
    $user_identifier_key = ( is_numeric($identifier) ) ? 'id' : 'selector';
    
    // Fetch user based on identifier (user ID or selector)
    $user_to_login = $user->get_by($user_identifier_key, $identifier);
    
  
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

      // Store the token in a cookie for 30 days
      Cookie::set('remember_me', $token, $this->login_length);
          
      
      // Store a hashed version of the token in the database
      // @todo this function returns a bool, verify that it worked
      $user->set_remember_me( $user_to_login['id'], $token );

    endif;
    

    $user_role = ( intval($user_to_login['is_verified']) == 1 ) ? $user_to_login['role'] : 'null';

  
    // Store critical data in the session for the user
    Session::set_key(['user', 'id'], $user_to_login['id']);
    Session::set_key(['user', 'selector'], $user_to_login['selector']);
    Session::set_key(['user', 'role'], $user_role);
  
    
    
    // Optionally update the last login timestamp
    if ( $update_last_login ):
      
      $user->update_last_login( $user_to_login['id'] );
    
    endif;
    
  
    return true;
    
  } // login()
  
  
  
  
  
  
  
  
  /**
   * Log out the current user.
   *
   * @todo How do we log out a specific User by ID? (banned user logout)
   */
  public function logout(): void {
    

    // If the visitor has a remember_me token
    // remove it from their User row in the database.
    if ( $remember_me = Cookie::get('remember_me') ):

      // First we check whether the token even matches a 
      // user in the database.
      $hashed_token = hash('sha256',  $remember_me);
      
      $user = User::get_instance();
      
      $user_to_logout = $user->get_by('remember_me', $hashed_token);

      
      if ( isset($user_to_logout['id']) ):

        // Found a valid User, so remove this token from their row
        $user->delete_remember_me_token($user_to_logout['id'], $remember_me);

      endif;

    endif;


    // Clear session and delete the remember me cookie
    Session::destroy();
    Cookie::delete('remember_me');
    
    
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