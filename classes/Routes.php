<?php


use League\HTMLToMarkdown\HtmlConverter;

class Routes {

  
  private static $instance = null;

  private $Page = null;

  private $Admin_Routes = null;

  public $map = null;









  
  private function __construct( $Page, $path ) {


    $this->Page = $Page;

    $this->Admin_Routes = Admin_Routes::get_instance($path);

    
    $this->add_route('/', $this, 'home');
    $this->add_route('profile', $this, 'profile');
    $this->add_route('post', $this, 'post');
    $this->add_route('logout', $this, 'logout');
    $this->add_route('404', $this, '_404');


    // Admin Routes
    $this->add_route('admin/dash', $this->Admin_Routes, 'dashboard');
    $this->add_route('admin/profile', $this->Admin_Routes, 'profile');
    $this->add_route('login', $this->Admin_Routes, 'login');
    $this->add_route('signup', $this->Admin_Routes, 'signup');
    $this->add_route('verify', $this->Admin_Routes, 'verify');
    $this->add_route('forgot', $this->Admin_Routes, 'forgot_password');
    $this->add_route('password-reset/{key?}', $this->Admin_Routes, 'password_reset');
    $this->add_route('admin/form-handler', $this->Admin_Routes, 'placeholder');


  
    
    
  } // __construct()

  







  private function home() {

    $this->Page->get_template( "index" );

  } // home()

  







  private function post() {
      
    $converter = new HtmlConverter(array('strip_tags' => true));
    
    $this->Page->get_template( 'post', null, ['converter' => $converter] );

  } // post()








  private function profile() {
      
      
    $auth = Auth::get_instance();


    if ( $auth->is_logged_in() && $auth->is_admin() ):

      Routing::redirect_to( $this->Page->url_for('admin/profile') );

    elseif ( $auth->is_logged_in() ):

      $user = User::get_instance();

      $cur_user = $user->get( Session::get_key(['user', 'id']) );
    
      $this->Page->get_template( 'profile', null, ['cur_user' => $cur_user] );

    else:

      Routing::redirect_to( $this->Page->url_for('/') );

    endif;


  } // profile()










  private function logout() {
      

    $auth = Auth::get_instance();
  
    $auth->logout( Session::get_key(['user', 'id']) );
    
    Routing::redirect_to( $this->Page->url_for('/') );


  } // logout()








  private function _404() {
    
    $this->Page->get_template( '404' );

  } // 404()








  private function add_route(string $key, $class_instance, string $method_name) {

    // Initialize $map if it's null
    $this->map ??= [];

    $this->map[$key] = [$class_instance, $method_name];

  } // add_route()







  public function get_routes(): array|null {


    return $this->map;


  } // get_routes()








  public function serve(string $key) {

    if (isset($this->map[$key]) && is_callable($this->map[$key])) {

      call_user_func($this->map[$key]);

    } else {

      echo "Method for '$key' not callable or does not exist.";

    }

  } // serve()







  
  public static function get_instance( $Page, $path ) {
  
    if (self::$instance === null):
      
      self::$instance = new self( $Page, $path );
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()


    
} // ::Routes
