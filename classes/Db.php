<?php

class Db {

  
  private static $instance = null;
  
  
  private function __construct() {

    $this->db_init();
    
  }
  
  
  
  
  private function db_init() {
   
    echo 'root: ' . ROOT_PATH . '<br>';
    
    
    // Define the path to the SQLite database file
    $databaseFile = ROOT_PATH . 'data/db.sqlite';

    echo 'databaseFile: ' . $databaseFile . '<br>';
    
    
    // Check if the database file exists
    if (!file_exists($databaseFile)) {
      try {
          // Create the database by connecting to it
          $pdo = new PDO('sqlite:' . $databaseFile);
          
          // Set the error mode to exception
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
          // Optionally, create tables or perform other setup tasks here
          $pdo->exec("CREATE TABLE IF NOT EXISTS users (
              id INTEGER PRIMARY KEY,
              username TEXT NOT NULL,
              password TEXT NOT NULL
          )");
    
          echo "Database created and connection established.";
      } catch (PDOException $e) {
          echo "Failed to create the database(1): " . $e->getMessage();
      }
    } else {
      echo "Database already exists.";
      try {
          // Connect to the existing database
          $pdo = new PDO('sqlite:' . $databaseFile);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
          echo " Connected to the existing database.";
      } catch (PDOException $e) {
          echo "Failed to connect to the existing database: " . $e->getMessage();
      }
    }

   
    
  }
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null) {
      self::$instance = new Db();
    }
  
    return self::$instance;
  
  } // get_instance()

    
} // ::Db
