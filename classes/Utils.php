<?php

/**
* Useful functions that come in handy throughout out application.
*
* All functions here should be static and not rely too much on other classes.
*/
class Utils {
  




  
  
  /**
  * Take a string and return a number of seconds.
  *
  * For instance '1 hour' would return 3600, '5 minutes' returns 300.
  */
  public static function convert_to_seconds(string $time_string): int|false {
    
    
    // Regular expression to match the pattern "number + unit"
    if (preg_match('/^\s*(\d+)\s*(seconds?|minutes?|hours?|days?)\s*$/i', $time_string, $matches)) :
      
      $value = (int) $matches[1];
      $unit = strtolower($matches[2]);
      
      // Convert the value based on the unit
      return match ($unit) {
        'second', 'seconds' => $value,
        'minute', 'minutes' => $value * 60,
        'hour',   'hours'   => $value * 3600,
        'day',    'days'    => $value * 86400,
        default             => false,
      };
      
    endif;
    
    
    // Return false if the format doesn't match
    return false;
    
  } // convert_to_seconds()
  
  
  
  
  
  
  
  
  
  /**
  * Test whether a string is valid JSON or not.
  */
  public static function is_valid_json( $str ): bool {
    
    json_decode($str);
    
    return (json_last_error() === JSON_ERROR_NONE);
    
  } // is_valid_json()
  
  
  
  
  
  
  
  /**
   * Handle the logic to determine whether an HTML
   * form checkbox is checked or not.
   */
  public static function is_checked($value = null): string {
      
    return $value ? 'checked' : '';
    
  } // is_checked()
  
  
  
  
  
  
  
  /**
   * Handle the logic to determine whether an HTML
   * form select option is selected or not.
   */
  public static function is_selected($value, $current): string {
    
    return (string) $value === (string) $current ? 'selected' : '';
    
  } // is_selected()


  
  
  
  
  
  
  
  
  /**
   * Take a string with any type of character and return
   * a sanitized string for use as a URL segment.
   */
  public static function make_sluggy( string $string ): string {
    
    // Convert to lowercase
    $string = mb_strtolower($string, 'UTF-8');
  
    // Replace spaces with a single hyphen
    $string = preg_replace('/\s+/', '-', $string);
  
    // Convert special characters to ASCII equivalents if possible
    if ( function_exists('transliterator_transliterate') ):
      
      $string = transliterator_transliterate('Any-Latin; Latin-ASCII', $string);
      
    endif;
  
    // Remove non-alphanumeric characters (keep hyphens)
    $string = preg_replace('/[^a-z0-9-]/', '', $string);
  
    // Remove multiple consecutive hyphens
    $string = preg_replace('/-+/', '-', $string);
  
    // Trim hyphens from start and end
    $string = trim($string, '-');
  
    return $string;
    
  } // make_sluggy()
  
  
  
  
  
  
  
  /**
   * Apply basic validation to a selector string.
   */
  public static function is_valid_selector(?string $str, array $args = []): bool {
    
    
    $defaults = [
      'min_len'        => 5,
      'max_len'        => 128,
      'allow_special'  => false,
    ];
  
    $args = array_merge($defaults, $args);
    
    
    if ( !is_string($str) ):
      
      return false;
      
    endif;
    
  
    $length = mb_strlen($str);
    
  
    if ($length < $args['min_len'] || $length > $args['max_len']):
  
      return false;
  
    endif;
    
  
    if ($args['allow_special']):
      
      // Allow any non-whitespace characters (no spaces)
      if (preg_match('/\s/', $str)):
        
        return false;
        
      endif;
      
    else:
      
      // Only allow alphanumeric characters, no spaces or special chars
      if (!preg_match('/^[a-zA-Z0-9]+$/u', $str)):
        
        return false;
        
      endif;
      
    endif;
  
    
    return true;
    
    
  } // is_valid_selector()


  
  
  
  
  
  
  
  
  /**
  * Test whether a string is is a valid datetime AND matches a specific format.
  */
  public static function is_valid_datetime(?string $datetime, string $format = 'Y-m-d H:i:s'): bool {
    
    $dt = DateTime::createFromFormat($format, $datetime);
    
    // Check if the DateTime object was successfully created and if the input fully matches the format
    return $dt && ($dt->format($format) === $datetime);
    
  } // is_valid_datetime()
  
  
  
  
  
  
  
  /**
  * Test whether a datetime string is in the future.
  */
  public static function is_future_datetime(string $datetime): bool {
    
    try {
      
      $date = new DateTime($datetime, new DateTimeZone('UTC'));
      $now = new DateTime('now', new DateTimeZone('UTC'));
      
      return $date > $now; // Returns true if $datetime is in the future
      
    } catch (Exception $e) {
      
      // Handle invalid datetime formats
      return false;
      
    }
    
  } // is_future_datetime()
  
  
  




  /**
  * Test whether a datetime string is in the past.
  */
  public static function is_past_datetime(string $datetime): bool {
    
    try {
      
      $date = new DateTime($datetime, new DateTimeZone('UTC'));
      $now = new DateTime('now', new DateTimeZone('UTC'));
      
      return $date < $now; // Returns true if $datetime is in the future
      
    } catch (Exception $e) {
      
      // Handle invalid datetime formats
      return false;
      
    }
    
  } // is_past_datetime()
  

  
  
  
  
  
  
  /**
  * Return a formatted datetime string.
  *
  * We use UTC for all internal datetimes, but anywhere dates or 
  * times are displayed publically we need to convert those to, 
  * preferably, the user's local timezone.
  * This function should be used everywhere a date is displayed 
  * in a template.
  *
  * This function will assume that all input times are UTC unless 
  * $time is a DateTime object.
  *
  * @param $time string|DateTime. Create NOW string if null.
  * @param $format string Return datetime format.
  * @param $tz string Return datetime time zone.
  */
  public static function format_date( $time = null, ?string $format = null, ?string $tz = null ): string|false {
    
    
    $Config = Config::get_instance();
    
    
    $default_format = $Config->get('date_format');
    
    $default_timezone = $Config->get('timezone');
    
    
    $format = ( $format ) ?? $default_format;
    
    $timezone = ( $tz ) ?? $default_timezone;
    
    
    if ( is_null($time) ):

      // Create a DateTime object in UTC
      $date = new DateTime( 'now', new DateTimeZone('UTC') );

    elseif ( is_string($time) && self::is_valid_datetime($time) ):
      
      $date = new DateTime( $time, new DateTimeZone('UTC') );

    elseif ( $time instanceof DateTime ):

      $date = $time;
      
    else:
      
      return false;

    endif;
    
    

    
    // Set the timezone to NYC (Eastern Time)
    $date->setTimezone( new DateTimeZone($timezone) );
    
    // Format the date for display
    return $date->format( $format );
    
    
  } // format_date()
  
  
  
  
  
  
  
  
  
  /**
  * Return an alphanumeric string of a given length.
  */
  public static function generate_random_string(int $length): string {
    
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_bytes = random_bytes($length);
    
    $random_string = '';
    
    for ($i = 0; $i < $length; $i++):
      
      $random_string .= $characters[ord($random_bytes[$i]) % $characters_length];
      
    endfor;
    
    return $random_string;
    
  } // generate_random_string()
  
  
  
  
  
  
  
  
  /**
  * Test whether a given string is alphanumeric.
  */
  public static function is_alphanumeric(string $str): bool {
    
    return preg_match('/^[a-zA-Z0-9]+$/', $str) === 1;
    
  } // is_alphanumeric()
  
  
  
  
  
  
  
  /**
  * Test whether all array values are integers.
  */
  public static function all_integers(array $arr): bool {
    
    return count(array_filter($arr, fn($value) => !is_int($value))) === 0;
    
  } // all_integers()
  
  
  




  
  /**
   * Get the real client IP address.
   * 
   * Supports direct connections and Cloudflare-proxied requests.
   * Configure 'cloudflare_proxy' => true when behind Cloudflare's proxy (orange cloud).
   * 
   * @return string|false Client IP address or false if unavailable.
   */
  public static function get_client_ip(): string|false {
    
    $Config = Config::get_instance();
    
    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
    
    
    // In debug mode, allow private ranges for local network testing.
    // In production, reject private ranges as they indicate spoofing or misconfiguration.
    if ( $Config->get('debug') ):
      $filter_flags = FILTER_FLAG_NO_RES_RANGE;
    else:
      $filter_flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
    endif;
    
    
    // Cloudflare proxy mode — read real IP from header
    if ( $Config->get('cloudflare_proxy') ):
      
      $cf_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
      
      if ( $cf_ip && filter_var($cf_ip, FILTER_VALIDATE_IP, $filter_flags) ):
        return $cf_ip;
      endif;
      
      // Header missing or invalid — possible misconfiguration or direct access bypass
      debug_log("Cloudflare proxy enabled but CF-Connecting-IP missing or invalid. REMOTE_ADDR: {$remote_addr}");
      
      // Fall through to REMOTE_ADDR as last resort
      
    endif;
    
    
    // Direct connection or Cloudflare fallback
    if ( $remote_addr && filter_var($remote_addr, FILTER_VALIDATE_IP, $filter_flags) ):
      return $remote_addr;
    endif;
    
    
    return false;
    
  } // get_client_ip()


  
  
  
  
  
  
} // ::Utils
