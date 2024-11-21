<?php

class User {
    
    
  private static $instance = null;
  
  private $db = null;
  
  
  
  
  private function __construct() {
    

    $this->db = Db::get_instance();

    
  } // __construct()
  
  
  
  
  
  
  
  
  public function new( array $user_data ): int|false {
    
    
    $result = false;
    
    
    
    $db_conn = $this->db->get_conn();
    
    
    // If th
    $user_role = ( !$this->db->row_exists('Users', 'role', 'admin') ) ? 'admin' : 'user';
    
    
    // @todo this needs to be unique
    $verify_key = $this->get_unique_column_val('verify_key', ['min_len' => 8]);
    
    
    // @todo this needs to be unique
    $selector = $this->get_unique_column_val('selector');
    
    
    
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
  
 







  public function get( int $user_id ) {

    return $this->db->get_row_by_id('Users', $user_id);

  } // get()






  
  
  
  
  
  /**
   * 
   */
  public function get_by(string $key, $value) {

    
    $db_conn = $this->db->get_conn();
    
    
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
  private function get_column(string $column, int $user_id) {
    
    
    return $this->db->get_column('Users', $column, $user_id);
    
    
  } // get_column()




  
  
  
  
  
  /**
   * 
   */
  private function set_column(string $column, $value, int $user_id): bool {
    
    
    return $this->db->set_column('Users', $column, $value, $user_id);
    
    
  } // set_column()
  
  
  
  
  
  
  
  
  
  /**
   * @internal instead of doing separate db calls, think
   *            of a nice way to do this with a transaction.
   *            Add a flag to set_column to allow for this.
   */
  public function verify(int $user_id ): bool {
    
    
    $remove_verifiy_key = $this->set_column('verify_key', null, $user_id);
    
    $set_verified = $this->set_column('is_verified', 1, $user_id);
    
    // @todo Reset failed login attempts
    // and locked_until, and set updated_at.
    
    
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
    
    
    $db_conn = $this->db->get_conn();
    
    
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
      
      $user_key_type = ( is_int($user_key) ) ? 'id' : 'email';
      
      return $this->db->row_exists('Users', $user_key_type, $user_key);
      
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

    $existing_tokens = $this->get_column('remember_me', $user_id);


    $clean_tokens = $this->clean_remember_me_tokens( $existing_tokens );


    $clean_tokens[] = $new_token;

    $clean_tokens = json_encode($clean_tokens);

    $new_col = $this->set_column('remember_me', $clean_tokens, $user_id);


    return $new_col;
    
    
  } // set_remember_me()
  
  







  public function update_last_active( ?int $user_id = 0, ?string $time_str = null ): bool {
    
    
    $user_id = ( $user_id ) ? $user_id : Session::get_key(['user', 'id']);
      
    $last_active = ( $time_str ) ? $time_str : date('Y-m-d H:i:s');
    
    
    return $this->set_column('last_active', $last_active, $user_id);
    
    
  } // update_last_active()
  
  





  /**
   * 
   */
  public function update_password( int $user_id, string $password ): bool {

    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

    return $this->set_column('password', $hashed_pass, $user_id);

  } // update_password()










  public function delete_remember_me_token( int $user_id, string $token_to_remove ): bool {


    $existing_tokens = $this->get_column('remember_me', $user_id);

    $clean_tokens = $this->clean_remember_me_tokens( $existing_tokens );


    // Filter out the token to remove
    $filtered_tokens = array_filter($clean_tokens, function ($t) use ($token_to_remove) {

      $hashed_token = hash('sha256',  $token_to_remove);

      return $t['token'] !== $hashed_token;

    });
    

    $updated_tokens = json_encode(array_values($filtered_tokens));
    
    return $this->set_column('remember_me', $updated_tokens, $user_id);


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







  public function set_password_reset_token( int $user_id ): string|false {


    $user_to_reset = $this->get( $user_id );

    // Is this a valid existing User ID?
    if ( is_array($user_to_reset) && isset($user_to_reset['id']) ):


      // Check whether we have an ongoing password reset request
      $reset_started = isset($user_to_reset['password_reset_started']) ?? null;

      if ( Utils::is_valid_datetime($reset_started) ):

        $reset_started_datetime = new DateTime($reset_started);
        $now = new DateTime();
        
        // Create a DateInterval of 30 minutes
        // @todo This should be a setting in config
        $interval = new DateInterval('PT30M');
        
        // Subtract 30 minutes from the current time
        $threshold_time = $now->sub($interval);
        
        // Compare the two DateTime objects
        if ( $created_at_datetime >= $threshold_time ):

          // There has already been a password reset requested
          // too recently, just bail
          return false;

        endif;

      endif;


      // If we made it here we have a valid User, and that user is
      // eligible for a new password reset.
      // @todo make length a config setting
      $new_reset_token = $this->get_unique_column_val('password_reset_token', ['min_len' => 16]);


      $now = date('Y-m-d H:i:s');

      // Create a DateTime object from the current time
      $date = new DateTime($now);
      
      // Add 30 minutes
      $date->modify('+30 minutes');
      
      // Get the updated datetime as a string
      $new_reset_expires = $date->format('Y-m-d H:i:s');

      // @todo We should use a transaction 
      $is_good_token = $this->set_column('password_reset_token', $new_reset_token, $user_id);
    
      $is_good_date = $this->set_column('password_reset_expires', $new_reset_expires, $user_id);

      return ( $is_good_token && $is_good_date );

    else:

      return false;

    endif;


  } // set_password_reset_token()








  public function check_password_reset_token( string $token ): int|false {

    
    $db_conn = $this->db->get_conn();

    $stmt = $db_conn->prepare("SELECT id
                                FROM Users 
                                WHERE password_reset_token = :token
                                AND password_reset_expires >= :now
                                LIMIT 1");
                                      

    $stmt->execute([
      ':token' => $token,
      ':now' => date('Y-m-d H:i:s')
    ]);


    // Fetch the result (just the 'id')
    return $stmt->fetchColumn();

  } // check_password_reset_token()




  
  
  


  function increment_failed_login(array $user): bool {

    $user_id = $user['id'];
    $failed_login_attempts = (int) $user['failed_login_attempts'];
    

    if ( $failed_login_attempts <= 50 ):

      $failed_login_attempts++;

      $db_conn = $this->db->get_conn();

      // Extend the lockout period but do not increment
      // the failed lockout count.
      //
      // @todo Calls to extend lockout run twice. Fix this.
      //
      // @internal I don't like how cyclical this entire lockout
      // process feels. Revisit this to think of a more unified
      // way to handle the process with less fragmentation and 
      // fewer database calls.
      $this->extend_lockout($user, false);
      

      $query = $db_conn->prepare('
        UPDATE `Users` 
        SET `failed_login_attempts` = :failed_login_attempts
        WHERE `id` = :id
      ');


      $query->bindParam(':failed_login_attempts', $failed_login_attempts, PDO::PARAM_INT);
      $query->bindParam(':id', $user_id, PDO::PARAM_INT);

      

      return $query->execute();

    else:

      return false;

    endif;

  } // increment_failed_login()









  function extend_lockout(array $user, ?bool $increment = true): void {
    

    $user_id = $user['id'];
    $failed_login_attempts = (int) $user['failed_login_attempts'];
    $locked_until = $user['locked_until'] ? new DateTime($user['locked_until']) : null;
    $now = new DateTime();

    $db_conn = $this->db->get_conn();
  
    
    if ( $increment ):
      
      $this->increment_failed_login($user);
      
    endif;

  
    if ( is_null($locked_until) ):

      $new_locked_until = $now->add(new DateInterval('PT5M'));
      
    elseif ($failed_login_attempts <= 10 ):

      $new_locked_until = max($now, $locked_until ?: $now)->add(new DateInterval('PT5M'));
  
    elseif ( $failed_login_attempts <= 15 ):

      $new_locked_until = $now->add(new DateInterval('PT30M'));
  
    else:

      $new_locked_until = $now->add(new DateInterval('PT1H'));
  
    endif;
    
    
    $new_locked_until = $new_locked_until->format('Y-m-d H:i:s');
    
  
    $query = $db_conn->prepare('
      UPDATE `Users` 
      SET `locked_until` = :locked_until 
      WHERE `id` = :id
    ');


    $query->bindParam(':locked_until', $new_locked_until, PDO::PARAM_STR);
    $query->bindParam(':id', $user_id, PDO::PARAM_INT);
  
    $query->execute();

  } // extend_lockout()
  








  function remove_lockout(array $user): void {

    $user_id = $user['id'];

    $db_conn = $this->db->get_conn();

  
    $query = $db_conn->prepare('
      UPDATE `Users` 
      SET `locked_until` = NULL 
      WHERE `id` = :id
    ');


    $query->bindParam(':id', $user_id, PDO::PARAM_INT);
  
    $query->execute();

  } // extend_lockout()  









  private function get_unique_column_val(string $column, array $args = []): string|false {

    return $this->db->get_unique_column_val('Users', $column, $args);

  } // get_unique_column_val()



  

  
  
  
  
  public static function make_tables( $db ): bool {
    
  
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $db->exec(
        "CREATE TABLE IF NOT EXISTS Users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          selector VARCHAR(16) UNIQUE,
          email VARCHAR(255) NOT NULL UNIQUE,
          password VARCHAR(255) NOT NULL,
          display_name VARCHAR(128),
          remember_me JSON DEFAULT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          last_login DATETIME,
          last_active DATETIME,
          is_active BOOLEAN DEFAULT 1,
          is_verified BOOLEAN DEFAULT 0,
          verify_key VARCHAR(16) UNIQUE,
          password_reset_token VARCHAR(64),
          password_reset_expires DATETIME,
          failed_login_attempts INTEGER DEFAULT 0,
          locked_until DATETIME,
          role TEXT DEFAULT 'user',
          CHECK (role IN ('user', 'author', 'admin'))
        );"
      );
      
      
      return $result;
      
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage();
      
      return false;
      
    }
    

  } // make_tables()
    
    
    
  
  
  
  
  
    
  
  
  
  
  public static function get_instance() {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::User