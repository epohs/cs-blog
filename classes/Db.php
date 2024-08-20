<?php

class Db {

  
  private static $instance = null;
  
  private $db_conn = null;
  
  
  
  
  private function __construct() {

    $this->db_init();
    
  }
  
  
  
  
  private function db_init() {
    
    // Define the path to the SQLite database file
    $db_file = ROOT_PATH . 'data/db.sqlite';
    $db_conn = false;
    $db_conn_err = false;
   
    
    
    // Check if the database file exists
    if ( !file_exists($db_file) ) {
      
      try {
        
          // Create the database by connecting to it
          $pdo = new PDO('sqlite:' . $db_file);
          
          // Set the error mode to exception
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
          // Optionally, create tables or perform other setup tasks here
          $pdo->exec("CREATE TABLE IF NOT EXISTS users (
              id INTEGER PRIMARY KEY,
              username TEXT NOT NULL,
              password TEXT NOT NULL
          )");
    
          $db_conn = $pdo;
          
      } catch (PDOException $e) {
      
        $db_conn_err = "Failed to create the database: " . $e->getMessage();
        
      }
      
    } else {
      
      try {
        
          // Connect to the existing database
          $pdo = new PDO('sqlite:' . $db_file);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
          $db_conn = $pdo;
          
      } catch (PDOException $e) {
        
          $db_conn_err = "Failed to connect to the existing database: " . $e->getMessage();
      
      }
      
    }
    
    
    $this->db_conn = $pdo;
   
    
    if ( $db_conn_err ):
      
      echo $db_conn_err;
      
    endif;
    
  } // db_init()
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null) {
      self::$instance = new Db();
    }
  
    return self::$instance;
  
  } // get_instance()

    
} // ::Db
