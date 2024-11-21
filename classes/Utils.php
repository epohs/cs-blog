<?php

/**
* 
*
*/
class Utils {
  
  
  private static $instance = null;
  
  
  
  
  
  
  
  
  private function __construct() {
    
    
    
  } // __construct()
  
  
  
  
  
  
  
  
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
  
  
  
  
  
  
  
  
  
  
  public static function is_valid_json( $str ): bool {
    
    json_decode($str);
    
    return (json_last_error() === JSON_ERROR_NONE);
    
  } // is_valid_json()
  
  
  
  
  
  
  
  
  
  public static function is_valid_datetime(string $datetime, string $format = 'Y-m-d H:i:s'): bool {
    
    $dt = DateTime::createFromFormat($format, $datetime);
    
    // Check if the DateTime object was successfully created and if the input fully matches the format
    return $dt && ($dt->format($format) === $datetime);
    
  } // is_valid_datetime()
  
  
  
  
  
  
  
  
  function is_future_datetime(string $datetime): bool {
    
    try {
      
      $date = new DateTime($datetime);
      $now = new DateTime();
      
      return $date > $now; // Returns true if $datetime is in the future
      
    } catch (Exception $e) {
      
      // Handle invalid datetime formats
      return false;
      
    }
    
  } // is_future_datetime()
  
  
  





  function is_past_datetime(string $datetime): bool {
    
    try {
      
      $date = new DateTime($datetime);
      $now = new DateTime();
      
      return $date < $now; // Returns true if $datetime is in the future
      
    } catch (Exception $e) {
      
      // Handle invalid datetime formats
      return false;
      
    }
    
  } // is_past_datetime()
  

  
  
  
  
  
  
  public static function format_date( $time_str = null, ?string $format = null, ?string $tz = null ): string {
    
    
    $default_format = 'F j, Y, g:i a';
    
    $default_timezone = 'America/New_York';
    
    
    $format = ( $format ) ?? $default_format;
    
    $timezone = ( $tz ) ?? $default_timezone;
    
    
    $date_utc = ( $time_str ) ?? date($format);
    
    
    // Create a DateTime object in UTC
    $date = new DateTime( $date_utc, new DateTimeZone('UTC') );
    
    // Set the timezone to NYC (Eastern Time)
    $date->setTimezone( new DateTimeZone($timezone) );
    
    // Format the date for display
    return $date->format( $format );
    
  } // format_date()
  
  
  
  
  
  
  
  
  
  
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
  
  
  
  
  
  
  
  
  
  
  
  public static function is_alphanumeric(string $str): bool {
    
    return preg_match('/^[a-zA-Z0-9]+$/', $str) === 1;
    
  } // is_alphanumeric()
  
  
  
  
  
  
  
  
  
  public static function get_client_ip(): string|false {
    
    
    // Check the most reliable headers in order
    $headers_to_check = [
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
          
          $config = Config::get_instance();
          
          if ( $config->get('debug') ):
            
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
