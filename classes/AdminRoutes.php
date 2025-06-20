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
    if ( $this->User->is_admin() || $this->User->is_author() ):
      
      $this->get_template( ['dashboard'] );
    
    else:
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;


  } // dashboard()
  
  
  
  
  
  
  
  
  public function new_post(): void {


    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() && !$this->User->is_author() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
      
    
    $nonce = $this->Auth::set_nonce('new-post');
    
    $this->get_template( ['post/new'], null, ['nonce' => $nonce] );
    

  } // new_post()
  
  
  
  
  
  
  
  
  public function edit_post(): void {
    

    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() && !$this->User->is_author() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
    
    
    $selector = Routing::get_route_vars('selector');
    
    
    if ( Utils::is_valid_selector($selector) ):
    
      $Post = Post::get_instance();
      
      $post_to_edit = $Post->get_by('selector', $selector);
      
      if ( !$post_to_edit ):
        
        Routing::redirect_with_alert( $this->Page->url_for('admin/dash'), ['code' => '200'] );
        
      endif;


      if ( $this->User->is_author() ):

        $current_user_id = Session::get_key(['user', 'id']);

        if ( $post_to_edit['author_id'] !== $current_user_id ):

          Routing::redirect_with_alert( $this->Page->url_for("admin/dash"), ['code' => '200'] );

        endif;

      endif;

    
    else:
        
      Routing::redirect_with_alert( $this->Page->url_for('admin/dash'), ['code' => '200'] );
      
    endif;

    
    $nonce = $this->Auth::set_nonce('edit-post');
    $nonce_delete = $this->Auth::set_nonce('delete-post');
    
    $this->get_template( ['post/edit'], null, ['nonce' => $nonce, 'nonce_delete' => $nonce_delete, 'post' => $post_to_edit] );
    

  } // edit_post()
  
  
  
  
  
  
  
  
  /**
   * List all posts
   */
  public function list_posts(): void {


    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() && !$this->User->is_author() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
    

    $Post = Post::get_instance();


    if ( $this->User->is_admin() ):

      $posts = $Post->get_posts();
    
    else:

      $current_user_id = Session::get_key(['user', 'id']);

      $posts = $Post->get_posts(['author_id' => $current_user_id]);

    endif;
    
    $this->get_template( ['post/list'], null, ['posts' => $posts] );
    

  } // list_posts()
  
  
  
  
  
  
  
  
  /**
   * List All Users
   */
  public function list_users(): void {


    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
    

    $users = $this->User->get_users();
    
    $this->get_template( ['user/list'], null, ['users' => $users] );
    

  } // list_users()
  
  
  
  
  
  
  
  
  public function edit_user(): void {
    

    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
    
    
    $selector = Routing::get_route_vars('selector');
    
    
    if ( Utils::is_valid_selector($selector) ):
    
      $User = User::get_instance();
      
      $user_to_edit = $User->get_by('selector', $selector);
      
      if ( !$user_to_edit ):
        
        Routing::redirect_with_alert( $this->Page->url_for('admin/dash'), ['code' => '300'] );
        
      endif;
    
    else:
        
      Routing::redirect_with_alert( $this->Page->url_for('admin/dash'), ['code' => '300'] );
      
    endif;

    
    $nonce = $this->Auth::set_nonce('edit-user');
    $nonce_pass_reset = $this->Auth::set_nonce('user-pass-reset');
    $nonce_delete = $this->Auth::set_nonce('delete-user');
    
    $this->get_template( ['user/edit'], null, ['nonce' => $nonce, 'nonce_pass_reset' => $nonce_pass_reset, 'nonce_delete' => $nonce_delete, 'user' => $user_to_edit] );
    

  } // edit_user()

  
  
  
  
  
  
  
  /**
   * Delete a User confirmation page.
   */
  public function delete_user(): void {


    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;

    
    $selector = Routing::get_route_vars('selector');
    
    
    if ( $selector ):
      
      $user = $this->User->get_by('selector', $selector);
      
      if ( $user ):

        // @todo TEMP, until get_nonce() method is added to Auth.
        // @todo IMPORTANT! - If a visitor clicks the delete user button
        //       and then refreshes the confirmationpage, they are redirected back
        //       to the edit user page, which is what I want. But then if they
        //       click the delete user button again the user is deleted without
        //       being presented with the confirmation page. This bad. FIX.
        $delete_confirm_nonce = Session::get_key(['nonces', 'user-delete-confirmation-page']);

        if ( !$this->Auth->validate_nonce($delete_confirm_nonce['nonce'], 'user-delete-confirmation-page') ):

          Routing::redirect_to( $this->Page->url_for("admin/user/edit/{$selector}") );

        endif;
        
        $cur_user = Session::get_key(['user', 'id']);
        
        $deleting_myself = ( $user['id'] == $cur_user );
        
        $Post = Post::get_instance();
    
        $post_count = $Post->get_posts(['author_id' => $user['id'], 'count_only' => true]);
        

        $nonce_delete = $this->Auth::set_nonce('delete-user');
        $nonce_delete_confirm = $this->Auth::set_nonce('delete-user-confirm');

        
        $template_args = [
                          'user' => $user,
                          'post_count' => $post_count,
                          'deleting_myself' => $deleting_myself,
                          'nonce_delete' => $nonce_delete,
                          'nonce_delete_confirm' => $nonce_delete_confirm, 
                        ];
        
      
        $this->get_template( ['user/delete'], null, $template_args );
        
      else:
        
        Routing::redirect_with_alert( $this->Page->url_for("admin/dash"), ['code' => '300'] );
        
      endif;
      
    else:
      
      Routing::redirect_with_alert( $this->Page->url_for("admin/dash"), ['code' => '300'] );
      
    endif;
    

  } // delete_user()
  
  
  
  
  
  
  
  
  
  /**
   * List All Categories
   */
  public function list_categories(): void {


    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
    
    
    $Category = Category::get_instance();

    $categories = $Category->get_categories();
    
    $this->get_template( ['category/list'], null, ['categories' => $categories] );
    

  } // list_categories()
  
  
  
  
  
  
  
  
  /**
   * Create a new Category.
   */
  public function new_category(): void {


    $this->verified_user_redirect();

    
    if ( !$this->User->is_admin() ):
      
      Routing::redirect_to( $this->Page->url_for('/') );
      
    endif;
      
    
    $nonce = $this->Auth::set_nonce('new-category');
    
    $this->get_template( ['category/new'], null, ['nonce' => $nonce] );
    

  } // new_category()
  
  

  
  
  
  
  
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
      
      $this->get_template( ['login'], null, ['nonce' => $nonce] );

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
      
      $this->get_template( ['signup'], null, ['nonce' => $nonce] );

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
      
    $this->get_template( ['verify'], null, ['nonce' => $nonce, 'verify_key' => $verify_key] );


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
      
      $this->get_template( ['forgot'], null, ['nonce' => $nonce] );

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
      
      $this->get_template( ['password-reset'], null, $tmpl_args );


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
  public function get_template(string|array $file_opts, ?string $suffix = null, $args = false): void {
    
    
    $this->Page->get_partial($file_opts, $suffix, $args, 'admin');

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
