<?php




class Admin {

  
  private static $instance = null;
  
  

  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
  
  
  
  /**
   * Determine which template will handle our  request,
   * and prepare the data for thatpage.
   *
   * @internal I don't like that this method is public
   *
   */
  public function serve_route( $path ) {
    

    if ( Routes::is_route('login', $path) ):
    
    
      $this->get_template( 'login', null, false );
      
      
    elseif ( Routes::is_route('signup', $path) ):
      
      // @todo Add a message to indicate it if the reason
      // you were redirected to this page is because no users
      // existed yet.
      
      $page = Page::get_instance();
      
      $nonce = $page->set_nonce('signup');
      
      $this->get_template( 'signup', null, ['nonce' => $nonce] );
    

    elseif ( Routes::is_route('verify', $path) ):
      
      
      $page = Page::get_instance();
      
      $nonce = $page->set_nonce('signup');
      
      $user_id = Session::get_key('user_id');
      
      if ( $user_id ):
    
        $user = User::get_instance();
        
        $cur_user = $user->get_by($user_id);
        
        $verify_key = $cur_user['verify_key'];
      
      else:
        
        $verify_key = null;
        
      endif;
          
        
      $this->get_template( 'verify', null, ['nonce' => $nonce, 'verify_key' => $verify_key] );
      
      
    elseif ( Routes::is_route('admin/form-handler', $path) ):
      
      
      $this->form_handler();
      
      
    else:
    
      
      $page = Page::get_instance();
      
      $page->get_template( '404' );
      
      
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
    
    
    $page = Page::get_instance();
    
    $page->get_partial($file, $suffix, $args, 'admin');
    
    
  } // get_template()
  
  
  
  
  
  
  
  
  
  /**
   * Endpoint for all admin forms
   *
   *
   */
  private function form_handler(): void {
    
      
    $post_vars = Routes::clean_post_vars( $_POST );
    
    
    if ( isset($post_vars['form_name']) && isset($post_vars['nonce']) ):
      
      
      $page = Page::get_instance();
      
      $form_name = $post_vars['form_name'];
      $nonce = $post_vars['nonce'];
      
      
      // Verify new user email
      if ( $form_name == 'verify' ):
        
        
        // If there is not a user_id in the session
        // Redirect back to the login screen.
        if ( !$user_id = Session::get_key('user_id') ):
          
          // @todo logging in a non-verified user should
          // not set last login timestame and redirect to
          // verify page.
          Routes::redirect_to( $page->url_for('login') );
          
        endif;
        
        
        $user = User::get_instance();
        
        $passed_verify_code = ( isset($post_vars['verify_key']) ) ? $post_vars['verify_key'] : false;
        
        $user_to_verify = $user->get_by($passed_verify_code, 'verify_key');
        
        // If there is a user with a key entered
        // and that user_id matches the user_id in the session
        // then log the user in setting the last login
        // timestamp, remove their verify key, and
        // redirect to their profile page.
        if ( isset($user_to_verify['id']) && ($user_to_verify['id'] === $user_id) ):
          
          
          $auth = Auth::get_instance();
          
          $auth->login( $user_id );
          
          
          $user->remove_verify_key( $user_id );
          
          
          // If the user is an admin user use that profile page
          // otherwise, use the non-admin profile page.
          if ( Session::get_key('user_role') == 'admin' ):
            
            Routes::redirect_to( $page->url_for('admin/profile') );
            
          else:
            
            Routes::redirect_to( $page->url_for('profile') );
            
          endif;
          
          
        // Otherwise, redirect back to verify page with an error
        else:
          
          Routes::redirect_to( $page->url_for('verify') . '?err=004' );
          
        endif;
      
        
      
      
      // New user sign up form
      elseif ( $form_name == 'signup' ):
        
      
        Routes::nonce_redirect($nonce, 'signup', 'signup');
        
        $user = User::get_instance();
        
        $user_email = isset($post_vars['email']) ? $post_vars['email'] : false;
        
        $user_pass = isset($post_vars['password']) ? $post_vars['password'] : '';
        
        
        
        if ( $user_email && !$user->user_exists($user_email) && $user->validate_pass($user_pass) ):
          
          
          $user_data = [
            'email' => $user_email,
            'password' => $user_pass
          ];
          
          $new_user_id = $user->new($user_data);
          
          if ( $new_user_id ):
            
            $auth = Auth::get_instance();
            
            // Manually set logged in cookie and session but
            // do not set last login timestamp.            
            $auth->login($new_user_id, false);
            
            
            Routes::redirect_to( $page->url_for('verify') );
            
          else:
            
            Routes::redirect_to( $page->url_for('signup') . '?err=070' );
            
          endif;
          
          
        elseif ( $user->user_exists($user_email) ):
          
          Routes::redirect_to( $page->url_for('signup') . '?err=002' );
            
        elseif ( !$user->validate_pass($user_pass) ):
          
          Routes::redirect_to( $page->url_for('signup') . '?err=003' );
            
        else:
          
          Routes::redirect_to( $page->url_for('signup') . '?err=070' );
          
        endif;
        
        
      else:
        
        echo '<br>bad post<br>';
        
        echo 'Post vars: ' . var_export($post_vars, true);  
      
      endif;
      
      
    endif;
    
    
  } // form_handler()
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Admin
