<?php




class RateLimits {
  
  
  private static $instance = null;
  
  private $limiters = [];
  
  
  
  
  
  
  public function __construct() {
    
    // Set up Redis storage
    // $redis = new Redis();
    // $redis->connect('127.0.0.1', 6379);

    // if ( !$redis->ping() ):

    //   die("Redis connection failed");

    // endif;
    
  } // _construct()
  
  
  
  
  
  
  
  /**
  * Configure a rate limiter with a specific ID and policy.
  *
  * @param string $id Unique identifier for the rate limiter.
  * @param int $limit Maximum number of actions allowed.
  * @param string $interval Time window for the rate limit (e.g., '5 minutes').
  */
  public function set(string $id, int $limit, string $interval): bool {
    
    
    $interval_in_seconds = Utils::convert_to_seconds($interval);
    
    
    if ( $interval_in_seconds ):
    
      $this->limiters[$id] = [
        'id' => $id,
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
  * @param string $id Identifier of the rate limiter.
  * @return bool True if the request is allowed, false otherwise.
  */
  public function check(string $id): bool {
    
    debug_log("checking rate limit {$id}");
    
    if ( !isset($this->limiters[$id]) ):
      
      debug_log("Rate limit {$id} is not configured");
      
      return false;

    endif;
    
    debug_log("Rate limit {$id} IS configured");
    
    
    // END HERE BECAUSE I'M NOT FINISHED
    return true;
    
    
    
    $limiter = $this->limiters[$id]->create();
    
    $limit = $limiter->consume(1);
    
    
    // you can also use the ensureAccepted() method - which throws a
    // RateLimitExceededException if the limit has been reached
    // $limiter->consume(1)->ensureAccepted();

    // to reset the counter
    // $limiter->reset();

    
    
    
    
    // Calculate the number of tries used
    $tries_used = $this->get_tries_used($id);
    
    debug_log("Tries used: {$tries_used}");

    
    if ( !$limit->isAccepted() ):
      
      return false;
      
    endif;
    
    return true;
    
  } // check()
  
  
  
  
  
  
  
  
  
  /**
  * Get the number of tries used for a specific limiter.
  *
  * @param string $id Identifier of the rate limiter.
  * @return int Number of tries used.
  */
  public function get_tries_used(string $id): int {

    if (!isset($this->limiters[$id])):

      throw new InvalidArgumentException("Rate limiter '{$id}' is not configured.");

    endif;
    
    $limiter = $this->limiters[$id]->create();
    $limitState = $limiter->consume(0); // Inspect state without consuming

    return $limitState->getLimit() - $limitState->getRemainingTokens();

  } // get_tries_used()
  
  
  
  
  
  
  
  
  
  
  
  /**
  * Get retry time for a specific limiter if the limit is exceeded.
  *
  * @param string $id Identifier of the rate limiter.
  * @return int|null Timestamp of the retry time, or null if no retry needed.
  */
  public function get_retry_after(string $id): ?int {

    if (!isset($this->limiters[$id])) {
        throw new InvalidArgumentException("Rate limiter '{$id}' is not configured.");
    }

    $limiter = $this->limiters[$id]->create();
    
    $limit = $limiter->consume(0);

    if ( !$limit->isAccepted() ):
      
      // Return the time when the user can retry
      return $limit->getRetryAfter()->getTimestamp();
    
    endif;
    

    return null; // No rate limit, user can proceed

  } // get_retry_after()
  
  
  
  
  
  
  
  

  public static function make_tables( $db ): bool {


    $result = null;
    
  
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS RateLimits (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          selector VARCHAR(64) NOT NULL,
          client_ip VARCHAR(255) NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        );"
      );
      
      
      $result = ( $result === false ) ? false : true;
      
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage();
      
      $result = false;
      
    }
    
    
    return $result;
    
  

  } // make_tables()
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
    
    if (self::$instance === null):
      
      self::$instance = new self();
      
    endif;
    
    
    return self::$instance;
    
  } // get_instance()
  
  
  
  
  
} // ::RateLimits

