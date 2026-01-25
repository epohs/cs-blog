<?php

/**
 * Allow for the creation of limits to the number of times a user can 
 * carry out a given action within a given period of time.
 *
 * User identification is handled both by IP address and session variables.
 * 
 * @todo This needs to check for Redis and use that if available
 * 
 * @todo Review all date time functions and standardize
 */
class RateLimits {
  
  
  private static $instance = null;
  
  private $pdo = null;
  
  private $limiters = [];
  
  
  
  
  
  
  public function __construct() {
    
    
    $Db = Database::get_instance();
    
    $this->pdo = $Db->get_pdo();
    
    
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
   * @param bool $increment Should a hit be added to the limiter.
   *
   * @return bool True if the request is allowed, false otherwise.
   */
  public function check(string $key, ?bool $increment = true ): bool {
    
    
    $return = false;
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return $return;
      
    endif;
    
    
    // Calculate the number of tries used
    $tries_used = $this->get_tries_used($key);


    // If the number of tries used has reached the number of tries
    // allowed by this limiter, leave the return value false as
    // this attempt failed, otherwise set return to true.
    //
    // We clear the expired tries during failed attempts to put
    // the database burden on the offenders.
    if ( is_countable($tries_used) && (count($tries_used) >= $this->limiters[$key]['limit']) ):

      $del = $this->delete_expired($key);
      
    else:
      
      $return = true;
      
    endif;
    

    
    if ( $increment ):
      
      if ( $return ):
        
        // Normal case: request allowed, just add the hit.
        $this->add_hit( $key );
        
      else:
        
        // Rate limited: add hit but enforce cap at 2x limit.
        $cap = $this->limiters[$key]['limit'] * 2;
        
        $this->add_hit_with_cap( $key, $cap );
        
      endif;
      
    endif;
    
    
    return $return;
    

  } // check()
    
    
    
    
    
    
    
    
  /**
   * Add a new entry for this limiter. Set the expires_at
   * to the appropriate number of seconds in the future.
   * 
   * @return int The ID of the hit added or false if adding failed.
   */
  private function add_hit( string $key ): int|false {
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    
    
    $client_ip = Utils::get_client_ip();
    
    $session_id = Session::get_key('id');
    
    
    // If neither the session ID, nor client IP are valid
    // then we can't identify the user, and rate limiting is
    // useless.
    if ( !$session_id && ($client_ip === false) ):
      
      return false;
      
    endif;
    

    $seconds = $this->limiters[$key]['interval'];

    $date = new DateTime('now', new DateTimeZone('UTC'));
  
    $date->modify("+{$seconds} seconds");

    $expires_at_str = $date->format('Y-m-d H:i:s');
    

    $query = 'INSERT INTO `RateLimits` (`key`, `client_ip`, `session_id`, `expires_at`) 
              VALUES (:key, :client_ip, :session_id, :expires_at)';


    try {
      
      $stmt = $this->pdo->prepare($query);
      
      // Bind parameters
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':client_ip', $client_ip, PDO::PARAM_STR);
      $stmt->bindValue(':session_id', $session_id, PDO::PARAM_STR);
      $stmt->bindValue(':expires_at', $expires_at_str, PDO::PARAM_STR);
      
      
      // Execute the query
      if ( $stmt->execute() ):
        
        return $this->pdo->lastInsertId();
        
      else:
        
        return false;
        
      endif;
      
    } catch (PDOException $e) {
      
      return false;
      
    }
    
  } // add_hit()








  /**
   * Add a hit while enforcing a maximum row cap for this client.
   * Deletes oldest rows (even if unexpired) to stay under the cap.
   *
   * @param string $key Identifier of the rate limiter.
   * @param int $cap Maximum rows to keep for this client/key.
   *
   * @return int|false The ID of the hit added or false if adding failed.
   */
  private function add_hit_with_cap( string $key, int $cap ): int|false {
    
    $client_ip = Utils::get_client_ip();
    
    $session_id = Session::get_key('id');
    
    
    if ( !$session_id && ($client_ip === false) ):
      
      return false;
      
    endif;
    
    
    try {
      
      // Count current rows for this client/key
      $count_query = 'SELECT COUNT(*) FROM `RateLimits`
                      WHERE `key` = :key
                        AND (`client_ip` = :client_ip OR `session_id` = :session_id)';
      
      $stmt = $this->pdo->prepare($count_query);
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':client_ip', $client_ip, PDO::PARAM_STR);
      $stmt->bindValue(':session_id', $session_id, PDO::PARAM_STR);
      $stmt->execute();
      
      $current_count = (int) $stmt->fetchColumn();
      
      
      // If at or above cap, delete oldest rows to make room
      if ( $current_count >= $cap ):
        
        $rows_to_delete = $current_count - $cap + 1;
        
        $delete_query = 'DELETE FROM `RateLimits`
                         WHERE `id` IN (
                           SELECT `id` FROM `RateLimits`
                           WHERE `key` = :key
                             AND (`client_ip` = :client_ip OR `session_id` = :session_id)
                           ORDER BY `expires_at` ASC
                           LIMIT :rows_to_delete
                         )';
        
        $stmt = $this->pdo->prepare($delete_query);
        
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->bindValue(':client_ip', $client_ip, PDO::PARAM_STR);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_STR);
        $stmt->bindValue(':rows_to_delete', $rows_to_delete, PDO::PARAM_INT);
        
        $stmt->execute();
        
      endif;
      
      
    } catch (PDOException $e) {
      
      debug_log('add_hit_with_cap cleanup failed: ' . $e->getMessage());
      
    }
    
    
    // Add the new hit using existing method
    return $this->add_hit( $key );
    
    
  } // add_hit_with_cap()
  
    
    
    
    
    
    
    
  /**
   * Get the tries used for a specific limiter.
   *
   * @param string $key Identifier of the rate limiter.
   * @param int $limit Number of tries to return. Default is the
   *.       limit defined by the limiter.
   *
   * @return array|false Array of tries used.
   */
  public function get_tries_used(string $key, ?int $limit = 0): array|false {
    
    
    if  ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    
    
    $limit = ( $limit ) ? $limit : $this->limiters[$key]['limit'];

    
    // Always use UTC/GMT as our baseline.
    $current_time = gmdate('Y-m-d H:i:s');

    $client_ip = Utils::get_client_ip();
    
    $session_id = Session::get_key('id');
    
    
    if ( is_numeric($limit) ):
      
      $limit = intval($limit);
      
    else:
      
      return false;
      
    endif;
    
    
    $query = 'SELECT *
              FROM `RateLimits`
              WHERE `key` = :key
                AND (`client_ip` = :client_ip OR `session_id` = :session_id)
                AND `expires_at` > :current_time
              ORDER BY `expires_at` DESC
              LIMIT :limit';
    
    
    try {

      $stmt = $this->pdo->prepare($query);
    
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
      $stmt->bindValue(':client_ip', $client_ip, PDO::PARAM_STR);
      $stmt->bindValue(':session_id', $session_id, PDO::PARAM_STR);
      $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
      
      
      $stmt->execute();
      
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Reorder to chronological order.
      usort($results, fn($a, $b) => strtotime($a['expires_at']) <=> strtotime($b['expires_at']));
      
      return $results;
      

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
   
    $limit = $this->limiters[$key]['limit'];
    
    
    $tries_used = $this->get_tries_used($key, $limit);
    
    $first_try = is_array($tries_used) && !empty($tries_used) ? reset($tries_used) : null;

    $expires_at = !is_null($first_try) ? $first_try['expires_at'] : 'now';

    $expires_at_date = new DateTime($expires_at, new DateTimeZone('UTC'));
      
    $retry_after = $expires_at_date->format('Y-m-d H:i:s');
    
    
    return $retry_after;
    
    
  } // get_retry_after()
    
    
    
    
    
    
    
    
  /**
   * Delete all expired hits for a given limiter.
   *
   * @return int Number of rows deleted.
   */
  function delete_expired(string $key): int {

    
    if ( !isset($this->limiters[$key]) ):
        
      return false;
      
    endif;

    
    $current_time = gmdate('Y-m-d H:i:s');


    $query = 'DELETE FROM `RateLimits`
              WHERE `key` = :key
                AND `expires_at` < :current_time';

    
    try {

      $stmt = $this->pdo->prepare($query);
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
      
      $stmt->execute();
      
      // Return the number of rows deleted
      return $stmt->rowCount();

    } catch (PDOException $e) {
      
      return 0;

    }
    

  } // delete_expired()
  
  
  
  
  
  
  
  
  /**
   * Create the database tables needed for rate limits.
   */
  public static function make_tables( $pdo ): bool {
    
    
    try {
      
      
      $result = $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `RateLimits` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `key` VARCHAR(64) NOT NULL,
          `client_ip` VARCHAR(255),
          `session_id` VARCHAR(32),
          `expires_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
      );
      
      
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
    
    if (self::$instance === null):
      
      self::$instance = new self();
      
    endif;
    
    
    return self::$instance;
    
  } // get_instance()
    
    
     
} // ::RateLimits
  
  
