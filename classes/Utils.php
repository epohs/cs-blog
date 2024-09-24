<?php

/**
 * 
 *
 */
class Utils {

  
  private static $instance = null;
  
  

  
  
  
  
  
  private function __construct() {

    
      
  } // __construct()
  
  






  public static function is_valid_json( $str ): bool {

    json_decode($str);

    return (json_last_error() === JSON_ERROR_NONE);
  
  } // is_valid_json()









  public static function is_valid_datetime(string $datetime, string $format = 'Y-m-d H:i:s'): bool {

    $dt = DateTime::createFromFormat($format, $datetime);

    // Check if the DateTime object was successfully created and if the input fully matches the format
    return $dt && $dt->format($format) === $datetime;

  } // is_valid_datetime()


  
  
  
  
  
  
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
    
  } // delete()
  
  






  
  
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
  
  
  

    
} // ::Utils
