<?php

class User {
    
    
  private static $instance = null;
  
  
  
  
  
  
  private function __construct() {
    
    
    
  } // __construct()
  
  
  
  
 
  
  
  
  
  
  
  public function get_by(string $key = null, $value) {


    
    

  } // get_by()
    
    
    
  
  
  
  
  

  
  
  
  
  public static function make_tables( $db ): bool {


    //$db->beginTransaction();
    
    $result = null;
    
  
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS Users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          email VARCHAR(255) NOT NULL UNIQUE,
          password VARCHAR(255) NOT NULL,
          display_name VARCHAR(255) NOT NULL UNIQUE,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          last_login DATETIME,
          is_active BOOLEAN DEFAULT 1,
          is_verified BOOLEAN DEFAULT 0,
          failed_login_attempts INTEGER DEFAULT 0,
          locked_until DATETIME,
          role TEXT DEFAULT 'user',
          CHECK (role IN ('user', 'author', 'admin'))
        );"
      );
      
      
      $result = ( $result === false ) ? false : true;
      
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage();
      
      $result = false;
      
    }
    
    
    return $result;
    
    //$db->commit();
  

  } // get_by()
    
    
    
  
  
  
  
  
    
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::User