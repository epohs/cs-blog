<?php



use League\HTMLToMarkdown\HtmlConverter;



/**
 * Admin related page routes have their own handlers and
 * their own templates that are not themeable.
 * 
 * This class handles all of those routes.
 */
class AdminRoutes {

  
  private static $instance = null;
  
  private $Page = null;

  private $Auth = null;

  private $User = null;



  
  private function __construct() {

    $this->Page = Page::get_instance();

    $this->Auth = Auth::get_instance();

    $this->User = User::get_instance();
      
  } // __construct()







  /**
   * Landing page for the admin section.
   */
  public function dashboard(): void {


    $this->verified_user_redirect();


    // If the current user is an admin load the 
    // admin dashboard, otherwise redirect home.
    if ( $this->User->is_logged_in() && Session::get_key(['user', 'role']) == 'admin' ):
      
      $this->get_template( 'dashboard' );
    
    else:
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;


  } // dashboard()
  
  
  
  
  
  
  
  
  public function new_post(): void {


    $this->verified_user_redirect();

      
    $nonce = $this->Auth::set_nonce('new-post');
    
    $converter = new HtmlConverter(array('strip_tags' => true));
    
    $this->get_template( 'post/new', null, ['nonce' => $nonce, 'converter' => $converter] );
    

  } // new_post()
  
  
  
  
  
  
  
  
  public function edit_post(): void {
    

    $this->verified_user_redirect();
    
    
    $selector = Routing::get_route_vars('selector');
    
    
    // @todo Add a function to apply basic validation to a selector
    if ( $selector && strlen($selector) >= 5 ):
    
      $Post = Post::get_instance();
      
      $post_to_edit = $Post->get_by('selector', $selector);
      
      if ( !$post_to_edit ):
        
        Routing::redirect_with_alert( $this->Page->url_for('admin/dash'), ['code' => '200'] );
        
      endif;
    
    else:
        
      Routing::redirect_with_alert( $this->Page->url_for('admin/dash'), ['code' => '200'] );
      
    endif;
    
    
    $Parsedown = new Parsedown();
    
    debug_log("Post content before parsing: {$post_to_edit['content']}");
    
    
    $post_to_edit['content'] = $Parsedown->text($post_to_edit['content']);
    
    debug_log("Post content after parsing: {$post_to_edit['content']}");
    
    $nonce = $this->Auth::set_nonce('edit-post');
    
    $this->get_template( 'post/edit', null, ['nonce' => $nonce, 'post' => $post_to_edit] );
    

  } // edit_post()

  

  
  
  
  
  
  /**
   * Login page.
   */
  public function login(): void {


    $this->verified_user_redirect();

      
    if ( $this->User->is_logged_in() ):
  
      $redirect_path = ( $this->User->is_admin() ) ? 'admin/dash' : '/';

      Routing::redirect_to( $this->Page->url_for($redirect_path) );

    else:
      
      $nonce = $this->Auth::set_nonce('login');
      
      $this->get_template( 'login', null, ['nonce' => $nonce] );

    endif;


  } // login()
  
  




  

  /**
   * New user sign up.
   */
  public function signup(): void {


    $this->verified_user_redirect();

      
    // If the user is already logged in redirect to their profile
    if ( Session::get_key(['user', 'id']) ):

      Routing::redirect_to( $this->Page->url_for('profile') );

    else:
      
      $nonce = $this->Auth::set_nonce('signup');
      
      $this->get_template( 'signup', null, ['nonce' => $nonce] );

    endif;


  } // signup()
  
  




  

  /**
   * Verify new user's email address.
   */
  public function verify(): void {
      
      
    $user_id = Session::get_key(['user', 'id']);

    
    if ( $user_id ):
      
      $cur_user = $this->User->get($user_id);
      
      // If user is already logged in redirect to their profile
      if ( intval($cur_user['is_verified']) == 1 ):

        Routing::redirect_to( $this->Page->url_for('profile') );

      endif;
    
    else:

      Routing::redirect_to( $this->Page->url_for('login') );
      
    endif;
    
    
    $nonce = $this->Auth::set_nonce('verify');
    
    $verify_key = ( isset($_GET['key']) ) ? trim($_GET['key']) : '';
      
    $this->get_template( 'verify', null, ['nonce' => $nonce, 'verify_key' => $verify_key] );


  } // verify()
  
  




  

  /**
   * Forgot password.
   * 
   * Ask for the User's email, then lead to password reset form.
   */
  public function forgot_password(): void {


    $this->verified_user_redirect();

      
    // If the user is already logged in redirect to their profile.
    if ( Session::get_key(['user', 'id']) ):

      Routing::redirect_to( $this->Page->url_for('profile') );

    else:
      
      $nonce = $this->Auth::set_nonce('forgot');
      
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


    $this->verified_user_redirect();

      
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


      $active_key_found = ( $key_valid ) ? $this->User->check_password_reset_token($reset_key) : false;


      if ( $key_exists && !$active_key_found ):

        Routing::redirect_with_alert( $this->Page->url_for('password-reset'), ['code' => '007'] );

      endif;


      $nonce = $this->Auth::set_nonce('password-reset');
      
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

    if ( $this->User->is_logged_in() && !$this->User->is_verified() ):

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

    // Do this to avoid triggering an alert on a page refresh.
    Session::delete_key('page_alert');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::AdminRoutes
