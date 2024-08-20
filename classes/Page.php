<?php

class Page {

  
  private static $instance = null;
  
  
  private function __construct() {

    Db::get_instance();
    
  }
  
  
  public static function get_instance() {
  
    if (self::$instance === null) {
      self::$instance = new Page();
    }
  
    return self::$instance;
  
  } // get_instance()

    
} // ::Page
