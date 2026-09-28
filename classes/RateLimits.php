<?php

/**
 * Allow for the creation of limits to the number of times a user can 
 * carry out a given action within a given period of time.
 *
 * Callers decide who is being limited by passing an identity,
 * such as the client IP address.
 * 
 * @todo This needs to check for Redis and use that if available
 */
class RateLimits {
  
  
  private static $instance = null;
  
  private $pdo = null;
  
  private $limiters = [];
  
  
  
  
  
  
  private function __construct() {
    
    
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
   * @param string $identity Who is being limited, e.g. a client IP.
   * @param bool $increment Should a hit be added to the limiter.
   *
   * @return bool True if the request is allowed, false otherwise.
   */
  public function check(string $key, string $identity, ?bool $increment = true ): bool {
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    
    
    $limit = $this->limiters[$key]['limit'];
    
    
    // Add the hit before counting. Each request then counts itself
    // along with every hit added before it, so a burst of concurrent
    // requests can't all read the same count and slip in together.
    $hit_added = $increment && $this->add_hit( $key, $identity );
    
    $tries_used = $this->count_tries_used($key, $identity);
    
    
    if ( $tries_used === false ):
      
      return true;
      
    endif;
    
    
    // Don't count the hit just added against this request.
    $tries_before = ( $hit_added ) ? $tries_used - 1 : $tries_used;
    
    
    if ( $tries_before < $limit ):
      
      return true;
      
    endif;
    
    
    // Rate limited. We clear the expired tries during failed
    // attempts to put the database burden on the offenders, and
    // cap the hits kept for this client at 2x the limit.
    $this->delete_expired($key);
    
    if ( $increment ):
      
      $this->trim_hits( $key, $identity, $limit * 2 );
      
    endif;
    
    
    return false;
    

  } // check()
    
    
    
    
    
    
    
    
  /**
   * Add a new entry for this limiter. Set the expires_at
   * to the appropriate number of seconds in the future.
   * 
   * @return int The ID of the hit added or false if adding failed.
   */
  private function add_hit( string $key, string $identity ): int|false {
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    

    $seconds = $this->limiters[$key]['interval'];

    $date = new DateTime('now', new DateTimeZone('UTC'));
  
    $date->modify("+{$seconds} seconds");

    $expires_at_str = $date->format('Y-m-d H:i:s');
    

    $query = 'INSERT INTO `RateLimits` (`key`, `identity`, `expires_at`) 
              VALUES (:key, :identity, :expires_at)';


    try {
      
      $stmt = $this->pdo->prepare($query);
      
      // Bind parameters
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':identity', $identity, PDO::PARAM_STR);
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
   * Delete all but the newest hits for this client, even if they
   * haven't expired, so an offender can't grow the table forever.
   *
   * @param string $key Identifier of the rate limiter.
   * @param string $identity Who is being limited.
   * @param int $cap Maximum rows to keep for this client/key.
   */
  private function trim_hits( string $key, string $identity, int $cap ): void {
    
    
    $query = 'DELETE FROM `RateLimits`
              WHERE `id` IN (
                SELECT `id` FROM `RateLimits`
                WHERE `key` = :key
                  AND `identity` = :identity
                ORDER BY `id` DESC
                LIMIT -1 OFFSET :cap
              )';
    
    
    try {
      
      $stmt = $this->pdo->prepare($query);
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':identity', $identity, PDO::PARAM_STR);
      $stmt->bindValue(':cap', $cap, PDO::PARAM_INT);
      
      $stmt->execute();
      
    } catch (PDOException $e) {
      
      debug_log('trim_hits() failed: ' . $e->getMessage());
      
    }
    
    
  } // trim_hits()
  
    
    
    
    
    
    
    
  /**
   * Count the unexpired tries used for a specific limiter.
   *
   * @param string $key Identifier of the rate limiter.
   * @param string $identity Who is being limited.
   *
   * @return int|false Number of tries used, or false if the query failed.
   */
  private function count_tries_used(string $key, string $identity): int|false {
    
    
    if  ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;
    
    
    // Always use UTC/GMT as our baseline.
    $current_time = gmdate('Y-m-d H:i:s');
    
    
    $query = 'SELECT COUNT(*)
              FROM `RateLimits`
              WHERE `key` = :key
                AND `identity` = :identity
                AND `expires_at` > :current_time';
    
    
    try {

      $stmt = $this->pdo->prepare($query);
    
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':identity', $identity, PDO::PARAM_STR);
      $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
      
      
      $stmt->execute();
      
      return (int) $stmt->fetchColumn();
      

    } catch (PDOException $e) {
        
      return false;
      
    }
    

  } // count_tries_used()
  
    
    
    
    
    
    
    
  /**
   * Get the next time when this limiter can be successfully hit again.
   *
   * @param string $key Identifier of the rate limiter.
   * @param string $identity Who is being limited.
   *
   * @return string|false Next available retry time, or false if limiter not found.
   */
  public function get_retry_after(string $key, string $identity): string|false {
    
    
    if ( !isset($this->limiters[$key]) ):
      
      return false;
      
    endif;

   
    $current_time = gmdate('Y-m-d H:i:s');
    
    
    // A client is allowed again once it has fewer than `limit`
    // unexpired tries, which is when its `limit`-th newest try expires.
    $query = 'SELECT `expires_at`
              FROM `RateLimits`
              WHERE `key` = :key
                AND `identity` = :identity
                AND `expires_at` > :current_time
              ORDER BY `expires_at` DESC
              LIMIT 1 OFFSET :offset';
    
    
    try {

      $stmt = $this->pdo->prepare($query);
      
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':identity', $identity, PDO::PARAM_STR);
      $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
      $stmt->bindValue(':offset', $this->limiters[$key]['limit'] - 1, PDO::PARAM_INT);
      
      $stmt->execute();
      
      $retry_after = $stmt->fetchColumn();
      
    } catch (PDOException $e) {
      
      $retry_after = false;
      
    }
    
    
    return $retry_after ?: $current_time;
    
    
  } // get_retry_after()
    
    
    
    
    
    
    
    
  /**
   * Delete all expired hits for a given limiter.
   *
   * @return int|false Number of rows deleted or false if no key.
   */
  public function delete_expired(string $key): int|false {

    
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
      
      
      $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `RateLimits` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `key` VARCHAR(64) NOT NULL,
          `identity` VARCHAR(255) NOT NULL,
          `expires_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
      );
      

      $pdo->exec(
        'CREATE INDEX IF NOT EXISTS idx_ratelimits_lookup 
          ON RateLimits(
            `key`,
            `identity`,
            `expires_at`
        )'
      );

      
      return true;
      
      
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
  
  
