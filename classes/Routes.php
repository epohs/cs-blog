<?php

/**
 * Hande the logic for individual routes in the application.
 * 
 */
class Routes {

  
  private static $instance = null;

  private $Page = null;

  private $User = null;

  private $AdminRoutes = null;

  private $FormHandler = null;

  // Map a URL segment to a function that handles
  // the logic for a specific route.
  public $map = null;

  
  
  
  
  
  
  
  private function __construct( $Page ) {


    $this->Page = $Page;

    $this->User = User::get_instance();

    $this->AdminRoutes = AdminRoutes::get_instance();

    $this->FormHandler = FormHandler::get_instance();
    

    // ---- Public Routes
    $this->add_route('/', $this, 'home');
    $this->add_route('profile', $this, 'profile');
    $this->add_route('logout', $this, 'logout');
    //$this->add_route('post', $this, 'post');
    $this->add_route('404', $this, '_404');


    // ---- Admin Routes
    $this->add_route('admin/dash', $this->AdminRoutes, 'dashboard');
    
    // Posts
    $this->add_route('admin/post/new', $this->AdminRoutes, 'new_post');
    $this->add_route('admin/post/edit/{selector}', $this->AdminRoutes, 'edit_post');
    $this->add_route('admin/post/list', $this->AdminRoutes, 'list_posts');
    
    // Users
    $this->add_route('admin/user/list', $this->AdminRoutes, 'list_users');
    $this->add_route('admin/user/edit/{selector}', $this->AdminRoutes, 'edit_user');
    
    // User Auth
    $this->add_route('login', $this->AdminRoutes, 'login');
    $this->add_route('signup', $this->AdminRoutes, 'signup');
    $this->add_route('verify', $this->AdminRoutes, 'verify');
    $this->add_route('forgot', $this->AdminRoutes, 'forgot_password');
    $this->add_route('password-reset/{key?}', $this->AdminRoutes, 'password_reset');


    // Forms
    $this->add_route('form-handler', $this->FormHandler, 'process');

    
  } // __construct()

  
  
  
  
  
  
  
  /**
   * Homepage.
   */
  private function home(): void {

    $this->Page->get_template( ['index'] );

  } // home()

  
  
  
  
  

  
  /**
   * User profile.
   */
  private function profile(): void {


    if ( $this->User->is_logged_in() ):

      $cur_user = $this->User->get( Session::get_key(['user', 'id']) );
    
      $this->Page->get_template( ['profile'], null, ['cur_user' => $cur_user] );

    else:

      Routing::redirect_to( $this->Page->url_for('/') );

    endif;


  } // profile()
  
  
  
  
  
  
  
  
  /**
   * User logout.
   */
  private function logout(): void {
      

    $auth = Auth::get_instance();
  
    $auth->logout();
    
    Routing::redirect_to( $this->Page->url_for('/') );


  } // logout()
  
  
  
  
  
  
  
  
  /**
   * Page not found.
   */
  private function _404(): void {
    
    http_response_code(404);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
    header("X-Robots-Tag: noindex, nofollow");
    
    $this->Page->get_template( ['404'] );

  } // 404()
  
  
  
  
  
  
  
  
  /**
   * Add a route to the map property.
   */
  private function add_route(string $key, $class_instance, string $method_name): void {

    // Initialize $map if it's null
    $this->map ??= [];

    $this->map[$key] = [$class_instance, $method_name];

  } // add_route()
  
  
  
  
  
  
  
  
  /**
   * Get the route map.
   */
  public function get_routes(): array|null {


    return $this->map;


  } // get_routes()
  
  
  
  
  
  
  
  
  /**
   * Call the appropriate route handler function
   * as registered in the route map.
   *
   * @todo Is this the best place to call admin functions?
   */
  public function serve(string $key): bool {
    

    if ( isset($this->map[$key]) && is_callable($this->map[$key]) ):
      
      
      $this->Page->set_prop('cur_page', $key);
      
      
      if ( $this->Page->is_admin() ):
        
        $admin_functions_file = ROOT_PATH . "admin/functions.php";
        
        if ( file_exists($admin_functions_file) ):
          
          require_once( $admin_functions_file );
        
        endif;
        
      endif;
      

      call_user_func($this->map[$key]);
      
      return true;
      

    else:

      debug_log("Method for '$key' not callable or does not exist.");
      
      return false;

    endif;
    

  } // serve()
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance( $Page ): self {
  
    if (self::$instance === null):
      
      self::$instance = new self( $Page );
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()


    
} // ::Routes
