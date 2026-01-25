<?php

/**
 * Handle reading from and writing to cookies.
 *
 */
class Cookie {

  
  private static $instance = null;
  
  

  
  
  
  
  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
  /**
   * Set a cookie value.
   *
   * Overwrite if cookie already exists.
   */
  public static function set($name, $value, $expiry = 3600, array $args = []): void {
    
    
    $defaults = [
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'http_only' => true,
        'samesite' => 'Lax'
    ];

    // Merge passed arguments with defaults
    $args = array_merge($defaults, $args);
    
    setcookie($name, $value, [
      'expires' => time() + $expiry,
      'path' => $args['path'],
      'domain' => $args['domain'],
      'secure' => $args['secure'],
      'httponly' => $args['http_only'],
      'samesite' => $args['samesite']
    ]);
  
    
  } // set()
  
  
  
  
  
  
  
  
  /**
   * Get a cookie by name.
   */
  public static function get($name): string|null {
    
    return $_COOKIE[$name] ?? null;
    
  } // get()
  
  
  
  
  
  
  
  
  /**
   * Delete a cookie by name.
   */
  public static function delete( $name ): void {
    
    setcookie($name, '', time() - 3600, '/');
    unset($_COOKIE[$name]);
    
  } // delete()
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Cookie
