<?php

class Database {

  
  private static $instance = null;
  
  private $Config = null;
  
  private $db_conn = null;
  
  
  
  
  private function __construct() {

    
    $this->Config = Config::get_instance();
    
    $this->db_init();
    
      
  } // __construct()
  
  
  
  
  
  
  
  
    
  public function get_row_by_id( string $table, int $id, array $columns = ['*'] ) {
    
    $columnList = implode(', ', $columns);
    
    $stmt = $this->db_conn->prepare("SELECT $columnList FROM $table WHERE id = :id LIMIT 1");
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ?: null;
    
  } // get_row_by_id()
  
  
  
  
  
  
  
  
  
  
  
  
  public function row_exists(string $table, string $column = 'id', $value = null): bool {
    
    
    $stmt = $this->db_conn->prepare("SELECT 1 FROM $table WHERE $column = :value LIMIT 1");
    
    $stmt->bindValue(':value', $value, PDO::PARAM_STR); 
    
    $stmt->execute();
    
    
    return $stmt->fetchColumn() !== false;
    
 
  } // row_exists()

  








  /**
   * 
   */
  public function get_column(string $table, string $column, int $id ) {
    
    
    $query = "SELECT `{$column}` FROM `{$table}` WHERE id = :id";
    
    
    $stmt = $this->db_conn->prepare($query);
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    $stmt->execute();
    
    
    return $stmt->fetchColumn();
    
    
  } // get_column()










  /**
   * 
   */
  public function set_column(string $table, string $column, $value, int $id): bool {
    
    
    $query = "UPDATE `{$table}` SET `{$column}` = :value WHERE id = :id";
    
    
    $stmt = $this->db_conn->prepare($query);
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    $stmt->bindValue(':value', $value);
    
    
    return ( $stmt->execute() ) ? true : false;
    
    
  } // set_column()



  




  /**
  * Return a randomized, unique string that does not already exist
  * in a given database table and column.
  *
  * We do this by creating an array of random strings, then selecting 
  * a number of rows where our column matches any of those strings. 
  * Returning the first string that wasn't matched.
  */
  public function get_unique_column_val(string $table, string $column, array $args = []): string|false {

    $defaults = [
      'min_len' => 5,
      'max_len' => 16,
      'str_per_batch' => 15,
      'step' => 1,
      'batch_per_step' => 2
    ];

    // Merge passed arguments with defaults
    $args = array_merge($defaults, $args);


    
    // Ensure max_len is greater than or equal to min_len
    if ( !Utils::all_integers($args) || ($args['max_len'] < $args['min_len']) ):
      
      return false;

    endif;
    
    
    
    foreach ( range($args['min_len'], $args['max_len'], $args['step'] ) as $length):
      
      
      $step_count = 1;
      
      
      while ($step_count <= $args['batch_per_step']):
  
        $batch = [];
        
        
        for ($i = 0; $i < $args['str_per_batch']; $i++):
  
          $batch[] = Utils::generate_random_string($length);
  
        endfor;
        
        
        // Create the number of question marks to match the number of strings in our batch.
        $placeholders = implode(',', array_fill(0, count($batch), '?'));
  
        $query = "SELECT `{$column}` FROM `{$table}` WHERE `{$column}` IN ($placeholders)";
        
        $stmt = $this->db_conn->prepare($query);
  
        // Pass our array of strings to fill the placeholders in our query.
        $stmt->execute($batch);
        
        $existing_values = $stmt->fetchAll(PDO::FETCH_COLUMN);
  
  
        // Find any strings that exist in this batch,
        // but not in our database column.
        $unique_strings = array_diff($batch, $existing_values);
        

        if ( !empty($unique_strings) ):
          
          // If there are unique strings, return the first one.
          return reset($unique_strings);
        
        endif;
      

      endwhile;
      
      
      $step_count++;
      
    
    endforeach;
  
      
    // Return false if no unique value is found.
    return false;


  } // get_unique_column_val()


  
  
  
  
  
  
  
  
  private function db_init() {
    
    // Define the path to the SQLite database file.
    $db_file = ROOT_PATH . 'data/db.sqlite';
   
    
    
    // Test whether the database file exists.
    if ( file_exists($db_file) ):


      try {
        
        // Connect to the existing database.
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Save database connection for use throught the application.
        $this->db_conn = $pdo;
          
      } catch (PDOException $e) {
        
        $this->Config->add_alert( "Failed to connect to the existing database: " . $e->getMessage() );
      
      }

      
    else:
      

      try {
      
        // Create the database by connecting to it.
        $pdo = new PDO('sqlite:' . $db_file);
        
        // Set the error mode to exception.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->db_conn = $pdo;
        
        
        $this->make_tables();

          
      } catch (PDOException $e) {
        
      
        $this->Config->add_alert( "Failed to create the database: " . $e->getMessage() );
        
      }
      
      
    endif; // file_exists(config)
    
    
    
    
  } // db_init()
  
  
  
  
  
  
  
  
  private function make_tables() {
    
    
    $db = $this->get_conn();
    
    
    if ( $db ):
    
      User::make_tables( $db );
      
      RateLimits::make_tables( $db );
      
    else:
            
      $this->Config->add_alert("can't find db connection");
      
    endif;
    
    
    
  } // make_tables()
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  public function get_conn() {
    
    
    return $this->db_conn;
    
    
  } // get_conn()
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()

    
} // ::Database
