<?php

class Actions {

  protected static array $actions = [];

  
  
  
  
  
  
  /**
   *
   */
  public static function add_action(string $name, callable $callback): void {
  
    if ( empty( self::$actions[$name] ) ):
      
      self::$actions[$name] = [];
      
    endif;

    self::$actions[$name][] = $callback;
  
  } // add_action()
  
  

  
  
  
  
  
  /**
   *
   */
  public static function do_action(string $name, array $context = []): void {
    
    if ( empty(self::$actions[$name]) ):
      
      return;
      
    endif;

    foreach ( self::$actions[$name] as $callback ):
      
      call_user_func($callback, $context);
      
    endforeach;
    
  } // do_action()
  
  
  
} // ::Actions
