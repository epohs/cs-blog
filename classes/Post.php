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
      // Check that user exists and is at least role: author.
      
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
   * Delete a post row by it's ID.
   */
  public function delete( int $post_id ): bool {

    return $this->Db->delete_row('Posts', $post_id);

  } // delete()

  
  
  
  
  
  
  
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
 * Retrieve posts from the database.
 */
public function get_posts( array $args = [] ): array|int|false {
  
  
  $defaults = [
    'author_id'    => null,
    'html_content' => true,
    'count_only'   => false,
    'limit'        => 10, // @todo This should be a setting
    'offset'       => 0
  ];  

  
  $args = array_merge( $defaults, $args );  
  

  // Build base query.
  $select = $args['count_only'] ? "COUNT(*) AS post_count" : "*";
  
  $query  = "SELECT {$select} FROM `Posts`";

  
  // Where statements are added to this array.
  $where = [];
  
  
  // Check if author filtering is needed.
  if ( is_int( $args['author_id'] ) ):
    
    $where[] = 'author_id = :author_id';
    
  endif;

  // Combine WHERE clauses.
  if ( !empty( $where ) ):
    
    $query .= ' WHERE ' . implode( ' AND ', $where );
    
  endif;

  
  // Apply ordering and pagination only if not counting. 
  if ( !$args['count_only'] ):
  
    $query .= ' ORDER BY `created_at` DESC LIMIT :limit OFFSET :offset';
  
  endif;

  
  $stmt = $this->pdo->prepare( $query );
  

  // Bind author if filtering by author.
  if ( is_int( $args['author_id'] ) ):
    
    $stmt->bindParam( ':author_id', $args['author_id'], PDO::PARAM_INT );
    
  endif;

  
  // Bind limit and offset only if not counting.
  if ( !$args['count_only'] ):

    $stmt->bindParam( ':limit', $args['limit'], PDO::PARAM_INT );
    $stmt->bindParam( ':offset', $args['offset'], PDO::PARAM_INT );

  endif;

  
  $stmt->execute();

  
  // Return count if counting only.
  if ( $args['count_only'] ):
    
    return $stmt->fetchColumn();
    
  endif;

  
  // Fetch all posts.
  $posts = $stmt->fetchAll( PDO::FETCH_ASSOC );
  

  // If html_content is true, parse the content with Parsedown.
  if ( $args['html_content'] ):
    
    $Parsedown = new Parsedown();

    foreach ( $posts as &$post ):
      
      $post['content'] = $Parsedown->text( $post['content'] );
      
    endforeach;
    
  endif;

  
  return $posts;

  
} // get_posts()


  
  
  
  
  
  
  
  
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
    
    // Define columns that should never be updated
    $protected_columns = [
        'id',
        'selector',
        'created_at'
    ];

    // Fetch column names from the Posts table
    $stmt = $this->pdo->query("PRAGMA table_info(Posts)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    // Filter out invalid or protected columns
    $editable_columns = array_diff($columns, $protected_columns);
    $valid_updates = array_intersect_key($post_data, array_flip($editable_columns));

    // If there are no valid updates, return false
    if ( empty($valid_updates) ):
      
      return false;
      
    endif;

    // Generate SQL for updating the post
    // @todo Change this to $columns_to_update?
    $set_clauses = [];
    
    foreach ($valid_updates as $column => $value):
      
      $set_clauses[] = "`$column` = :$column";
      
    endforeach;

    // Always update the `updated_at` column
    // @todo Should updated_at be a protected column?
    $set_clauses[] = "`updated_at` = CURRENT_TIMESTAMP";

    $query = "UPDATE `Posts` SET " . implode(", ", $set_clauses) . " WHERE `id` = :post_id";

    $stmt = $this->pdo->prepare($query);
    

    // Bind valid parameters, casting as either INT or STR.
    foreach ($valid_updates as $column => &$value):
      
      $param_type = ( is_numeric($value) && ctype_digit(strval($value)) ) ? PDO::PARAM_INT : PDO::PARAM_STR;
      
      $stmt->bindValue(":$column", $value, $param_type);
      
    endforeach;
    

    $stmt->bindParam(":post_id", $post_id, PDO::PARAM_INT);


    if ( !$stmt->execute() ):
      
      return false;
      
    endif;
    

    return $this->get($post_id);
    
    
  } // update()
  
  
  
  
  
  
  
  
  /**
   * Handle Pagination for multiple data types.
   * 
   * @todo I think this will be more appropriate in the Page class.
   */
  public function get_pagination(array $args = []): array|string {
    
    $defaults = [
      'cur_page'             => $this->cur_page,
      'pagination_base'      => '/posts',
      'return'               => 'array',
      'items_per_page'       => 10,
      'num_pagination_items' => 6,
      'total_items'          => $this->total_posts,
    ];
  
    $args = array_merge($defaults, $args);
  
    $total_pages = max(1, ceil($args['total_items'] / $args['items_per_page']));
    $cur_page = max(1, min($args['cur_page'], $total_pages));
    $pagination = [];
  
    if ( $total_pages <= $args['num_pagination_items'] ):
      
      for ( $i = 1; $i <= $total_pages; $i++ ):
        
        $pagination[] = [
          'page' => $i,
          'link' => $args['pagination_base'] . '?page=' . $i
        ];
        
      endfor;
      
    else:
      
      $half = floor($args['num_pagination_items'] / 2);
      $start = max(1, $cur_page - $half);
      $end = min($total_pages, $start + $args['num_pagination_items'] - 1);
  
      
      if ( $end - $start + 1 < $args['num_pagination_items'] ):
        
        $start = max(1, $end - $args['num_pagination_items'] + 1);
        
      endif;
      
  
      if ( $start > 1 ):
        
        $pagination[] = ['page' => 1, 'link' => $args['pagination_base'] . '?page=1'];
  
        if ( $start > 2 ):
          
          $pagination[] = ['page' => '…', 'link' => null];
          
        endif;
        
      endif;
  
      for ( $i = $start; $i <= $end; $i++ ):
        
        $pagination[] = [
          'page' => $i,
          'link' => $args['pagination_base'] . '?page=' . $i
        ];
        
      endfor;
  
      if ( $end < $total_pages ):
        
        if ( $end < $total_pages - 1 ):
          
          $pagination[] = ['page' => '…', 'link' => null];
          
        endif;
  
        $pagination[] = ['page' => $total_pages, 'link' => $args['pagination_base'] . '?page=' . $total_pages];
        
      endif;
      
    endif;
  
    return ($args['return'] === 'json') ? json_encode($pagination, JSON_PRETTY_PRINT) : $pagination;

  } // get_pagination()

  
  
  
  
  
  
  
  
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
