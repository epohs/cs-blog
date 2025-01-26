<?php

/**
 * All forms post to the /form-handler/ URL.
 * 
 * The methods in this class handle processing each of
 * those forms.
 */


class FormHandler {

  private static $instance = null;

  private $Page = null;

  private $Auth = null;

  private $User = null;
  
  private $Limits = null;

  private $map = null;

  private $post_vars = null;

  private $nonce = null;








  private function __construct() {

    
    $this->Page = Page::get_instance();

    $this->Auth = Auth::get_instance();

    $this->User = User::get_instance();
    
    $this->Limits = RateLimits::get_instance();


    $this->add_form('login', 'login');
    $this->add_form('verify', 'verify');
    $this->add_form('signup', 'signup');
    $this->add_form('forgot', 'forgot_password');
    $this->add_form('password-reset', 'password_reset');
    
    
    $this->Limits->set('form_login', 5, '5 minutes');

    
  } // __construct()








  /**
   * Decide which method in this class handles this request. 
   * If the request is valid, use the serve() method to call 
   * the correct handler.
   */
  public function process(): bool {
    

    $return = false;
    
    $debug_msg = null;
    
    $post_vars = Routing::clean_post_vars( $_POST );

    $form_name = $post_vars['form_name'] ?? null;
    
    $nonce = $post_vars['nonce'] ?? null;
    

    if ( $form_name && $nonce ):

      
      // Check whether the form name posted matches a form
      // name that has been mapped to a handler function.
      if ( is_array($this->map) && array_key_exists($form_name, $this->map) ):

        $this->post_vars = $post_vars;

        $this->nonce = $nonce;

        $this->serve( $form_name );
        
        $return = true;

      else:

        $debug_msg = "Bad form name: {$form_name}";

      endif;


    else:

      $debug_msg = "Bad post to form handler. form_name: {$form_name} | nonce: {$nonce}";

    endif;
    
    
    if ( $debug_msg ):
      
      debug_log($debug_msg);
      
    endif;
    
    
    // If processing the form post gets to this point, return
    // should always be false, and no form was served.
    return $return;
    

  } // process()








  /**
   * Handle the login form.
   */
  private function login(): void {

    
    // This form is rate limited. Redirect with an error
    // if the form has been submitted too many times.
    if ( !$this->Limits->check('form_login') ):

      
      $retry_after = $this->Limits->get_retry_after('form_login');
      
      $retry_after_str = Utils::format_date($retry_after);
      
      $retry_after_header = Utils::format_date($retry_after, 'D, d M Y H:i:s') . ' GMT';
      
      
      header("Retry-After: {$retry_after_header}");

      $err_msg = "Too many login attempts. Try again after {$retry_after_str}.";
      
      // Login attempt failed. Redirect back with an error.
      Routing::redirect_with_alert( $this->Page->url_for('login'), ['code' => '001', 'text' => $err_msg], 429 );


    endif;


    Routing::nonce_redirect($this->nonce, 'login');
               
    $user_to_login = $this->User->get_by('email', $this->post_vars['email']);
    
    
    if ( $user_to_login ):

      // Check whether the `locked_until` column is set to a 
      // date in the future for this user. If it is, this
      // user is locked out.
      // Do not proceed with any verification.
      // Extend the lockout and redirect with an error.
      if ( isset($user_to_login['locked_until']) &&
            Utils::is_valid_datetime($user_to_login['locked_until']) &&  
            Utils::is_future_datetime($user_to_login['locked_until'])
          ):
          

        $locked_until = $this->User->extend_lockout($user_to_login);
      
        $retry_after_str = Utils::format_date($locked_until);
      
        $retry_after_header = Utils::format_date($locked_until, 'D, d M Y H:i:s') . ' GMT';
        
        $err_msg = "Too many login attempts. Try again after {$retry_after_str}.";
      
      
        header("Retry-After: {$retry_after_header}");
      
        // Login attempt failed. Redirect back with an error.
        Routing::redirect_with_alert( $this->Page->url_for('login'), ['code' => '001', 'text' => $err_msg], 429 );


      elseif ( isset($user_to_login['locked_until']) &&
          Utils::is_valid_datetime($user_to_login['locked_until']) &&  
          Utils::is_past_datetime($user_to_login['locked_until'])
        ):
        
        
        // User was locked out but it expired. Clear the lockout.
        $this->User->remove_lockout($user_to_login, 'lockout-only');
        

      endif;

      
      // Check whether the password entered was correct.
      if ( password_verify($this->post_vars['password'], $user_to_login['password']) ):

        // If the user isn't verified then we don't want to update 
        // the last_login timestamp.
        $update_last_login = ( intval($user_to_login['is_verified']) == 1 ) ? true : false;

        // Optionally set a cookie to remember user across sessions.
        $remember_me = ( isset($this->post_vars['remember_me']) && (intval($this->post_vars['remember_me']) == 1) ) ? true : false;

        // Log this user in.
        $is_logged_in = $this->Auth->login( $user_to_login['id'], $update_last_login, $remember_me );
        
      else:
        
        $this->User->increment_failed_login($user_to_login);
        
        $is_logged_in = false;
        
      endif;

      
    else:
      
      $is_logged_in = false;
      
    endif;
    
    
    
    if ( $is_logged_in ):


      $this->Limits->delete_expired('form_login');
      
      $this->User->remove_lockout($user_to_login);
      
      
      if ( !$this->User->is_verified() ):

        Routing::redirect_to( $this->Page->url_for('verify') );

      elseif ( $this->User->get_role() == 'admin' ):
        
        Routing::redirect_to( $this->Page->url_for('admin/dash') );
        
      else:
        
        Routing::redirect_to( $this->Page->url_for('/') );
        
      endif;
      

    else:
      
      // Login attempt failed. Redirect back with an error.
      Routing::redirect_with_alert( $this->Page->url_for('login'), ['code' => '005'] );
      
    endif;


  } // login()








  /**
   * User email verification.
   */
  private function verify(): void {
        
        
    Routing::nonce_redirect($this->nonce, 'verify');
    
    
    // If there is not a user_id in the session
    // Redirect back to the login screen.
    if ( !$user_id = Session::get_key(['user', 'id']) ):
      
      Routing::redirect_to( $this->Page->url_for('login') );
      
    endif;
    
    
    $passed_verify_code = ( isset($this->post_vars['verify_key']) ) ? $this->post_vars['verify_key'] : false;
    
    $user_to_verify = ($passed_verify_code) ? $this->User->get_by('verify_key', $passed_verify_code) : false;
    
    
    // If there is a user with this verification key
    // and that user_id matches the user_id in the session
    // then log the user in, setting the last_login timestamp.
    // Verify the user, and redirect to their profile page.
    if ( isset($user_to_verify['id']) && ($user_to_verify['id'] === $user_id) ):
      
      
      $this->Auth->login( $user_id );
      
      $this->User->verify( $user_id );
      

      // We override the session of a non-verified user to 
      // always be null, so after we verify a user we need to 
      // set this back to what it should be.
      Session::set_key(['user', 'role'], $user_to_verify['role']);
      
      Routing::redirect_to( $this->Page->url_for('profile') );
      
      
    else:
      
      // Otherwise, redirect back to verify page with an error
      Routing::redirect_with_alert( $this->Page->url_for('verify'), ['code' => '004'] );
      
    endif;


  } // verify()








  /**
   * New user signup.
   */
  private function signup() {
        
      
    Routing::nonce_redirect($this->nonce, 'signup');
        
        
    $user_email = isset($this->post_vars['email']) ? $this->post_vars['email'] : false;
    
    $user_pass = isset($this->post_vars['password']) ? $this->post_vars['password'] : '';
    
    $user_exists = $this->User->user_exists($user_email);
    
    $user_pass_valid = $this->User->validate_pass($user_pass);
    
    
    if ( $user_email && !$user_exists && $user_pass_valid ):
      
      
      $user_data = [
        'email' => $user_email,
        'password' => $user_pass
      ];
      
      // Create the new user.
      $new_user_id = $this->User->new($user_data);
      
      // Test whether the user was successfully added.
      if ( $new_user_id ):
        
        // Manually set logged in cookie and session but
        // do not set last login timestamp.            
        $this->Auth->login($new_user_id, false);
        
        // @todo Add a pretty strict rate limit for this.
        
        Routing::redirect_to( $this->Page->url_for('verify') );
        
      else:
        
        Routing::redirect_with_alert( $this->Page->url_for('signup'), ['code' => '070'] );
        
      endif;
      
      
    elseif ( $user_exists ):
      
      Routing::redirect_with_alert( $this->Page->url_for('signup'), ['code' => '002'] );
        
    elseif ( !$user_pass_valid ):
      
      Routing::redirect_with_alert( $this->Page->url_for('signup'), ['code' => '003'] );
        
    else:
      
      Routing::redirect_with_alert( $this->Page->url_for('signup'), ['code' => '070'] );
      
    endif;


  } // signup()








  /**
   * Reset user password.
   */
  private function forgot_password(): void {


    Routing::nonce_redirect($this->nonce, 'forgot');
        
        
    $user_email = isset($this->post_vars['email']) ? $this->post_vars['email'] : false;


    // If the email address entered is a valid looking email
    // address then we move forward, otherwise we redirect back
    // to the forgot password page with an error.
    if ( filter_var($user_email, FILTER_VALIDATE_EMAIL) ):
      

      $user_to_reset = $this->User->get_by('email', $user_email);
      

      if ( is_array($user_to_reset) && isset($user_to_reset['id']) ):

        // A user with this email adress was found. Set the reset password token.
        $this->User->set_password_reset_token( $user_to_reset['id'] );

      endif;
      

      // Redirect to the password reset page regardless.
      // @todo Make sure there's a message to check your email for the reset key
      //        and make sure the email is correct.
      // @todo Make it clear during signup that it's important to remember your email
      Routing::redirect_to( $this->Page->url_for('password-reset') );


    else:

      Routing::redirect_with_alert( $this->Page->url_for('forgot'), ['code' => '006'] );

    endif;


  } // forgot_password()








  /**
   * Password reset.
   */
  private function password_reset() {


    Routing::nonce_redirect($this->nonce, 'password-reset');


    $reset_key = ( isset($this->post_vars['reset_key']) ) ? $this->post_vars['reset_key'] : false;
    $session_key = Session::get_key('reset_key');
    $keys_match = ( $reset_key === $session_key );
    $new_pass = ( isset($this->post_vars['new_pass']) ) ? $this->post_vars['new_pass'] : false;


    // If we have both a key and a new password
    // then validate both and reset the password for this user.
    if ( $reset_key && $keys_match && $new_pass ):

      $user_to_reset = $this->User->check_password_reset_token($reset_key);

      // This should never really happen because a user should never be able to get
      // to the form that allows them to enter a new password if they don't have a reset key
      // that matches a user, but to be safe..
      if ( !$user_to_reset ):

        debug_log('Password reset for user that doesnt match.');
        Routing::redirect_with_alert( $this->Page->url_for('password-reset') . "/{$reset_key}", ['code' => '070'] );

      endif;


      if ( $this->User->validate_pass($new_pass) ):

        Session::delete_key('reset_key');

        // Reset the password for the user that matches and redirect to a confirmation page
        $pass_updated = $this->User->update_password($user_to_reset, $new_pass);

        if ( $pass_updated ):

          Routing::redirect_with_alert( $this->Page->url_for('login'), ['code' => '101'] );

        else:

          Routing::redirect_with_alert( $this->Page->url_for('password-reset'), ['code' => '070'] );

        endif;

      else:

        Routing::redirect_with_alert( $this->Page->url_for('password-reset') . "/{$reset_key}", ['code' => '003'] );

      endif;
      
    
    // Else if all we have is a reset key then validate it
    // and redirect appropriately.
    elseif ( $reset_key ):

      // Is key the valid length, and does it contain only approved characters?
      $key_valid = ( (strlen($reset_key) == 16) && Utils::is_alphanumeric($reset_key) );

      // @todo We will delete the key either when it expires or when it is used to reset the pass
      $active_key_found = $this->User->check_password_reset_token($reset_key);

      if ( $key_valid && $active_key_found ):

        Session::set_key('reset_key', $reset_key);

        // Key was valid so redirect back to the reset page with the key in the URL
        Routing::redirect_to( $this->Page->url_for('password-reset') . "/{$reset_key}" );

      else:

        Session::delete_key('reset_key');

        // Redirect back with an error
        Routing::redirect_with_alert( $this->Page->url_for('password-reset'), ['code' => '007'] );

      endif;

    else:

      Session::delete_key('reset_key');

      Routing::redirect_with_alert( $this->Page->url_for('password-reset'), ['code' => '007'] );

    endif;

    
  } // password_reset()








  /**
   * Add a form to be processed by this class.
   *
   * @param string $key The name of the form. Used for 
   *                    reference and as the action URL.
   * @param string $method_name Name of the method in this class
   *                            that will handle this form.
   */
  private function add_form(string $key, string $method_name): void {

    // Initialize $map if it's null
    $this->map ??= [];

    $this->map[$key] = [$this, $method_name];

  } // add_form()








  /**
   * Determine with method will handle this form using our
   * map as a reference.
   */
  public function serve(string $key) {

    if (isset($this->map[$key]) && is_callable($this->map[$key])) {

      call_user_func($this->map[$key]);

    } else {

      echo "Method for '$key' not callable or does not exist.";

    }

  } // serve()








  /**
   * Return an instance of this class.
   */
  public static function get_instance( $process_route = false ): self {
  
    if (self::$instance === null):
      
      self::$instance = new self( $process_route );

    endif;
  
    return self::$instance;
  
  } // get_instance()




} // ::FormHandler