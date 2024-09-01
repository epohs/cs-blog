<?php

class Db {

  
  private static $instance = null;
  
  private $db_conn = null;
  
  
  
  
  private function __construct() {

    $this->db_init();
      
  } // __construct()
  
  
  
  
  
  
  
  
    
  public function get_row_by_id( string $table, int $id, array $columns = ['*'] ) {
    
    $columnList = implode(', ', $columns);
    
    $stmt = $this->db_conn->prepare("SELECT $columnList FROM $table WHERE id = :id LIMIT 1");
    
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ?: null;
    
  } // get_row_by_id()
  
  
  
  
  
  
  
  
  
  
  public function row_exists(string $table, string $column = 'id', $value = null): bool {
    
    
    $stmt = $this->db_conn->prepare("SELECT 1 FROM $table WHERE $column = :value LIMIT 1");
    
    // Use the appropriate PDO::PARAM_* based on your data type
    $stmt->bindParam(':value', $value, PDO::PARAM_STR); 
    
    $stmt->execute();
    
    
    return $stmt->fetchColumn() !== false;
    
 
  } // row_exists()

  
  
  
  
  
  
  
  
  
  
  private function db_init() {
    
    // Define the path to the SQLite database file
    $db_file = ROOT_PATH . 'data/db.sqlite';
   
    
    
    // Check if the database file exists
    if ( !file_exists($db_file) ) {
      
      try {
        
          // Create the database by connecting to it
          $pdo = new PDO('sqlite:' . $db_file);
          
          // Set the error mode to exception
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          
          // Save database connection
          $this->db_conn = $pdo;
          
          
          $this->make_tables();

          
      } catch (PDOException $e) {
        
        
        $page = Page::get_instance();
        
      
        $page->add_error( "Failed to create the database: " . $e->getMessage() );
        
      }
      
    } else {
      
      try {
        
        // Connect to the existing database
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->db_conn = $pdo;
          
      } catch (PDOException $e) {
        
        $page = Page::get_instance();
        
        $page->add_error( "Failed to connect to the existing database: " . $e->getMessage() );
      
      }
      
    } // file_exists(config)
    
    
    
    
  } // db_init()
  
  
  
  
  
  
  
  
  private function make_tables() {
    
    
    $db = $this->get_conn();
    
    
    if ( $db ):
    
      $db->beginTransaction();
    
      // Optionally, create tables or perform other setup tasks here
      $db->exec(
        "CREATE TABLE Users (
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
      
      $db->commit();
      
    else:

      $page = Page::get_instance();
            
      $page->add_error("can't find db connection");
      
    endif;
    
    
    
  } // make_tables;
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  public function get_conn() {
    
    
    return $this->db_conn;
    
    
  } // get_conn()
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()

    
} // ::Db
