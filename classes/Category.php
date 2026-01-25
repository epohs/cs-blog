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
   * 
   */
  function get_categories( array $args = [] ): array|false {
    
    
    $defaults = [
      'order_by' => 'name',
      'limit' => 10, // @todo This should be a setting
      'offset' => 0
    ];

    $args = array_merge($defaults, $args);
    
    
    $allowed_order = ['name', 'created_at', 'id'];
    
    $order_by = in_array($args['order_by'], $allowed_order) ? $args['order_by'] : 'name';
    
    $query = "SELECT * FROM `Categories`
              ORDER BY `{$order_by}` ASC
              LIMIT :limit OFFSET :offset";

    
    $stmt = $this->pdo->prepare($query);

    
    $stmt->bindParam(':limit', $args['limit'], PDO::PARAM_INT);
    $stmt->bindParam(':offset', $args['offset'], PDO::PARAM_INT);

    $stmt->execute();


    // Fetch Users
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    return $categories;

  } // get_categories()
  
  
  
  
  
  
  /*
  
  // Get posts by category ID
  
  SELECT DISTINCT Posts.*
  FROM Posts, json_each(Posts.categories)
  WHERE json_valid(Posts.categories)
    AND CAST(json_each.value AS INTEGER) = 3;
  
  */
  
  
  
  
  
  
  
  
  
  /*
  
  // Get all posts that are uncategorized
    
    
  SELECT DISTINCT Posts.*
  FROM Posts
  LEFT JOIN json_each(Posts.categories)
    ON json_valid(Posts.categories)
  WHERE NOT json_valid(Posts.categories)
   OR json_array_length(Posts.categories) = 0
   OR (
     json_array_length(Posts.categories) > 0
     AND Posts.id IN (
       SELECT Posts.id
       FROM Posts
       JOIN json_each(Posts.categories)
         ON json_valid(Posts.categories)
       LEFT JOIN Categories
         ON Categories.id = CAST(json_each.value AS INTEGER)
       WHERE Categories.id IS NULL
       GROUP BY Posts.id
       HAVING COUNT(*) = json_array_length(Posts.categories)
     )
   );

  */
  
  
  

  
  
  
  
  
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
