<?php



class FormHandler {

  private static $instance = null;

  private $page = null;

  private $auth = null;

  private $user = null;

  private $map = null;

  private $post_vars = null;

  private $nonce = null;
  
  private $limits = null;






  private function __construct() {

    
    $this->page = Page::get_instance();

    $this->auth = Auth::get_instance();

    $this->user = User::get_instance();
    
    $this->limits = RateLimits::get_instance();


    $this->add_form('login', 'login');
    $this->add_form('verify', 'verify');
    $this->add_form('signup', 'signup');
    $this->add_form('forgot', 'forgot_password');
    $this->add_form('password-reset', 'password_reset');
    
    
    $this->limits->set('form_login', 5, '5 minutes');

    
  } // __construct()







  public function process() {


    $post_vars = Routing::clean_post_vars( $_POST );


    if ( isset($post_vars['form_name']) && isset($post_vars['nonce']) ):
      
      
      $form_name = $post_vars['form_name'];
      $nonce = $post_vars['nonce'];



      if ( is_array($this->map) && array_key_exists($form_name, $this->map) ):

        $this->post_vars = $post_vars;

        $this->nonce = $nonce;

        $this->serve( $form_name );

      else:

        echo '<br>bad form name<br>';
        
        echo 'Post vars: ' . var_export($post_vars['form_name'], true);  

      endif;




    else:

      echo '<br>bad post<br>';
        
      echo 'Post vars: ' . var_export($post_vars, true);

    endif;



    // Clear temporarilly stored values to avoid problems on the next request
    // @todo think of a cleaner way to do this
    //$this->post_vars = null;
    //$this->nonce = null;

  } // process()













  private function login() {




    

    if ( !$this->limits->check('form_login') ):

      echo "Too many login attempts. Please try again after " . $this->limits->get_retry_after('form_login') . ".";
      exit;

    endif;





    Routing::nonce_redirect($this->nonce, 'login');
               
    $user_to_login = $this->user->get_by('email', $this->post_vars['email']);
    
    
    
    if ( $user_to_login ):
      
      if ( password_verify($this->post_vars['password'], $user_to_login['password']) ):

        // If the user isn't verified then we don't want to update 
        // the last_login timestamp.
        $update_last_login = ( intval($user_to_login['is_verified']) == 1 ) ? true : false;

        // Optionally set a cookie to remember user across sessions
        $remember_me = ( isset($this->post_vars['remember_me']) && (intval($this->post_vars['remember_me']) == 1) ) ? true : false;

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
        
        Routing::redirect_to( $this->page->url_for('admin/dash') );
        
      else:
        
        Routing::redirect_to( $this->page->url_for('/') );
        
      endif;
      
    else:
      
      
      // If the login was bad, increment the failed_login_attempts
      // column in the User table and redirect back to 
      // the login page with an error.
      //
      // @toto add failed_login_attempts increment.
      Routing::redirect_to( $this->page->url_for('login') . '?err=005' );
      
    endif;


  } // login()











  private function verify() {
        
        
    Routing::nonce_redirect($this->nonce, 'verify');
    
    // If there is not a user_id in the session
    // Redirect back to the login screen.
    if ( !$user_id = Session::get_key(['user', 'id']) ):
      
      Routing::redirect_to( $this->page->url_for('login') );
      
    endif;
    
    
    $passed_verify_code = ( isset($this->post_vars['verify_key']) ) ? $this->post_vars['verify_key'] : false;
    
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
        
        Routing::redirect_to( $this->page->url_for('admin/profile') );
        
      else:
        
        Routing::redirect_to( $this->page->url_for('profile') );
        
      endif;
      
      
    // Otherwise, redirect back to verify page with an error
    else:
      
      Routing::redirect_to( $this->page->url_for('verify') . '?err=004' );
      
    endif;


  } // verify()











  private function signup() {

        
      
    Routing::nonce_redirect($this->nonce, 'signup');
        
        
    $user_email = isset($this->post_vars['email']) ? $this->post_vars['email'] : false;
    
    $user_pass = isset($this->post_vars['password']) ? $this->post_vars['password'] : '';
    
    
    
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
        
        
        Routing::redirect_to( $this->page->url_for('verify') );
        
      else:
        
        Routing::redirect_to( $this->page->url_for('signup') . '?err=070' );
        
      endif;
      
      
    elseif ( $this->user->user_exists($user_email) ):
      
      Routing::redirect_to( $this->page->url_for('signup') . '?err=002' );
        
    elseif ( !$this->user->validate_pass($user_pass) ):
      
      Routing::redirect_to( $this->page->url_for('signup') . '?err=003' );
        
    else:
      
      Routing::redirect_to( $this->page->url_for('signup') . '?err=070' );
      
    endif;


  } // signup()











  private function forgot_password() {



    Routing::nonce_redirect($this->nonce, 'forgot');
        
        
    $user_email = isset($this->post_vars['email']) ? $this->post_vars['email'] : false;


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
      Routing::redirect_to( $this->page->url_for('password-reset') );


    else:

      Routing::redirect_to( $this->page->url_for('forgot') . '?err=006' );

    endif;


  } // forgot_password()











  private function password_reset() {


    Routing::nonce_redirect($this->nonce, 'password-reset');


    $reset_key = ( isset($this->post_vars['reset_key']) ) ? $this->post_vars['reset_key'] : false;
    $session_key = Session::get_key('reset_key');
    $keys_match = ( $reset_key === $session_key );
    $new_pass = ( isset($this->post_vars['new_pass']) ) ? $this->post_vars['new_pass'] : false;


    // If we have both a key and a new password
    // then validate both and reset the password for this user
    // @todo
    if ( $reset_key && $keys_match && $new_pass ):

      $user_to_reset = $this->user->check_password_reset_token($reset_key);

      // This should never really happen because a user should never be able to get
      // to the form that allows them to enter a new password if they don't have a reset key
      // that matches a user, but to be safe 
      if ( !$user_to_reset ):

        Routing::redirect_to( $this->page->url_for('password-reset') . "/{$reset_key}?err=070" );

      endif;


      if ( $this->user->validate_pass($new_pass) ):

        Session::delete_key('reset_key');

        // Reset the password for the user that matches and redirect to a confirmation page
        $pass_updated = $this->user->update_password($user_to_reset, $new_pass);

        if ( $pass_updated ):

          Routing::redirect_to( $this->page->url_for('login') . "?msg=101" );

        else:

          Routing::redirect_to( $this->page->url_for('password-reset') . "?err=070" );

        endif;

      else:

        Routing::redirect_to( $this->page->url_for('password-reset') . "/{$reset_key}?err=003" );

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
        Routing::redirect_to( $this->page->url_for('password-reset') . "/{$reset_key}" );

      else:

        Session::delete_key('reset_key');

        // Redirect back with an error
        Routing::redirect_to( $this->page->url_for('password-reset') . '?err=007' );

      endif;

    else:

      Session::delete_key('reset_key');

      Routing::redirect_to( $this->page->url_for('password-reset') . '?err=007' );

    endif;

    
  } // password_reset()













  private function add_form(string $key, string $method_name) {

    // Initialize $map if it's null
    $this->map ??= [];

    $this->map[$key] = [$this, $method_name];

  } // add_form()









  public function serve(string $key) {

    if (isset($this->map[$key]) && is_callable($this->map[$key])) {

      call_user_func($this->map[$key]);

    } else {

      echo "Method for '$key' not callable or does not exist.";

    }

  } // serve()








  public static function get_instance( $process_route = false ) {
  
    if (self::$instance === null):
      
      self::$instance = new self( $process_route );

    endif;
  
    return self::$instance;
  
  } // get_instance()




} // ::FormHandler