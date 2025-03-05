<?php






/**
 * Handle functionality related to blog posts.
 */
class Post {

  
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
   * Create a new post.
   *
   * Return the ID of the newly created post if the post 
   * creation succeeds. Otherwise, return false.
   */
  public function new( array $post_data ): int|false {
    
    
    $result = false;
    
    $selector = $this->Db->get_unique_column_val('Posts', 'selector');
    
    
    
    // @todo User needs to be either Admin or Author
    // @todo Probably should make a User function for can_author() or something
    // If an author was passed, assign it as the author
    // otherwise, make the current user the author.
    if ( isset($post_data['author']) && is_int($post_data['author']) ):
      
      $User = User::get_instance();
      
      // @todo Do better checks.
      
      $author_id = $post_data['author'];
      
    else:
      
      $author_id = Session::get_key(['user', 'id']);
      
    endif;
    
    
    $post_title = $post_data['title'];
    $post_content = $post_data['content'];
    
    // @todo Add ability to customize slug separately from post title.
    $slug = Utils::make_sluggy($post_title);
    
    
    
    try {
      
  
      // Prepare the SQL statement
      $query = 'INSERT INTO `Posts` (`selector`, `author_id`, `slug`, `title`, `content`) 
                VALUES (:selector, :author, :slug, :title, :content)';
        
      $stmt = $this->pdo->prepare( $query );
  
      $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
      $stmt->bindValue(':author', $author_id, PDO::PARAM_INT);
      $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
      $stmt->bindValue(':title', $post_title, PDO::PARAM_STR);
      $stmt->bindValue(':content', $post_content, PDO::PARAM_STR);
  
      
      if ( $stmt->execute() ):
        
        $result = $this->pdo->lastInsertId();
        
      else:
        
        $result = false;
        
      endif;
      
    
    } catch (PDOException $e) {
    
      debug_log('New Post creation failed: ' . $e->getMessage());
      
      $result = false;
      
    }
    
    
    return $result;
    
    
  } // new()
  
  
  
  
  
  
  
  
  /**
   * Get a post by it's ID.
   *
   * @todo I think I want to format the post content as HTML
   * by default and add a parameter to disable formatting.
   */
  public function get( int $post_id ): array|false {

    return $this->Db->get_row_by_id('Posts', $post_id);

  } // get()

  
  
  
  
  
  
  
  /**
   * Get a post by certain allowed keys.
   */
  public function get_by(string $key, $value): array|false {
    
    
    $valid_keys = [
      'id',
      'selector'
    ];
    
    $key = ( in_array($key, $valid_keys) ) ? $key : 'id';
    
    
    if ( $key == 'id' ):

      return $this->get($value);

    else:

      $query = "SELECT * FROM `Posts` WHERE `{$key}` = :value";

    endif;
    
    
    $stmt = $this->pdo->prepare($query);

    $param_type = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    
    $stmt->bindValue(':value', $value, $param_type);
    
    
    $stmt->execute();
    
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
    
    
  } // get_by()
  
  
  
  
  
  
  
  
  /**
   * Get the selector for a post.
   */
  public function get_selector( int $post_id ): string|false {
    
    
    return $this->get_column('selector', $post_id);
    
    
  } // get_selector()
  
  
  
  
  
  
  
  /**
   * Update a post.
   *
   * @todo Finish this.
   */
  public function update( int $post_id, array $post_data ): array|false {
    
    
    return $this->get($post_id);
    
    
  } // update()
  
  
  
  
  
  
  
  
  /**
   * Private function to get a single user column.
   */
  private function get_column(string $column, int $user_id): mixed {
    
    
    return $this->Db->get_column('Posts', $column, $user_id);
    
    
  } // get_column()

  
  
  
  
  
  
  
  /**
   * Private function to set a single user column.
   */
  private function set_column(string $column, $value, int $user_id): bool {
    
    
    return $this->Db->set_column('Posts', $column, $value, $user_id);
    
    
  } // set_column()
  
  
  
  




  /**
   * Create the database tables needed for users.
   *
   * @todo Should I index selector?
   * @todo author_id should be author.
   */
  public static function make_tables( $pdo ): bool {
    
  
    try {
      
      
      $result = $pdo->exec('
        CREATE TABLE IF NOT EXISTS `Posts` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `selector` VARCHAR(16) UNIQUE,
          `author_id` INTEGER NOT NULL,
          `title` VARCHAR(255) NOT NULL,
          `slug` VARCHAR(255) UNIQUE NOT NULL,
          `content` TEXT NOT NULL,
          `categories` JSON DEFAULT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `published_at` DATETIME DEFAULT NULL,
          `is_published` BOOLEAN DEFAULT 0,
          `comments_open` BOOLEAN DEFAULT 1,
          `show_comments` BOOLEAN DEFAULT 1,
          FOREIGN KEY (`author_id`) REFERENCES `Users`(`id`) ON DELETE CASCADE
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

  
    
} // ::Post