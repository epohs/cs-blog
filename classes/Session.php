<?php

/**
 * 
 *
 */
class Session {


  
  

  public static function set_key($keys, $value): void {
  
    
    if ( is_string($keys) ):
    
      
      $_SESSION[$keys] = $value;
  
      
    elseif (is_array($keys) ):
      
      
      // Copy of the session array
      $cur_array = $_SESSION;
      
      $new_value = $value;
  
      // Create the nested array structure based on the keys
      while ( $key = array_pop($keys) ):
        
        // Wrap the current value with the next key
        $new_value = [$key => $new_value];
      
      endwhile;
  
      // Merge the new structure into the session array
      $_SESSION = array_replace_recursive($cur_array, $new_value);
      
      
    endif;

    
  } // set_key()

  
  
  
  
  
  
  
  
  
  public static function get_key($keys) {
    
    
    if ( is_string($keys) ):
      
      // Return the value if the key exists, otherwise return null
      return $_SESSION[$keys] ?? null;
      
    elseif ( is_array($keys) ):
      
      
      // Start from the session array
      $cur_array = $_SESSION;

      // Traverse the keys to go deeper into the session array
      foreach ( $keys as $key ):
        
        
        // If the key exists, move deeper, otherwise return null
        if ( isset($cur_array[$key]) ):
        
          $cur_array = $cur_array[$key];
        
        else:
        
          return null;
        
        endif;
        
        
      endforeach;
      

      // Return the found value
      return $cur_array;
      
      
    endif;

    
  } // get_key()

  
  
  
  
  
  
  
  
  public static function key_isset($keys): bool {
  
    
    if ( is_string($keys) ):
    
      
      // Return true if the single key is set
      return isset($_SESSION[$keys]);
  
      
    elseif ( is_array($keys) ):
      
      
      // Start from the session array
      $cur_array = $_SESSION;

      // Traverse the keys
      foreach ( $keys as $key ):
        
        // If the key exists, move deeper
        if ( isset($cur_array[$key]) ):
        
          $cur_array = $cur_array[$key];
        
        else:
        
          // Return false if any key is not set
          return false;
        
        endif;
        
      endforeach;

      // Return true if all keys exist
      return true;
      
      
    endif;

    
    // Return false if neither string nor array was provided
    return false;

  } // key_isset()

  
  
  
  
  
  
  
  
  
  public static function delete_key($keys): void {
  
    
    if ( is_string($keys) ):
    
      
      // Unset the single key if it exists
      unset($_SESSION[$keys]);
  
      
    elseif ( is_array($keys) ):
      
      
      // Copy the session array to a variable
      $cur_array = $_SESSION;
      
      // Traverse all but the last key
      foreach ( $keys as $i => $key ):
        
        if ( isset($cur_array[$key]) ):
        
          // If it's the last key, unset it
          if ( $i === array_key_last($keys) ):
          
            unset($cur_array[$key]);
          
          else:
          
            // Move deeper into the array
            $cur_array = $cur_array[$key];
          
          endif;
        
        else:
        
          // Key not found, nothing to delete
          return;
        
        endif;
        
      endforeach;

      // Rebuild the session array without the deleted key
      $_SESSION = array_replace_recursive($_SESSION, $cur_array);
      
      
    endif;
    
    
  } // delete_key()


  
  
  
  
  
  
  
  
  public static function destroy(): void {

    session_unset();
    session_destroy();
  
  } // destroy()
  
  
  
  
  
  
  public static function regenerate(): void {
  
    session_regenerate_id(true);
  
  } // regenerate()


  

  
  

    
} // ::Session
