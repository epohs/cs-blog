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
  * Test whether a string is is a valid datetime AND matches a specific format.
  */
  public static function is_valid_datetime(string $datetime, string $format = 'Y-m-d H:i:s'): bool {
    
    $dt = DateTime::createFromFormat($format, $datetime);
    
    // Check if the DateTime object was successfully created and if the input fully matches the format
    return $dt && ($dt->format($format) === $datetime);
    
  } // is_valid_datetime()
  
  
  
  
  
  
  
  /**
  * Test whether a datetime string is in the future.
  */
  public static function is_future_datetime(string $datetime): bool {
    
    try {
      
      $date = new DateTime($datetime);
      $now = new DateTime();
      
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
      
      $date = new DateTime($datetime);
      $now = new DateTime();
      
      return $date < $now; // Returns true if $datetime is in the future
      
    } catch (Exception $e) {
      
      // Handle invalid datetime formats
      return false;
      
    }
    
  } // is_past_datetime()
  

  
  
  
  
  
  
  /**
  * Return a formatted datetime string.
  *
  * We use UTC for all internal datetimes, but anywhere dates or times are displayed
  * publically we need to convert those to, preferably, the user's local timezone.
  * This function should be used everywhere a date is displayed in a template.
  *
  * @param $time string|DateTime. Create NOW string if null.
  * @param $format string Return datetime format.
  * @param $tz string Return datetime time zone.
  */
  public static function format_date( $time = null, ?string $format = null, ?string $tz = null ): string {
    
    
    $Config = Config::get_instance();
    
    
    $default_format = $Config->get('date_format');
    
    $default_timezone = $Config->get('timezone');
    
    
    $format = ( $format ) ?? $default_format;
    
    $timezone = ( $tz ) ?? $default_timezone;
    
    
    if ( is_null($time) ):

      $date_utc = date($format);

      // Create a DateTime object in UTC
      $date = new DateTime( $date_utc, new DateTimeZone('UTC') );

    elseif ( is_string($time) &&  self::is_valid_datetime($time) ):
      
      // Create a DateTime object in UTC
      $date = new DateTime( $time, new DateTimeZone('UTC') );

    elseif ( $time instanceof DateTime ):

      $date = $time;

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
  * Return the IP address of the current visitor.
  */
  public static function get_client_ip(): string|false {
    
    
    // Check the most reliable headers in order
    $headers_to_check = [
      'HTTP_CF_CONNECTING_IP',
      'HTTP_CLIENT_IP',
      'HTTP_X_FORWARDED_FOR',
      'HTTP_X_FORWARDED',
      'HTTP_X_CLUSTER_CLIENT_IP',
      'HTTP_FORWARDED_FOR',
      'HTTP_FORWARDED',
      'REMOTE_ADDR'
    ];
    
    
    
    foreach ($headers_to_check as $header) :
      
      if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) :
        
        $ip_list = explode(',', $_SERVER[$header]);
        
        foreach ($ip_list as $ip) :
          
          $ip = trim($ip);
          
          $Config = Config::get_instance();
          
          if ( $Config->get('debug') ):
            
            $is_ip_valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
            
          else:
            
            $is_ip_valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            
          endif;
          
          if ( $is_ip_valid ) :
            
            return $ip;
            
          endif;
          
          
          
        endforeach;
        
      endif;
      
    endforeach;
    
    
    
    // Return false if no valid IP is found
    return false;
    
  } // get_client_ip()
  
  
  
  
  
  
} // ::Utils
