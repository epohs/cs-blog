<?php

/**
 * Handles crucial database setup and core methods used
 * by other classes throughout the appliction.
 *
 * This class handles the creation of our database, and
 * shares the db connection with all other classes.
 */
class Database {

  
  private static $instance = null;
  
  private $Alerts = null;
  
  private $Config = null;
  
  private $pdo = null;
  
  
  
  
  
  
  private function __construct() {

    $this->Alerts = Alerts::get_instance();

    $this->Config = Config::get_instance();
    
    $this->db_init();
    
      
  } // __construct()
  
  
  
  
  
  
  
  
  /**
   * Return the PDO for all other classes to use to
   * connect to the database.
   */
  public function get_pdo() {
    
    
    return $this->pdo;
    
    
  } // get_pdo()
  
  
  
  
  
  
  
  
  /**
   * Get selected columns from a row by it's ID.
   */  
  public function get_row_by_id( string $table, int $id, array $columns = ['*'] ) {
    
    $columnList = implode(', ', $columns);
    
    $stmt = $this->pdo->prepare("SELECT {$columnList} FROM `{$table}` WHERE `id` = :id LIMIT 1");
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ?: null;
    
  } // get_row_by_id()
  
  
  
  
  
  
  
  
  /**
   * Test whether a row exists by selecting a row that matches 
   * a selected column with a given value.
   */
  public function row_exists(string $table, string $column = 'id', $value = null): bool {
    
    
    $stmt = $this->pdo->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = :value LIMIT 1");
    
    $stmt->bindValue(':value', $value, PDO::PARAM_STR); 
    
    $stmt->execute();
    
    
    return $stmt->fetchColumn() !== false;
    
 
  } // row_exists()

  
  
  
  
  
  
  
  /**
   * Get a single column by the row's ID.
   */
  public function get_column(string $table, string $column, int $id ): mixed {
    
    
    $stmt = $this->pdo->prepare("SELECT `{$column}` FROM `{$table}` WHERE `id` = :id");
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    $stmt->execute();
    
    
    return $stmt->fetchColumn();
    
    
  } // get_column()








  /**
   * Set a single column to a given value. The row is targetted
   * by it's ID.
   */
  public function set_column(string $table, string $column, $value, int $id): bool {
    
    
    $stmt = $this->pdo->prepare("UPDATE `{$table}` SET `{$column}` = :value WHERE `id` = :id");
    
    $stmt->bindValue(':value', $value);
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    
    return ( $stmt->execute() ) ? true : false;
    
    
  } // set_column()








  /**
   * Delete a row from a given table by its ID.
   */
  public function delete_row(string $table, int $id): bool {
    
    // Define allowed table names
    $valid_tables = ['Posts', 'Users', 'Comments'];

    if ( !in_array($table, $valid_tables) ):
      
      throw new Exception("Invalid table name");

    endif;
    
    
    if ( $id <= 0 ):
      
      throw new Exception("Invalid ID");

    endif;

    
    $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE `id` = :id");

    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    try {

      if ( $stmt->execute() ):
        
        return true;

      else:

        throw new Exception("Failed to delete row.");

      endif;

    } catch (Exception $e) {

        // Log the exception or handle it as needed
        return false;

    }

  } // delete_row()




  




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

    // Merge passed arguments with defaults.
    $args = array_merge($defaults, $args);

    
    // Ensure max_len is greater than or equal to min_len.
    if ( !Utils::all_integers($args) || ($args['max_len'] < $args['min_len']) ):
      
      return false;

    endif;
    
    
    // Loop from the smallest length string to the largest in our
    // range of strings, stepping by our defined increment.
    foreach ( range($args['min_len'], $args['max_len'], $args['step'] ) as $length):
      
      
      $step_count = 1;
      
      
      while ($step_count <= $args['batch_per_step']):
  
        $batch = [];
        
      
        // Create the defined number of strings to check.  
        for ($i = 0; $i < $args['str_per_batch']; $i++):
  
          $batch[] = Utils::generate_random_string($length);
  
        endfor;
        
        
        // Create the number of question marks to match the number of strings in our batch.
        $placeholders = implode(',', array_fill(0, count($batch), '?'));
  
        $query = "SELECT `{$column}` FROM `{$table}` WHERE `{$column}` IN ($placeholders)";
        
        $stmt = $this->pdo->prepare($query);
  
        // Pass our array of strings to fill the placeholders in our query.
        $stmt->execute($batch);
        
        $existing_values = $stmt->fetchAll(PDO::FETCH_COLUMN);
  
  
        // Find any strings that exist in this batch,
        // but not in our target database column.
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

  
  
  
  
  
  
  
  /**
   * Initialize the database, and set up the PDO object for use 
   * by the application.
   * 
   * If the database doesn't exist create it, and kick off creating
   * the tables the application needs otherwise just connect.
   */
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
        $this->pdo = $pdo;
        
      } catch (PDOException $e) {
        
        $this->Config->add( "Failed to connect to the existing database: " . $e->getMessage() );
      
      }

      
    else:
      

      try {
        
      
        // Create the database by connecting to it.
        $pdo = new PDO('sqlite:' . $db_file);
        
        // Set the error mode to exception.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->pdo = $pdo;
        
        
        $this->make_tables();

          
      } catch (PDOException $e) {
      
        $this->Config->add( "Failed to create the database: " . $e->getMessage() );
        
      }
      
      
    endif; // file_exists(config)
    
    
  } // db_init()
  
  
  
  
  
  
  
  
  /**
   * Manage the creation of database tables defined in all other classes.
   */
  private function make_tables() {
    
    
    $pdo = $this->get_pdo();
    
    
    if ( $pdo ):
    
      User::make_tables( $pdo );
      
      RateLimits::make_tables( $pdo );
      
    else:
            
      $this->Config->add("can't find db connection");
      
    endif;
    
    
  } // make_tables()
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()

    
  
} // ::Database
