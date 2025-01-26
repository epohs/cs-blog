<?php

/**
 * Admin related page routes have their own handlers and
 * their own templates that are not themeable.
 * 
 * This class handles all of those routes.
 */



class AdminRoutes {

  
  private static $instance = null;
  
  private $Page = null;
  
  private $path = null;

  private $auth = null;

  private $user = null;



  
  private function __construct( $path ) {

    $this->Page = Page::get_instance();

    $this->path = $path;

    $this->auth = Auth::get_instance();

    $this->user = User::get_instance();
      
  } // __construct()







  /**
   * Landing page for the admin section.
   */
  public function dashboard(): void {


    $this->verified_user_redirect( $this->path );


    // If the current user is an admin load the 
    // admin dashboard, otherwise redirect home.
    if ( $this->user->is_logged_in() && Session::get_key(['user', 'role']) == 'admin' ):
      
      $this->get_template( 'dashboard' );
    
    else:
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;


  } // dashboard()
  
  




  

  /**
   * Login page.
   */
  public function login(): void {


    $this->verified_user_redirect( $this->path );

      
    if ( $this->user->is_logged_in() ):
  
      $redirect_path = ( $this->user->is_admin() ) ? 'admin/dash' : '/';

      Routing::redirect_to( $this->Page->url_for($redirect_path) );

    else:
      
      $nonce = $this->Page->set_nonce('login');
      
      $this->get_template( 'login', null, ['nonce' => $nonce] );

    endif;


  } // login()
  
  




  

  /**
   * New user sign up.
   */
  public function signup(): void {


    $this->verified_user_redirect( $this->path );

      
    // If the user is already logged in redirect to their profile
    if ( Session::get_key(['user', 'id']) ):

      Routing::redirect_to( $this->Page->url_for('profile') );

    else:
        
      // @todo Add a message to indicate it if the reason
      // you were redirected to this page is because no users
      // existed yet.
      
      $nonce = $this->Page->set_nonce('signup');
      
      $this->get_template( 'signup', null, ['nonce' => $nonce] );

    endif;


  } // signup()
  
  




  

  /**
   * Verify new user's email address.
   */
  public function verify(): void {
      
      
    $user_id = Session::get_key(['user', 'id']);

    
    if ( $user_id ):
      
      $cur_user = $this->user->get($user_id);
      
      // If user is already logged in redirect to their profile
      if ( intval($cur_user['is_verified']) == 1 ):

        Routing::redirect_to( $this->Page->url_for('profile') );

      endif;
    
    else:

      Routing::redirect_to( $this->Page->url_for('login') );
      
    endif;
    
    
    $nonce = $this->Page->set_nonce('verify');
    
    $verify_key = $cur_user['verify_key'];
      
    $this->get_template( 'verify', null, ['nonce' => $nonce, 'verify_key' => $verify_key] );


  } // verify()
  
  




  

  /**
   * Forgot password.
   * 
   * Ask for the User's email, then lead to password reset form.
   */
  public function forgot_password(): void {


    $this->verified_user_redirect( $this->path );

      
    // If the user is already logged in redirect to their profile.
    if ( Session::get_key(['user', 'id']) ):

      Routing::redirect_to( $this->Page->url_for('profile') );

    else:
      
      $nonce = $this->Page->set_nonce('forgot');
      
      $this->get_template( 'forgot', null, ['nonce' => $nonce] );

    endif;



  } // forgot_password()
  
  




  

  /**
   * Password reset.
   * 
   * Password resets require a unique key that is emailed to the 
   * address we have stored. This route either gets the 
   * password reset key from the URL segment, or it displays a form
   * to copy and paste the key from the email and re-POSTS back to 
   * this page with the key in the URL.
   * 
   * Having a valid key will display the password reset form.
   */
  public function password_reset(): void {


    $this->verified_user_redirect( $this->path );

      
    // If the user is already logged in redirect to their profile
    if ( Session::get_key(['user', 'id']) ):

      Routing::redirect_to( $this->Page->url_for('profile') );

    else:
      
      // Get reset key from URL segement.
      $reset_key = Routing::get_route_vars('key');
        
      $key_exists = ( !empty($reset_key) );


      if ( $key_exists ):

        // Is key the valid length, and does it contain only approved characters?
        $key_valid = ( (strlen($reset_key) == 16) && Utils::is_alphanumeric($reset_key) );

      else:

        // We're deleting the session key here to avoid
        // false positives. We reset the session key when
        // there is a valid key in the URL, and then test
        // it against the key passed as a hidden input field
        // when the new password form is submitted.
        Session::delete_key('reset_key');

        // We should use this variable to decide to show the key entry field.
        $key_valid = false;

      endif;


      // @todo We will delete the key either when it expires or when it is used to reset the pass
      $active_key_found = ( $key_valid ) ? $this->user->check_password_reset_token($reset_key) : false;


      if ( $key_exists && !$active_key_found ):

        Routing::redirect_with_alert( $this->Page->url_for('password-reset'), ['code' => '007'] );

      endif;


      $nonce = $this->Page->set_nonce('password-reset');
      
      $tmpl_args = [
                    'nonce' => $nonce,
                    'key_exists' => $key_exists,
                    'active_key_found' => $active_key_found,
                    'reset_key' => $reset_key
                  ];
      
      $this->get_template( 'password-reset', null, $tmpl_args );


    endif;


  } // password_reset()
  






  /**
   * In order to view most Admin routes the user must have validated
   * their email address.
   * 
   * This function handles redirecting non-verified users
   */
  private function verified_user_redirect(): void {

    if (
        $this->user->is_logged_in() &&
        /* @internal I don't think this is needed if I just don't include this function in those routes.
        !( Routing::is_route('verify', $this->path) || 
           Routing::is_route('form-handler', $this->path) ) &&  
        */
        !$this->user->is_verified()
      ):

      Routing::redirect_to( $this->Page->url_for('verify') );

    endif;

  } // verified_user_redirect()
  
  
  
  
  
  
  
  /**
   * We treat template files the same as partials
   * except instead of being served from the /partials/
   * sub-directory, they're served directly out of
   * the theme root. So, this function is just a thin
   * wrapper around the get_partial() function, but we
   * change the root directory.
   */
  public function get_template(string $file, ?string $suffix = null, $args = false): void {
    
    
    $this->Page->get_partial($file, $suffix, $args, 'admin');


    // @todo Reassess this.
    //
    // get_template() should never be called twice, so
    // we can ditch the page_alert session here.
    Session::delete_key('page_alert');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   *
   * @todo I don't think we need to pass $path. The only
   *       thing it's being used for is validate_user_redirect
   *       and the code that used it is commented out.
   *       If it can be removed here, it can also likely be removed
   *       from Routes as well.
   */
  public static function get_instance( $path ): self {
  
    if (self::$instance === null):
      
      self::$instance = new self( $path );
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::AdminRoutes
