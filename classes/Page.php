<?php


/**
 * Handles the preparation and display of all pages, including logic for
 * metadata, internal URLs, error display, and template rendering.
 *
 * This class is made public in all templates.
 */

class Page {

  
  private static $instance = null;
  
  private array $cur_page_props = [];

  private $Alerts = null;
  
  private $Config = null;
  
  private $Db = null;

  private $User = null;

  private $Routing = null;
  
  private $partial_root = 'partials';
  
  


  



  private function __construct() {
    
    

    $this->Alerts = Alerts::get_instance();

    $this->Config = Config::get_instance();
    

    $this->Db = Database::get_instance();
    
    $this->User = User::get_instance();

    $this->Routing = Routing::get_instance();
    
    
  } // __construct();
  
  
  





  /**
   * Is the page currently being viewed an admin level page?
   */
  public function is_admin(): bool {

    return $this->Routing->get_is_admin_route() ?? false;

  } // is_admin()


  

  
  

  
  /**
   * Return the URL of this website without a trailing slash.
   * 
   * Uses the value set in our config process to determine the URL.
   */
  public function site_root(): string {
    
    return rtrim($this->Config->get('site_root'), '/');
    
  } // site_root()
  
  
  
  
  

  
  
  /**
   * Take a string representing a route within this website and
   * return a fully formed absolute URL.
   */
  public function url_for( string $path ): string {
    
    $path = ( $path === '/' ) ? '' : $path;
    
    $site_root = $this->site_root();
    
    return $site_root . '/' . $path;
    
  } // url_for()
  







  /**
   * Get the full URL for the current page.
   */
  public function get_url(): string|null {

    $return = '';

    $path = $this->Routing->get_path();

    if ( !is_null($path) && array_key_exists('segments', $path) ):

      $joined_path = implode('/', $path['segments']);

      $return = $this->url_for($joined_path);

    endif;


    return $return;

  } // get_url()
  
  
  
  
  

  
  
  /**
   * Get the page title for the current page.
   * 
   * Mostly used for the HTML <title>.
   */
  public function get_page_title(): string {
    
    return $this->Config->get('site_name');
    
  } // get_page_title()
  
  
  
  
  
  
  
  
  /**
   * @todo this need to be thought about and fleshed out
   * a lot.
   */
  public function get_prop( ?string $key = null ): mixed {
    
    
    if ( array_key_exists($key, $this->cur_page_props) ):
      
      return $this->cur_page_props[$key];
      
    elseif ( is_null($key) ):
      
      return $this->cur_page_props;
      
    else:
      
      return false;
      
    endif;
    
    
  } // get_prop()
  
  
  
  
  
  
  
  
  /**
   * @todo this need to be thought about and fleshed out
   * a lot.
   */
  public function set_prop( string $key, mixed $value ): void {
    
    $this->cur_page_props[$key] = $value;
    
  } // set_prop()
  
  
  

  
  
  
  /**
   * Render the primary HTML template file for a page.
   * 
   * We treat template files the same as partials except instead of being 
   * served from the /partials/ sub-directory, they're served directly out 
   * of the theme root. This function is a thin wrapper around the 
   * get_partial() function, but we change the serving directory.
   */
  public function get_template(string|array $file_opts, ?string $suffix = null, $args = false): void {
    

    $this->get_partial($file_opts, $suffix, $args, '');


    // After the page has been viewed, remove page alerts to avoid a
    // situation where multiple alerts are triggered during a page
    // refresh.
    Session::delete_key('page_alert');
    
    
  } // get_template()
  
  
  
  
  

  
  
  /**
   * Render a piece of an HTML template for a page.
   * 
   * This function will look for a file containing the HTML for a partial,
   * first in the active theme, and if the file isn't found it will look
   * in the 'default' theme directory for the same file. This allows for 
   * themes that customize only part of the default theme.
   * 
   * @todo Re-document this, and test more thoroughly after change to $file_opts
   * 
   */
  public function get_partial(string|array $file_opts, ?string $suffix = null, $args = false, $partial_root = false): bool {
    
    
    $Config = Config::get_instance();
    
    $Defaults = Defaults::get_instance();
    
    $theme = $Config->get('theme');
    
    $default_theme = $Defaults->get('theme');
    
    $fallback_path = false;
    
    $found_partial = false;
    
    
    
    // If $partial_root is false we're looking for a true
    // partial, so look in the theme's partials folder.
    if ( $partial_root === false ):
      
      
      $file_base = "themes/%s/{$this->partial_root}/";

      
    // If $partial_root is an empty string we're looking for
    // a file in the root of the theme.
    elseif( $partial_root === '' ):
      
      
      $file_base = "themes/%s/";
    
    
    // If $partial_root has any other value, build the path
    // using the string we passed.
    // This is to accomodate pages being served from
    // outside the theme directory - primarily admin pages.
    else:
    
      
      $file_base = "$partial_root/";
      
      
    endif;
    
    
    
    
    
    // Determine the file that we are looking for using $file_opts.
    // If $file_opts is a string we assume it is a true partial.
    // If $file_opts is an array we look for the 'base' and 'content' keys
    // If 'base' is unset or null we assume the default index.php as 
    // the html base.
    // 'content' is never assumed, and is passed as an arg named 
    // 'template_content'. If the array has no keys, we assume that the
    // first item is the content file, passed as a string.
    if ( is_string($file_opts) ):
      
      $file = $file_opts;
      
    else:
      
      
      if ( isset($file_opts['base']) ):
        
        $file = $file_opts['base'];
       
      else:
        
        $file = 'index';
       
      endif; 
      
      
      if ( isset($file_opts['content']) ):
        
        if ( !is_array($args) ):
          
          $args = [];
          
        endif;
        
        $args['template_content'] = $file_opts['content'];
        
      elseif ( isset($file_opts[0]) && is_string($file_opts[0]) ):
        
        if ( !is_array($args) ):
          
          $args = [];
          
        endif;
        
        
        $args['template_content'] = $file_opts[0];
        
      else:
        
        $this->Alerts->add("Partial content {$file} not found.", 'warn');
        
      endif;
      
    endif;
    
    
    // Build the full path to the partial based 
    // on what was passed.
    $partial_path = ROOT_PATH . $file_base . $file;
    
    
    if ( !is_null($suffix) ):
      
      $partial_path .= '-' . $suffix;  
    
    endif;  
    
    
    $partial_path .= '.php';
    
    
    
    // If $partial_root was empty, that means this
    // partial is loaded from within a theme.
    // Inject the theme name into our path and include
    // a fallback to the default theme in case the partial
    // in question isn't being overriden by the custom theme.
    if ( empty($partial_root) ):
      
      // Parse fallback path first to avoid overwriting
      // the primary $partial_path variable.
      $fallback_path = sprintf($partial_path, $default_theme);
      
      $partial_path = sprintf($partial_path, $theme);
      
    endif;
    
    

    // Determine whether the partial file requested exists.
    // If it doesn't, and the partial is a theme file, look
    // in the default theme as a fallback.
    if ( file_exists($partial_path) ):
   
      $found_partial = $partial_path;

    elseif ( $fallback_path && file_exists($fallback_path) ):
      
      $found_partial = $fallback_path;
      
    endif;
 
    
    
    // If the partial was found do some preparation and include it.
    if ( $found_partial ):
      
      // Make the Page class available inside the included file.
      $Page = Page::get_instance();

      $User = $this->User;
      
   
      // If we have args, extract them into variables
      // for more readable code in the partial file.
      if ( is_array($args) && !empty($args) ):
        
        extract($args);  
      
      endif;
      
      
      include( $found_partial );


      return true;
      
    else:
      
      // @internal This error will never be seen in any case where
      // the errors.php partial isn't included.
      $this->Alerts->add("Partial {$file} not found.", 'warn');

      return false;
      
    endif;
    
    
  } // get_partial()







  /**
   * 
   */
  function has_alerts( $level = false ) {

    return $this->Alerts->has_alerts($level);

  } // has_alerts()








  /**
   * 
   */
  function get_alerts( $level = false ) {

    return $this->Alerts->get($level);

  } // has_alerts()
  

  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
  
    if (self::$instance === null):
      
      self::$instance = new self();

    endif;
  
    return self::$instance;
  
  } // get_instance()
  
  
    
} // ::Page
