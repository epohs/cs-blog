<?php

class Page {

  
    private static $instance = null;
    

    private function __construct() {
        // Private constructor to prevent multiple instances.
    }
    

    public static function get_instance() {
    
      if (self::$instance === null) {
        self::$instance = new Page();
      }

      return self::$instance;
    
    } // get_instance()

    
} // ::Page
