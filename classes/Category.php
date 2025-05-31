<?php


/**
 * Handle functionality related to blog posts.
 */
class Category {

  
  private static $instance = null;
  
  private $Config = null;

  private $Db = null;

  private $pdo = null;
  
  
  
  
  
  
  
  
  private function __construct() {
    

    $this->Config = Config::get_instance();

    $this->Db = Database::get_instance();

    $this->pdo = $this->Db->get_pdo();
    
    
  } // __construct()



  
  
  
  
  
  /**
   * Create the database tables needed for Categories.
   *
   * @todo Should I index selector?
   */
  public static function make_tables( $pdo ): bool {
    
  
    try {


      $result = $pdo->exec('
        CREATE TABLE IF NOT EXISTS `Categories` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `selector` VARCHAR(16) UNIQUE,
          `slug` VARCHAR(255) UNIQUE NOT NULL,
          `name` VARCHAR(255) NOT NULL,
          `description` TEXT DEFAULT NULL,
          `order` VARCHAR(16) UNIQUE,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )
      ');
      
     return $result;
      
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage();
      
      return false;
      
    }
    

  } // make_tables()
  
  
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::Category