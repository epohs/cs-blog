<?php

/**
 * Handle reading from and writing to the session.
 *
 * For use througout this application the session is
 * a strictly formatted multi-dimensional array with
 * named keys.
 */
class Session {


  
  

  
  
  /**
   * Set the given key to the given value.
   *
   * @param string|array $keys A string will set a key
   *        on the first tier of the array. An array is
   *        used to set a key in deeper levels.
   */
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

  
  
  
  
  
  
  
  /**
   * Get the value of a given key.
   *
   * @param string|array $keys A string will get a key
   *        on the first tier of the array. An array is
   *        used to get keys in deeper levels.
   */
  public static function get_key( $keys ) {
    
    
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

  
  
  
  
  
  
  /**
   * Test whether a given key is set in the session.
   *
   * String for root level keys, for deeper keys pass an array.
   */
  public static function key_isset( $keys ): bool {
  
    
    if ( is_string($keys) ):
    
      
      // Return true if the single key is set.
      return isset($_SESSION[$keys]);
  
      
    elseif ( is_array($keys) ):
      
      
      // Start from the session array.
      $cur_array = $_SESSION;

      // Traverse the keys.
      foreach ( $keys as $key ):
        
        // If the key exists, move deeper.
        if ( isset($cur_array[$key]) ):
        
          $cur_array = $cur_array[$key];
        
        else:
        
          // Return false if any key is not set.
          return false;
        
        endif;
        
      endforeach;

      // Return true if all keys exist
      return true;
      
      
    endif;

    
    // Return false if neither string nor array was provided.
    return false;
    

  } // key_isset()

  
  
  
  
  
  
  
  /**
   * Delete a given key.
   */
  public static function delete_key( $keys ): void {


    if ( !isset($_SESSION) ):
      
      // Session is not initialized, do nothing.
      return;
    
    endif;

    
    if ( is_string($keys) ):
    
      
      // Unset the single key if it exists.
      unset($_SESSION[$keys]);
  
      
    elseif ( is_array($keys) ):
      
      // Copy the session array to a variable.
      $ref_array = &$_SESSION;
      
      // Traverse array following the keys parameter.
      foreach ( $keys as $i => $key ):

        if ( isset($ref_array[$key]) ):
        

          // If this is the last key in the array of keys 
          // that we are looking for then we are as deep 
          // as we're going to go so unset it.
          if ( $i === array_key_last($keys) ):
          
            unset($ref_array[$key]);
          
          elseif ( is_array($ref_array[$key]) ):
          
            // Move deeper into the array.
            $ref_array = &$ref_array[$key];

          else:

            // Key is not an array, stop here.
            return;
          
          endif;

        
        else:
        
          // Key not found, nothing to delete.
          return;
        
        endif;
        
      endforeach;
      
      
    endif;
    
    
  } // delete_key()


  
  
  
  
  
  
  /**
   * Destroy the session and remove all session data.
   */
  public static function destroy(): void {

    session_unset();
    session_destroy();
  
  } // destroy()
  
  
  
  
  
  
  
  /**
   * Regenerate the session ID while preserving existing session data.
   */
  public static function regenerate(): void {
  
    session_regenerate_id(true);
  
  } // regenerate()


  

    
} // ::Session
