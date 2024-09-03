<?php

class User {
    
    
  private static $instance = null;
  
  
  
  
  
  
  private function __construct() {
    
    
    
  } // __construct()
  
  
  
  
  
  
  
  
  public function new( array $user_data ): int|false {
    
    
    $result = false;
    
    $db = Db::get_instance();
    
    $db_conn = $db->get_conn();
    
    
    // If th
    $user_role = ( !$db->row_exists('Users', 'role', 'admin') ) ? 'admin' : 'user';
    
    
    // @todo this needs to be unique
    $verify_key = substr(bin2hex(random_bytes(4)), 0, 8);
    
    // @todo Make this a config value
    $default_display_name = 'New user';
    
    
    try {
      
      // Hash the password before storing it
      $hashed_pass = password_hash($user_data['password'], PASSWORD_DEFAULT);
  
      // Prepare the SQL statement
      $sql = "INSERT INTO Users (email, password, display_name, role, verify_key) 
              VALUES (:email, :password, :display_name, :role, :verify_key)";
      
      
      $stmt = $db_conn->prepare($sql);
  
      // Bind the parameters
      $stmt->bindParam(':email', $user_data['email'], PDO::PARAM_STR);
      $stmt->bindParam(':password', $hashed_pass, PDO::PARAM_STR);
      $stmt->bindParam(':display_name', $default_display_name, PDO::PARAM_STR);
      $stmt->bindParam(':role', $user_role, PDO::PARAM_STR);
      $stmt->bindParam(':verify_key', $verify_key, PDO::PARAM_STR);
  
      // Execute the statement and return the ID of the User
      // we just added, or false if something failed.
      if ( $stmt->execute() ):
        
        $result = $db_conn->lastInsertId();
        
      else:
        
        $result = false;
        
      endif;
      
    
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage() . '<br>';
      
      $result = false;
      
    }
    
    
    
    return $result;
    
  } // new()
  
 
  
  
  
  
  
  
  public function get_by($value, string $key = 'id') {

    
    $db = Db::get_instance();
    
    

  } // get_by()
  
  
  
  
  
    
    
    
  
  
  public function user_exists( $id_or_email ): bool {
    
    $user_key = null;
    
    // Check if the input is a valid integer
    if ( is_numeric($id_or_email) && intval($id_or_email) == $id_or_email ):
    
      $user_key = intval($id_or_email);
      
    endif;
    
    

    // Check if the input is a valid email address
    if ( filter_var($id_or_email, FILTER_VALIDATE_EMAIL) ):
      
      $user_key = trim($id_or_email);
        
    endif;
    
    
    
    if ( is_null($user_key) ):
    
      return false;
      
    else:
      
      $db = Db::get_instance();
      
      $user_key_type = ( is_int($user_key) ) ? 'id' : 'email';
      
      return $db->row_exists('Users', $user_key_type, $user_key);
      
    endif;
    
    
    
  } // user_exists()
  
  
  
  
  
  
  
  
  
  /**
   * @todo Flesh this function out
   */
  public function validate_pass( string $password ) {
    
    
    return ( strlen($password) >= 4 );
    
    
  } // validate_pass()
  
  
  
  

  
  
  
  
  public static function make_tables( $db ): bool {


    //$db->beginTransaction();
    
    $result = null;
    
  
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS Users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          selector VARCHAR(16) UNIQUE,
          email VARCHAR(255) NOT NULL UNIQUE,
          password VARCHAR(255) NOT NULL,
          display_name VARCHAR(255) NOT NULL,
          remember_me VARCHAR(64) UNIQUE,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          last_login DATETIME,
          is_active BOOLEAN DEFAULT 1,
          is_verified BOOLEAN DEFAULT 0,
          verify_key VARCHAR(16) UNIQUE,
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