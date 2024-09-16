<?php

class User {
    
    
  private static $instance = null;
  
  
  
  
  
  
  private function __construct() {
    
    
    
  } // __construct()
  
  
  
  
  
  
  
  
  public function new( array $user_data ): int|false {
    
    
    $result = false;
    
    $db = Db::get_instance();
    
    $db_conn = $db->get_conn();
    
    
    // If th
    $user_role = ( !$db->row_exists('Users', 'role', 'admin') ) ? 'admin' : 'user';
    
    
    // @todo this needs to be unique
    $verify_key = substr(bin2hex(random_bytes(4)), 0, 8);
    
    // @todo this needs to be unique
    $selector = substr(bin2hex(random_bytes(4)), 0, 6);
    
    
    
    try {
      
      // Hash the password before storing it
      $hashed_pass = password_hash($user_data['password'], PASSWORD_DEFAULT);
  
      // Prepare the SQL statement
      $query = "INSERT INTO Users (email, password, selector, role, verify_key) 
                VALUES (:email, :password, :selector, :role, :verify_key)";
      
      
      $stmt = $db_conn->prepare( $query );
  
      // Bind the parameters
      $stmt->bindParam(':email', $user_data['email'], PDO::PARAM_STR);
      $stmt->bindParam(':password', $hashed_pass, PDO::PARAM_STR);
      $stmt->bindParam(':selector', $selector, PDO::PARAM_STR);
      $stmt->bindParam(':role', $user_role, PDO::PARAM_STR);
      $stmt->bindParam(':verify_key', $verify_key, PDO::PARAM_STR);
  
      // Execute the statement and return the ID of the User
      // we just added, or false if something failed.
      if ( $stmt->execute() ):
        
        $result = $db_conn->lastInsertId();
        
      else:
        
        $result = false;
        
      endif;
      
    
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage() . '<br>';
      
      $result = false;
      
    }
    
    
    
    return $result;
    
  } // new()
  
 
  
  
  
  
  
  /**
   * @internal I think renaming this to get() would be nicer
   */
  public function get_by($value, string $key = 'id') {

    
    $db = Db::get_instance();
    
    $db_conn = $db->get_conn();
    
    
    $valid_keys = [
      'id',
      'email',
      'selector',
      'remember_me',
      'verify_key'
    ];
    
    $key = ( in_array($key, $valid_keys) ) ? $key : 'id';
    
    if ( $key == 'remember_me' ):

      $query = "SELECT * 
        FROM Users 
        WHERE EXISTS ( 
            SELECT 1 
            FROM json_each(remember_me) 
            WHERE json_each.value->>'token' = :value 
               AND date(json_each.value->>'created_at') >= date('now', '-30 days')
        )";

    else:

      $query = "SELECT * FROM Users WHERE `{$key}` = :value";

    endif;
    
    
    $stmt = $db_conn->prepare($query);

    $param_type = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    
    // Bind the parameters
    $stmt->bindParam(':value', $value, $param_type);
    
    
    $stmt->execute();
    
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
    
    
  } // get_by()
  
  






  
  
  
  
  /**
   * Private function to set any single column
   */
  private function get_column(int $user_id, string $column) {
    
    $db = Db::get_instance();
    
    $db_conn = $db->get_conn();
    
    
    $query = "SELECT `{$column}` FROM Users WHERE id = :id";
    
    
    $stmt = $db_conn->prepare($query);
    
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

    $stmt->execute();
    
    return $stmt->fetchColumn();
    
    
  } // get_key()




  
  
  
  
  
  /**
   * Private function to set any single column
   */
  private function set_column(int $user_id, string $column, $value): bool {
    
    $db = Db::get_instance();
    
    $db_conn = $db->get_conn();
    
    
    $query = "UPDATE Users SET `{$column}` = :value WHERE id = :id";
    
    
    $stmt = $db_conn->prepare($query);
    
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':value', $value);
    
    return ( $stmt->execute() ) ? true : false;
    
    
  } // set_key()
  
  
  
  
  
  
  
  
  
  /**
   * @internal instead of doing separate db calls, think
   *            of a nice way to do this with a transaction.
   *            Add a flag to set_column to allow for this.
   */
  public function verify(int $user_id ): bool {
    
    
    $remove_verifiy_key = $this->set_column($user_id, 'verify_key', null);
    
    $set_verified = $this->set_column($user_id, 'is_verified', 1);
    
    // @internal Probably should reset failed login attempts,
    // updated_at, and locked_until.
    
    
    return ($remove_verifiy_key && $set_verified);
    
    
  } // $remove_verify_key()
  
  
  
  
  
  
  
  
  
  /**
   * Update the last login time for the user.
   *
   * @param int $value User ID or selector.
   * @param string $key
   * 
   * @return void
   */
  public function update_last_login(int $value, string $key = 'id'): void {
    
    
    $db = Db::get_instance();
    
    $db_conn = $db->get_conn();
    
    
    $valid_keys = [
      'id',
      'selector'
    ];
    
    $key = ( in_array($key, $valid_keys) ) ? $key : 'id';
    
    
    // @internal when/if mariadb support is added
    // this can be switched to datetime('now') with
    // a db type check.
    $current_time = date('Y-m-d H:i:s');
    
    
    $query = "UPDATE Users SET last_login = :current_time WHERE `{$key}` = :value";
    
    
    $stmt = $db_conn->prepare($query);
    
    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
    $stmt->bindValue(':current_time', $current_time);
    
    $stmt->execute();
    

  } // update_last_login()
  
  
  
    
    
    
  
  
  public function user_exists( $id_or_email ): bool {
    
    $user_key = null;
    
    // Check if the input is a valid integer
    if ( is_numeric($id_or_email) && intval($id_or_email) == $id_or_email ):
    
      $user_key = intval($id_or_email);
      
    endif;
    
    

    // Check if the input is a valid email address
    if ( filter_var($id_or_email, FILTER_VALIDATE_EMAIL) ):
      
      $user_key = trim($id_or_email);
        
    endif;
    
    
    
    if ( is_null($user_key) ):
    
      return false;
      
    else:
      
      $db = Db::get_instance();
      
      $user_key_type = ( is_int($user_key) ) ? 'id' : 'email';
      
      return $db->row_exists('Users', $user_key_type, $user_key);
      
    endif;
    
    
    
  } // user_exists()
  
  
  
  
  
  
  
  
  
  /**
   * @todo Flesh this function out
   */
  public function validate_pass( string $password ) {
    
    
    return ( strlen($password) >= 4 );
    
    
  } // validate_pass()
  
  
  
  
  
  
  
  
  
  public function set_remember_me( int $user_id, string $token ): bool {
    
    
    // Store the hashed version in the database
    // @todo research whether this is secure enough
    $hashed_token = hash('sha256', $token);
    
    $created_at = date('Y-m-d H:i:s');

    $new_token = ['token' => $hashed_token, 'created_at' => $created_at];

    $existing_tokens = $this->get_column($user_id, 'remember_me');


    $clean_tokens = $this->clean_remember_me_tokens( $existing_tokens );


    $clean_tokens[] = $new_token;

    $clean_tokens = json_encode($clean_tokens);

    $new_col = $this->set_column($user_id, 'remember_me', $clean_tokens);


    return $new_col;
    
    
  } // set_remember_me()
  
  







  public function update_last_active( ?int $user_id = 0, ?string $time_str = null ): bool {
    
    
    $user_id = ( $user_id ) ? $user_id : Session::get_key('user_id');
      
    $last_active = ( $time_str ) ? $time_str : date('Y-m-d H:i:s');
    
    
    return $this->set_column($user_id, 'last_active', $last_active);
    
    
  } // update_last_active()
  
  







  public function delete_remember_me_token( int $user_id, string $token_to_remove ): bool {


    $existing_tokens = $this->get_column($user_id, 'remember_me');

    $clean_tokens = $this->clean_remember_me_tokens( $existing_tokens );


    // Filter out the token to remove
    $filtered_tokens = array_filter($clean_tokens, function ($t) use ($token_to_remove) {

      $hashed_token = hash('sha256',  $token_to_remove);

      return $t['token'] !== $hashed_token;

    });
    

    $updated_tokens = json_encode(array_values($filtered_tokens));
    
    return $this->set_column($user_id, 'remember_me', $updated_tokens);


  } // delete_remember_me_token()










  private function clean_remember_me_tokens( $tokens ): array {


    if ( Utils::is_valid_json($tokens) ):

      $tokens = json_decode($tokens, true);

    else:

      $tokens = [];

    endif;



    $valid_tokens = [];



    if ( !empty($tokens) ):


      $now = new DateTime('now', new DateTimeZone('UTC'));

      // Loop through the tokens array
      foreach ($tokens as $token_data):

          // Check if 'token' and 'created_at' keys exist and are properly formatted
          if ( !isset($token_data['token'], $token_data['created_at']) ):
              continue;
          endif;

          // Check if 'created_at' is a valid date
          $created_at = DateTime::createFromFormat('Y-m-d H:i:s', $token_data['created_at']);

          // Skip if 'created_at' is not valid
          if (!$created_at):
              continue; 
          endif;

          // Get difference between the current date and 'created_at'
          $interval = $now->diff($created_at);

          // Skip tokens older than 30 days
          if ($interval->days > 30):
              continue; 
          endif;

          // If everything is valid, add it to the valid tokens array
          $valid_tokens[] = [
              'token' => $token_data['token'],
              'created_at' => $token_data['created_at']
          ];

      endforeach;


    endif;


    // Return formatted JSON string as an array
    return $valid_tokens;


  } // clean_remember_me_tokens()



  

  
  
  
  
  public static function make_tables( $db ): bool {


    
    $result = null;
    
  
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS Users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          selector VARCHAR(16) UNIQUE,
          email VARCHAR(255) NOT NULL UNIQUE,
          password VARCHAR(255) NOT NULL,
          display_name VARCHAR(255),
          remember_me JSON DEFAULT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          last_login DATETIME,
          last_active DATETIME,
          is_active BOOLEAN DEFAULT 1,
          is_verified BOOLEAN DEFAULT 0,
          verify_key VARCHAR(16) UNIQUE,
          failed_login_attempts INTEGER DEFAULT 0,
          locked_until DATETIME,
          role TEXT DEFAULT 'user',
          CHECK (role IN ('user', 'author', 'admin'))
        );"
      );
      
      
      $result = ( $result === false ) ? false : true;
      
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage();
      
      $result = false;
      
    }
    
    
    return $result;
    
  

  } // make_tables()
    
    
    
  
  
  
  
  
    
  
  
  
  
  // Get the singleton instance of the class
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::User