<?php



/**
* Very basic rate limiting.
* 
* @todo this needs to check for Redis and use that if available
*/
class RateLimits {
  
  
  private static $instance = null;
  
  private $db = null;
  
  private $limiters = [];
  
  
  
  
  
  
  public function __construct() {
    
    
    $db = Db::get_instance();
    
    $this->db = $db->get_conn();
    
    
  } // _construct()
    
    
    
    
    
    
    
  /**
  * Configure a rate limiter with a specific key and policy.
  *
  * @param string $key Unique identifier for the rate limiter.
  * @param int $limit Maximum number of actions allowed.
  * @param string $interval Time window for the rate limit (e.g., '5 minutes').
  */
  public function set(string $key, int $limit, string $interval): bool {
    
    
    $interval_in_seconds = Utils::convert_to_seconds($interval);
    
    
    if ( $interval_in_seconds ):
      
      $this->limiters[$key] = [
        'limit' => $limit,
        'interval' => $interval_in_seconds
      ];
      
      return true;
      
    else:
      
      return false;
      
    endif;
    
    
  } // set()
    
    
    
    
    
  
  
  /**
  * Check and consume tokens for a specific limiter.
  *
  * @param string $key Identifier of the rate limiter.
  *
  * @return bool True if the request is allowed, false otherwise.
  */
  public function check(string $key, ?bool $increment = true ): bool {
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    
    
    // Calculate the number of tries used
    $tries_used = $this->get_tries_used($key);


    if ( is_countable($tries_used) && (count($tries_used) >= $this->limiters[$key]['limit']) ):

      $del = $this->delete_expired($key);
      
      return false;
      
    else:


      if ( $increment ):
        
        $new_id = $this->add_hit( $key );
        
      endif;
      
      
      return true;
      
      
    endif;
    

  } // check()
    
    
    
    
    
    
    
    
    
    
    
    
  /**
   * Add a new entry for this limiter. Set the expires_at
   * to the appropriate number of seconds in the future.
   * 
   * 
   */
  private function add_hit( string $key ): int|false {
    
    
    $client_ip = Utils::get_client_ip();
    
    
    if ( !isset($this->limiters[$key]) || ($client_ip === false) ):
      
      return false;
      
    endif;
    
    
    $now = date('Y-m-d H:i:s');

    $seconds = $this->limiters[$key]['interval'];

    $date = new DateTime($now);

    $date->modify("+{$seconds} seconds");

    $expires_at_str = $date->format('Y-m-d H:i:s');
    

    $query = "INSERT INTO `RateLimits` (`key`, `client_ip`, `expires_at`) 
              VALUES (:key, :client_ip, :expires_at)";


    try {
      
      $stmt = $this->db->prepare($query);
      
      // Bind parameters
      $stmt->bindParam(':key', $key, PDO::PARAM_STR);
      $stmt->bindParam(':client_ip', $client_ip, PDO::PARAM_STR);
      $stmt->bindParam(':expires_at', $expires_at_str, PDO::PARAM_STR);
      
      
      // Execute the query
      if ( $stmt->execute() ):
        
        return $this->db->lastInsertId();
        
      else:
        
        return false;
        
      endif;
      
    } catch (PDOException $e) {
      
      return false;
      
    }
    
  } // add_hit()
  
    
    
    
    
    
    
    
    
  
  /**
  * Get the tries used for a specific limiter.
  *
  * @param string $key Identifier of the rate limiter.
  *
  * @return array|false Array of tries used.
  */
  public function get_tries_used(string $key, ?int $limit = 0): array|false {
    
    
    if  ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    
    
    $limit = ( $limit ) ? $limit : $this->limiters[$key]['limit'];
    
    $current_time = date('Y-m-d H:i:s');

    $client_ip = Utils::get_client_ip();
    
    
    if ( is_numeric($limit) ):
      
      $limit = intval($limit);
      
    else:
      
      return false;
      
    endif;
    
    
    $query = "SELECT *
            FROM `RateLimits`
            WHERE `key` = :key
              AND `client_ip` = :client_ip
              AND `expires_at` > :current_time
            ORDER BY `expires_at` ASC
            LIMIT :limit";
    
    
    try {

      $stmt = $this->db->prepare($query);
    
      // Bind the parameters
      $stmt->bindParam(':key', $key, PDO::PARAM_STR);
      $stmt->bindParam(':current_time', $current_time, PDO::PARAM_STR);
      $stmt->bindParam(':client_ip', $client_ip, PDO::PARAM_STR);
      $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
      
      
      $stmt->execute();
      
      if ( $limit === 1 ):

        return $stmt->fetch(PDO::FETCH_ASSOC);

      else:

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

      endif;

    } catch (PDOException $e) {
        
      return false;
      
    }
    

  } // get_tries_used()
    
    
    
    
    
    
    
    
    
    
  
  /**
  * Get the next time when this limiter can be successfully hit again.
  *
  * @param string $key Identifier of the rate limiter.
  *
  * @return string|false Next available retry time, or false if limiter not found.
  */
  public function get_retry_after(string $key): string|false {
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;

    
    $seconds = $this->limiters[$key]['interval'];     
    
    
    $first_try_in_window = $this->get_tries_used($key, 1);

    $expires_at = $first_try_in_window['expires_at'];

    $expires_at_date = new DateTime($expires_at);

    $expires_at_str = $expires_at_date->format('Y-m-d H:i:s');
    
    $retry_after = Utils::format_date($expires_at_str);
    
    
    return $retry_after;
    
    
  } // get_retry_after()
    
    
    
    
    
    
    
    
    
    
    
    
  function delete_expired(string $key): int {

    
    if ( !isset($this->limiters[$key]) ):
        
      return false;
      
    endif;

    
    $current_time = date('Y-m-d H:i:s');


    $query = "DELETE FROM `RateLimits`
              WHERE `key` = :key
                AND `expires_at` < :current_time";

    
    try {

      $stmt = $this->db->prepare($query);
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
      
      $stmt->execute();
      
      // Return the number of rows deleted
      return $stmt->rowCount();

    } catch (PDOException $e) {
      
      return 0;

    }
    

  } // delete_expired()
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  public static function make_tables( $db ): bool {
    
    
    try {
      
      
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS RateLimits (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          key VARCHAR(64) NOT NULL,
          client_ip VARCHAR(255) NOT NULL,
          expires_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );"
      );
      
      
      return $result;
      
    } catch (PDOException $e) {
      
      echo "Error: " . $e->getMessage();
      
      return false;
      
    }
    
    
  } // make_tables()
    
    
    
    
    
    
    
    
    
    
  public static function get_instance() {
    
    if (self::$instance === null):
      
      self::$instance = new self();
      
    endif;
    
    
    return self::$instance;
    
  } // get_instance()
    
    
    
    
    
} // ::RateLimits
  
  