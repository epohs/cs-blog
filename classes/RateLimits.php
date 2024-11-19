<?php



/**
* 
* 
* 
* @todo this needs to check for Redis and use that if available
*/
class RateLimits {
  
  
  private static $instance = null;
  
  private $db = null;
  
  private $limiters = [];
  
  
  
  
  
  
  public function __construct() {
    
    // Set up Redis storage
    // $redis = new Redis();
    // $redis->connect('127.0.0.1', 6379);
    
    // if ( !$redis->ping() ):
      
      //   die("Redis connection failed");
      
      // endif;
      
      $db = Db::get_instance();
      
      $this->db = $db->get_conn();
      
      
    } // _construct()
    
    
    
    
    
    
    
    /**
    * Configure a rate limiter with a specific ID and policy.
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
    * @return bool True if the request is allowed, false otherwise.
    */
    public function check(string $key, ?bool $increment = true ): bool {
      
      debug_log("checking rate limit {$key}");
      
      if ( !isset($this->limiters[$key]) ):
        
        return false;
        
      endif;
      
      
      
      // END HERE BECAUSE I'M NOT FINISHED
      // return true;
      
      
      
      // Calculate the number of tries used
      $tries_used = $this->get_tries_used($key);
      
      //debug_log('Tries used: ' . var_export($tries_used, true));


      if ( is_countable($tries_used) && (count($tries_used) >= $this->limiters[$key]['limit']) ):
        
        debug_log('Too many tries.');
        debug_log('countable? ' . var_export(is_countable($tries_used), true));
        if ( is_countable($tries_used) ):
          debug_log('count: ' . var_export(count($tries_used), true));
        endif;

        $del = $this->delete_expired($key);

        debug_log("Number of keys deleted: {$del}");
        
        return false;
        
      else:
        
        
        debug_log('Num tries in window: ' . var_export(count($tries_used), true));


        if ( $increment ):
          
          $new_id = $this->add_hit( $key );
          
          debug_log('Hit added ID: ' . var_export($new_id, true));
          
        endif;
        
        
        return true;
        
      endif;
      
      
      
      
      
    } // check()
    
    
    
    
    
    
    
    
    
    
    
    
    
    private function add_hit( string $key ): int|false {
      
      
      $client_ip = Utils::get_client_ip();
      
      
      if ( $client_ip === false ):
        
        return false;
        
      endif;
      
      
      
      try {
        
        $sql = "INSERT INTO RateLimits (`key`, `client_ip`) VALUES (:key, :client_ip)";
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->bindParam(':client_ip', $client_ip, PDO::PARAM_STR);
        
        // Execute the query
        if ( $stmt->execute() ):
          
          return $this->db->lastInsertId();
          
        else:
          
          debug_log('STATEMENT DIDNT EXECUTE');
          
          return false;
          
        endif;
        
      } catch (PDOException $e) {
        
        return false;
        
      }
      
    } // add_hit()
    
    
    
    
    
    
    
    
    
    
    /**
    * Get the number of tries used for a specific limiter.
    *
    * @param string $key Identifier of the rate limiter.
    * @return int Number of tries used.
    */
    public function get_tries_used(string $key, ?int $limit = 0): array|false {
      
      
      if  (!isset($this->limiters[$key]) ):
        
        return false;
        
      endif;
      
      
      $limit = ( $limit ) ? $limit : $this->limiters[$key]['limit'];
      
      $seconds = $this->limiters[$key]['interval'] ?? null;

      $client_ip = Utils::get_client_ip();
      
      debug_log('get_tries_used() limit: ' . var_export($limit, true));
      
      
      if ( is_numeric($limit) && is_numeric($seconds) ):
        
        $limit = intval($limit);
        $seconds = intval($seconds);
        
      else:
        
        return false;
        
      endif;
      
      
      
      $query = "SELECT *
              FROM RateLimits
              WHERE `key` = :key
                AND `client_ip` = :client_ip
                AND `created_at` > date('now', '-' || :seconds || ' seconds')
              ORDER BY `created_at` ASC
              LIMIT :limit";
      
      
      
      $stmt = $this->db->prepare($query);
      
      
      // Bind the parameters
      $stmt->bindParam(':key', $key, PDO::PARAM_STR);
      $stmt->bindParam(':seconds', $seconds, PDO::PARAM_INT);
      $stmt->bindParam(':client_ip', $client_ip, PDO::PARAM_STR);
      $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
      
      
      $stmt->execute();
      
      
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
      
    } // get_tries_used()
    
    
    
    
    
    
    
    
    
    
    
    /**
    * Get retry time for a specific limiter if the limit is exceeded.
    *
    * @param string $key Identifier of the rate limiter.
    * @return int|null Timestamp of the retry time, or null if no retry needed.
    */
    public function get_retry_after(string $key): string|false {
      
      
      if ( !isset($this->limiters[$key]) ):
        
        return false;
        
      endif;


      
      $seconds = $this->limiters[$key]['interval'] ?? null;     
      
      
      $first_try_in_window = $this->get_tries_used($key, 1);

      $created_at = $first_try_in_window['created_at'];

      $created_at_date = new DateTime($created_at);

      $created_at_date->modify("+{$seconds} seconds");

      $created_at_str = $created_at_date->format('Y-m-d H:i:s');
      
      $retry_after = Utils::format_date($created_at_str);
      
      
      return $retry_after;
      
      
    } // get_retry_after()
    
    
    
    
    
    
    
    
    
    
    
    
  function delete_expired(string $key): int {

    
    if ( !isset($this->limiters[$key]) ):
        
      return false;
      
    endif;

    
    $seconds = $this->limiters[$key]['interval'] ?? null;  


    $query = "DELETE FROM RateLimits
              WHERE `key` = :key
                AND `created_at` > date('now', '-' || :seconds || ' seconds')";

    
    try {

      // Prepare the statement
      $stmt = $this->db->prepare($query);
      
      // Bind parameters
      $stmt->bindValue(':key', $key, PDO::PARAM_STR);
      $stmt->bindValue(':seconds', $seconds, PDO::PARAM_INT);
      
      // Execute the statement
      $stmt->execute();
      
      // Return the number of rows deleted
      return $stmt->rowCount();

    } catch (PDOException $e) {
      
      return 0;

    }

  } // delete_expired()
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  public static function make_tables( $db ): bool {
    
    
    $result = null;
    
    
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS RateLimits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key VARCHAR(64) NOT NULL,
        client_ip VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
  
  