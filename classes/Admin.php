<?php




class Admin {

  
  private static $instance = null;
  
  private $page = null;

  private $auth = null;

  private $user = null;



  
  private function __construct() {

    $this->page = Page::get_instance();

    $this->auth = Auth::get_instance();

    $this->user = User::get_instance();
      
  } // __construct()
  
  
  
  
  
  
  
  
  
  
  
  /**
   * Determine which template will handle our  request,
   * and prepare the data for thatpage.
   *
   * @internal I don't like that this method is public
   *
   */
  public function serve_route( $path ) {


    
    if ( 
        $this->auth->is_logged_in() &&
        !( 
          Routes::is_route('verify', $path) || 
          Routes::is_route('admin/form-handler', $path) 
        ) &&
        Session::get_key(['user', 'role']) == 'null'
        ):

        Routes::redirect_to( $this->page->url_for('verify') );

    endif;




    
    if ( Routes::is_route('admin/dash', $path) ):
      
      
      // If the current user is an admin load the 
      // admin dashboard, otherwise redirect home.
      if ( $this->auth->is_logged_in() && Session::get_key(['user', 'role']) == 'admin' ):
        

        $this->get_template( 'dashboard' );
      
      else:
        
        Routes::redirect_to( $this->page->url_for('/') );
        
      endif;
      
      
    elseif ( Routes::is_route('admin/profile', $path) ):



      // If the current user is an admin load the 
      // admin dashboard, otherwise redirect home.
      if ( $this->auth->is_logged_in() && Session::get_key(['user', 'role']) == 'admin' ):
        
        $user = User::get_instance();

        $cur_user = ( ( Session::get_key(['user', 'id']) ) ) ? $user->get( Session::get_key(['user', 'id']) ) : null;
        
        $this->get_template( 'profile', null, ['cur_user' => $cur_user] );
        
      else:
        
        Routes::redirect_to( $this->page->url_for('/') );
        
      endif;

    // Login page
    elseif ( Routes::is_route('login', $path) ):

      
      if ( $this->auth->is_logged_in() ):
    
        $redirect_path = ( $this->auth->is_admin() ) ? 'admin/dash' : '/';

        Routes::redirect_to( $this->page->url_for($redirect_path) );

      else:
        
        $nonce = $this->page->set_nonce('login');
        
        $this->get_template( 'login', null, ['nonce' => $nonce] );

      endif;
      
    
    // Initial sign up page for creating new user
    elseif ( Routes::is_route('signup', $path) ):

      
      // If the user is already logged in redirect to their profile
      if ( Session::get_key(['user', 'id']) ):

        Routes::redirect_to( $this->page->url_for('profile') );

      else:
          
        // @todo Add a message to indicate it if the reason
        // you were redirected to this page is because no users
        // existed yet.
        
        $nonce = $this->page->set_nonce('signup');
        
        $this->get_template( 'signup', null, ['nonce' => $nonce] );

      endif;


    elseif ( Routes::is_route('verify', $path) ):
      
      
      $user_id = Session::get_key(['user', 'id']);
      
      if ( $user_id ):
        
        $cur_user = $this->user->get($user_id);
        
        // If user is already logged in redirect to their profile
        if ( intval($cur_user['is_verified']) == 1 ):

          Routes::redirect_to( $this->page->url_for('profile') );

        endif;
      
      else:

        Routes::redirect_to( $this->page->url_for('login') );
        
      endif;
      
      
      $nonce = $this->page->set_nonce('verify');
      
      $verify_key = $cur_user['verify_key'];
        
      $this->get_template( 'verify', null, ['nonce' => $nonce, 'verify_key' => $verify_key] );

    // Forgot password
    elseif ( Routes::is_route('forgot', $path) ):

      
      // If the user is already logged in redirect to their profile
      if ( Session::get_key(['user', 'id']) ):

        Routes::redirect_to( $this->page->url_for('profile') );

      else:
        
        $nonce = $this->page->set_nonce('forgot');
        
        $this->get_template( 'forgot', null, ['nonce' => $nonce] );

      endif;


    // Password reset
    elseif ( Routes::is_route('password-reset/{key?}', $path) ):

      
      // If the user is already logged in redirect to their profile
      if ( Session::get_key(['user', 'id']) ):

        Routes::redirect_to( $this->page->url_for('profile') );

      else:
        
        // @todo Do some validation on the reset key
        // ✓ does it exist
        // ✓ is it the valid length, and does it contain only approved characters
        // ✓ check the database. does it exist, is it expired?
        // > if key doesn't exist display key entry form
        // > if it does exist and is invalid, redirect to error
        // > if it does exist and is valid, display reset form
        $reset_key = Routes::get_route_vars('key');
          
        $key_exists = ( !empty($reset_key) );


        if ( $key_exists ):

          // Is key the valid length, and does it contain only approved characters?
          $key_valid = ( (strlen($reset_key) == 16) && Utils::is_alphanumeric($reset_key) );

        else:

          // @internal We're deleting the session key here to avoid
          //            false positives. We reset the session key when
          //            there is a valid key in the URL, and then test
          //            it against the key passed as a hidden input field
          //            when the new password form is submitted.
          Session::delete_key('reset_key');

          // We will use this variable to decide to show the
          // key entry field
          $key_valid = false;

        endif;


        // @todo We will delete the key either when it expires or when it is used to reset the pass
        $active_key_found = $this->user->check_password_reset_token($reset_key);


        if ( $key_exists && !$active_key_found ):

          Routes::redirect_to( $this->page->url_for('password-reset') . '?err=007' );

        endif;


        
        $nonce = $this->page->set_nonce('password-reset');
        
        $tmpl_args = [
                      'nonce' => $nonce,
                      'key_exists' => $key_exists,
                      'key_valid' => $key_valid,
                      'active_key_found' => $active_key_found,
                      'reset_key' => $reset_key
                    ];
        
        $this->get_template( 'password-reset', null, $tmpl_args );

      endif;

      
    elseif ( Routes::is_route('admin/form-handler', $path) ):
      
      
      $this->form_handler();
      
      
    else:

      
      $this->page->get_template( '404' );
      
      
    endif;
    
    
    
  } // serve_route()
  
  
  
  
  
  
  
  
  
  
  
  /**
   * We treat template files the same as partials
   * except instead of being served from the /partials/
   * sub-directory, they're served directly out of
   * the theme root. So, this function is just a thin
   * wrapper around the get_partial() function, but we
   * change the root directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false) {
    
    
    $this->page->get_partial($file, $suffix, $args, 'admin');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  /**
   * Endpoint for all admin forms
   *
   *
   */
  private function form_handler(): void {
    
      
    $post_vars = Routes::clean_post_vars( $_POST );
    
    
    if ( isset($post_vars['form_name']) && isset($post_vars['nonce']) ):
      
      
      $form_name = $post_vars['form_name'];
      $nonce = $post_vars['nonce'];
      
      
      // User log in
      if ( $form_name == 'login' ):

        
        Routes::nonce_redirect($nonce, 'login');
               
        $user_to_login = $this->user->get_by('email', $post_vars['email']);
        
        
        
        if ( $user_to_login ):
          
          if ( password_verify($post_vars['password'], $user_to_login['password']) ):

            // If the user isn't verified then we don't want to update 
            // the last_login timestamp.
            $update_last_login = ( intval($user_to_login['is_verified']) == 1 ) ? true : false;

            // Optionally set a cookie to remember user across sessions
            $remember_me = ( isset($post_vars['remember_me']) && (intval($post_vars['remember_me']) == 1) ) ? true : false;

            $is_logged_in = $this->auth->login( $user_to_login['id'], $update_last_login, $remember_me );
            
          else:
            
            $is_logged_in = false;
            
          endif;
          
        else:
          
          $is_logged_in = false;
          
        endif;
        
        
        
        if ( $is_logged_in ):
          
          
          // If the user who just logged in is an admin
          // go to the admin panel. Otherwise, go to the
          // homepage.
          if ( Session::get_key(['user', 'role']) == 'admin' ):
            
            Routes::redirect_to( $this->page->url_for('admin/dash') );
            
          else:
            
            Routes::redirect_to( $this->page->url_for('/') );
            
          endif;
          
        else:
          
          
          // If the login was bad, increment the failed_login_attempts
          // column in the User table and redirect back to 
          // the login page with an error.
          //
          // @toto add failed_login_attempts increment.
          Routes::redirect_to( $this->page->url_for('login') . '?err=005' );
          
        endif;
        
      
      // Verify new user email
      elseif ( $form_name == 'verify' ):
        
        
        Routes::nonce_redirect($nonce, 'verify');
        
        // If there is not a user_id in the session
        // Redirect back to the login screen.
        if ( !$user_id = Session::get_key(['user', 'id']) ):
          
          Routes::redirect_to( $this->page->url_for('login') );
          
        endif;
        
        
        $passed_verify_code = ( isset($post_vars['verify_key']) ) ? $post_vars['verify_key'] : false;
        
        $user_to_verify = $this->user->get_by('verify_key', $passed_verify_code);
        
        // If there is a user with a key entered
        // and that user_id matches the user_id in the session
        // then log the user in setting the last login
        // timestamp, verify the user, and
        // redirect to their profile page.
        if ( isset($user_to_verify['id']) && ($user_to_verify['id'] === $user_id) ):
          
          
          $this->auth->login( $user_id );
          
          $this->user->verify( $user_id );

          // We override the session of a non-verified user to 
          // always be null, so after we verify a user we need to 
          // set this back to what it should be.
          Session::set_key(['user', 'role'], $user_to_verify['role']);
          
          
          // If the user is an admin user use that profile page
          // otherwise, use the non-admin profile page.
          if ( Session::get_key(['user', 'role']) == 'admin' ):
            
            Routes::redirect_to( $this->page->url_for('admin/profile') );
            
          else:
            
            Routes::redirect_to( $this->page->url_for('profile') );
            
          endif;
          
          
        // Otherwise, redirect back to verify page with an error
        else:
          
          Routes::redirect_to( $this->page->url_for('verify') . '?err=004' );
          
        endif;
        
      
      
      // New user sign up form
      elseif ( $form_name == 'signup' ):
        
      
        Routes::nonce_redirect($nonce, 'signup');
        
        
        $user_email = isset($post_vars['email']) ? $post_vars['email'] : false;
        
        $user_pass = isset($post_vars['password']) ? $post_vars['password'] : '';
        
        
        
        if ( $user_email && !$this->user->user_exists($user_email) && $this->user->validate_pass($user_pass) ):
          
          
          $user_data = [
            'email' => $user_email,
            'password' => $user_pass
          ];
          
          $new_user_id = $this->user->new($user_data);
          
          if ( $new_user_id ):
            
            // Manually set logged in cookie and session but
            // do not set last login timestamp.            
            $this->auth->login($new_user_id, false);
            
            
            Routes::redirect_to( $this->page->url_for('verify') );
            
          else:
            
            Routes::redirect_to( $this->page->url_for('signup') . '?err=070' );
            
          endif;
          
          
        elseif ( $this->user->user_exists($user_email) ):
          
          Routes::redirect_to( $this->page->url_for('signup') . '?err=002' );
            
        elseif ( !$this->user->validate_pass($user_pass) ):
          
          Routes::redirect_to( $this->page->url_for('signup') . '?err=003' );
            
        else:
          
          Routes::redirect_to( $this->page->url_for('signup') . '?err=070' );
          
        endif;



      // Forgot password form
      elseif ( $form_name == 'forgot' ):


        Routes::nonce_redirect($nonce, 'forgot');
        
        
        $user_email = isset($post_vars['email']) ? $post_vars['email'] : false;


        // If the email address entered is a valid looking email
        // address then we move forward, otherwise we redirect back
        // to the forgot password page with an error.
        if ( filter_var($user_email, FILTER_VALIDATE_EMAIL) ):

          $user_to_reset = $this->user->get_by('email', $user_email);

          if ( is_array($user_to_reset) && isset($user_to_reset['id']) ):

            // A user with this email adress was found. Set the reset password token.
            // @todo this returns a bool. Check if false and redirect with err if so.
            $this->user->set_password_reset_token( $user_to_reset['id'] );

          endif;

          // Redirect to the password reset page regardless.
          // @todo Make sure there's a message to check your email for the reset key
          //        and make sure the email is correct.
          // @todo Make it clear during signup that it's important to remember your email
          Routes::redirect_to( $this->page->url_for('password-reset') );


        else:

          Routes::redirect_to( $this->page->url_for('forgot') . '?err=006' );

        endif;
        

      // Forgot password form
      elseif ( $form_name == 'password-reset' ):


        Routes::nonce_redirect($nonce, 'password-reset');


        $reset_key = ( isset($post_vars['reset_key']) ) ? $post_vars['reset_key'] : false;
        $session_key = Session::get_key('reset_key');
        $keys_match = ( $reset_key === $session_key );
        $new_pass = ( isset($post_vars['new_pass']) ) ? $post_vars['new_pass'] : false;


        // If we have both a key and a new password
        // then validate both and reset the password for this user
        // @todo
        if ( $reset_key && $keys_match && $new_pass ):

          $user_to_reset = $this->user->check_password_reset_token($reset_key);

          // This should never really happen because a user should never be able to get
          // to the form that allows them to enter a new password if they don't have a reset key
          // that matches a user, but to be safe 
          if ( !$user_to_reset ):

            Routes::redirect_to( $this->page->url_for('password-reset') . "/{$reset_key}?err=070" );

          endif;


          if ( $this->user->validate_pass($new_pass) ):

            Session::delete_key('reset_key');

            // Reset the password for the user that matches and redirect to a confirmation page
            $pass_updated = $this->user->update_password($user_to_reset, $new_pass);

            if ( $pass_updated ):

              Routes::redirect_to( $this->page->url_for('login') . "?msg=101" );

            else:

              Routes::redirect_to( $this->page->url_for('password-reset') . "?err=070" );

            endif;

          else:

            Routes::redirect_to( $this->page->url_for('password-reset') . "/{$reset_key}?err=003" );

          endif;
          
        
        // Else if all we have is a reset key then validate it
        // and redirect appropriately
        elseif ( $reset_key ):

          // Is key the valid length, and does it contain only approved characters?
          $key_valid = ( (strlen($reset_key) == 16) && Utils::is_alphanumeric($reset_key) );

          // @todo We will delete the key either when it expires or when it is used to reset the pass
          $active_key_found = $this->user->check_password_reset_token($reset_key);

          if ( $key_valid && $active_key_found ):

            Session::set_key('reset_key', $reset_key);

            // Key was valid so redirect back to the reset page with the key in the URL
            Routes::redirect_to( $this->page->url_for('password-reset') . "/{$reset_key}" );

          else:

            Session::delete_key('reset_key');

            // Redirect back with an error
            Routes::redirect_to( $this->page->url_for('password-reset') . '?err=007' );

          endif;

        else:

          Session::delete_key('reset_key');

          Routes::redirect_to( $this->page->url_for('password-reset') . '?err=007' );

        endif;

        
      else:
        
        echo '<br>bad form name<br>';
        
        echo 'Post vars: ' . var_export($post_vars['form_name'], true);  
      
      endif;


    else:

      echo '<br>bad post<br>';
        
      echo 'Post vars: ' . var_export($post_vars, true);  
      
      
    endif;
    
    
  } // form_handler()
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Admin
