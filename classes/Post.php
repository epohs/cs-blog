<?php

/**
 * Handle functionality related to blog posts.
 */
class Post {
    
    
  
  
  
  /**
   * Create the database tables needed for users.
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