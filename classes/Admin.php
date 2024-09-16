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

    // @todo figure out a way to redirect all admin-ish actions
    //        to the verify page
    //
    // if ( 
    //     $this->auth->is_logged_in() &&
    //     !Routes::is_route('verify', $path) $$
    //     Session::get_key('user_role') == 'notverified'
    //     ):

    //     Routes::redirect_to( $this->page->url_for('verify') );

    // endif;




    
    if ( Routes::is_route('admin/dash', $path) ):
      
      
      // If the current user is an admin load the 
      // admin dashboard, otherwise redirect home.
      if ( $this->auth->is_logged_in() && Session::get_key('user_role') == 'admin' ):
        

        $this->get_template( 'dashboard' );
      
      else:
        
        Routes::redirect_to( $this->page->url_for('/') );
        
      endif;
      
      
    elseif ( Routes::is_route('admin/profile', $path) ):

      
      $this->get_template( 'profile' );


    // Login page
    elseif ( Routes::is_route('login', $path) ):
      
      
      $nonce = $this->page->set_nonce('login');

      
      if ( $this->auth->is_logged_in() ):
    
        $redirect_path = ( $this->auth->is_admin() ) ? 'admin/dash' : '/';

        Routes::redirect_to( $this->page->url_for($redirect_path) );

      else:

        $this->get_template( 'login', null, ['nonce' => $nonce] );

      endif;
      
    
    // Initial sign up page for creating new user
    elseif ( Routes::is_route('signup', $path) ):

      
      // @todo Add a message to indicate it if the reason
      // you were redirected to this page is because no users
      // existed yet.
      
      $nonce = $this->page->set_nonce('signup');
      
      
      $this->get_template( 'signup', null, ['nonce' => $nonce] );

    

    elseif ( Routes::is_route('verify', $path) ):
      
      
      $nonce = $this->page->set_nonce('signup');
      
      $user_id = Session::get_key('user_id');
      
      if ( $user_id ):
        
        $cur_user = $this->user->get_by($user_id);
        
        $verify_key = $cur_user['verify_key'];
      
      else:
        
        $verify_key = null;
        
      endif;
          
        
      $this->get_template( 'verify', null, ['nonce' => $nonce, 'verify_key' => $verify_key] );
      
      
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

        
        Routes::nonce_redirect($nonce, 'login', 'login');
               
        $user_to_login = $this->user->get_by($post_vars['email'], 'email');
        
        
        
        if ( $user_to_login ):
          
          if ( password_verify($post_vars['password'], $user_to_login['password']) ):

            // If the user isn't verified then we don't want to update 
            // the last_login timestamp.
            $update_last_login = ( intval($user_to_login['is_verified']) == 1 ) ? true : false;

            // Optionally set a cookie to remember user across sessions
            $remember_me = ( intval($post_vars['remember_me']) == 1 ) ? true : false;

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
          if ( Session::get_key('user_role') == 'admin' ):
            
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
        
        
        // If there is not a user_id in the session
        // Redirect back to the login screen.
        if ( !$user_id = Session::get_key('user_id') ):
          
          // @todo logging in a non-verified user should
          // not set last login timestame and redirect to
          // verify page.
          Routes::redirect_to( $this->page->url_for('login') );
          
        endif;
        
        
        $passed_verify_code = ( isset($post_vars['verify_key']) ) ? $post_vars['verify_key'] : false;
        
        $user_to_verify = $this->user->get_by($passed_verify_code, 'verify_key');
        
        // If there is a user with a key entered
        // and that user_id matches the user_id in the session
        // then log the user in setting the last login
        // timestamp, verify the user, and
        // redirect to their profile page.
        if ( isset($user_to_verify['id']) && ($user_to_verify['id'] === $user_id) ):
          
          
          $this->auth->login( $user_id );
          
          $this->user->verify( $user_id );
          
          
          // If the user is an admin user use that profile page
          // otherwise, use the non-admin profile page.
          if ( Session::get_key('user_role') == 'admin' ):
            
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
        
      
        Routes::nonce_redirect($nonce, 'signup', 'signup');
        
        
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
