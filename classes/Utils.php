<?php

/**
 * 
 *
 */
class Utils {

  
  private static $instance = null;
  
  

  
  
  
  
  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
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
  
  
  
  
  
  
  
  

    
} // ::Utils
