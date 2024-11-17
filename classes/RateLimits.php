<?php


use RedisStorage as CSBlog_Redis;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\RedisStorage;


class RateLimits {
  
  
  private static $instance = null;
  
  private $storage;
  
  private $limiters = [];
  
  
  
  
  
  public function __construct() {
    
    // Set up Redis storage
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    if ( !$redis->ping() ):

      die("Redis connection failed");

    endif;
    
    $this->storage = new CSBlog_Redis($redis);
    
  } // _construct()
  
  
  
  
  
  
  
  /**
  * Configure a rate limiter with a specific ID and policy.
  *
  * @param string $id Unique identifier for the rate limiter.
  * @param int $limit Maximum number of actions allowed.
  * @param string $interval Time window for the rate limit (e.g., '5 minutes').
  */
  public function configure_limiter(string $id, int $limit, string $interval): void {
    
    $this->limiters[$id] = new RateLimiterFactory([
      'id' => $id,
      'policy' => 'sliding_window',
      'limit' => $limit,
      'interval' => $interval,
    ], $this->storage);
    
  } // configure_limiter()
  
  
  
  
  
  
  
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
      
      throw new InvalidArgumentException("Rate limiter '{$id}' is not configured.");

    endif;
    
    debug_log("Rate limit {$id} IS configured");
    
    
    $limiter = $this->limiters[$id]->create();
    
    $limit = $limiter->consume(1);
    
    
    
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
    
    $limitState = $limiter->consume(0);

    if ( !$limitState->isAccepted() ) {
        // Return the time when the user can retry
        return $limit->getRetryAfter()->getTimestamp();
    }

    return null; // No rate limit, user can proceed

  } // get_retry_after()
  
  
  
  
  public static function get_instance() {
    
    if (self::$instance === null):
      
      self::$instance = new self();
      
    endif;
    
    
    return self::$instance;
    
  } // get_instance()
  
  
  
  
  
} // ::RateLimits

