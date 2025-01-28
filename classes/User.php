<?php

/**
 * Functionality specifically tied to user accounts.
 */
class User {
    
    
  private static $instance = null;
  
  private $Db = null;

  private $pdo = null;
  
  
  
  
  
  
  
  
  private function __construct() {
    

    $this->Db = Database::get_instance();

    $this->pdo = $this->Db->get_pdo();
    
    
    // Set crucial session variables
    // for a logged in visitor.
    $this->is_logged_in();
    
    
  } // __construct()
  
  
  
  
  
  
  
  /**
   * Create a new user.
   *
   * Return the ID of the newly created user if the account
   * creation succeeds. Otherwise, return false.
   */
  public function new( array $user_data ): int|false {
    
    
    $result = false;
    
    // If no admin account exists, assume this is the
    // first user and make it admin. Otherwise, default
    // to a user role.
    $user_role = ( !$this->Db->row_exists('Users', 'role', 'admin') ) ? 'admin' : 'user';    
    
    $verify_key = $this->Db->get_unique_column_val('Users', 'verify_key', ['min_len' => 8]);
    
    $selector = $this->Db->get_unique_column_val('Users', 'selector');
    
    
    try {
      
      
      // Hash the password before storing it
      $hashed_pass = password_hash($user_data['password'], PASSWORD_DEFAULT);
  
      // Prepare the SQL statement
      $query = 'INSERT INTO Users (`email`, `password`, `selector`, `role`, `verify_key`) 
                VALUES (:email, :password, :selector, :role, :verify_key)';
        
      $stmt = $this->pdo->prepare( $query );
  
      $stmt->bindValue(':email', $user_data['email'], PDO::PARAM_STR);
      $stmt->bindValue(':password', $hashed_pass, PDO::PARAM_STR);
      $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
      $stmt->bindValue(':role', $user_role, PDO::PARAM_STR);
      $stmt->bindValue(':verify_key', $verify_key, PDO::PARAM_STR);
  
      
      if ( $stmt->execute() ):
        
        $result = $this->pdo->lastInsertId();
        
      else:
        
        $result = false;
        
      endif;
      
    
    } catch (PDOException $e) {
    
      debug_log('New User creation failed: ' . $e->getMessage());
      
      $result = false;
      
    }
    
    
    return $result;
    
    
  } // new()
  
  
  
  
  
  
  
  
  /**
   * Get a user row by it's ID.
   */
  public function get( int $user_id ): array|false {

    return $this->Db->get_row_by_id('Users', $user_id);

  } // get()

  
  
  
  
  
  
  
  /**
   * Get a user row by certain allowed keys.
   * 
   * @todo Audit code for any get_by() calls using id as key.
   *       There ought to be a good reason to use this method
   *       as opposed to get(). If there are none, remove id
   *       from valid_keys.
   */
  public function get_by(string $key, $value): array|false {
    
    
    $valid_keys = [
      'id',
      'email',
      'selector',
      'remember_me',
      'verify_key'
    ];
    
    $key = ( in_array($key, $valid_keys) ) ? $key : 'id';
    
    
    if ( $key == 'remember_me' ):

      $query = 'SELECT * 
                FROM `Users` 
                WHERE EXISTS ( 
                  SELECT 1 
                  FROM json_each(`remember_me`) 
                  WHERE json_valid(`remember_me`)
                   AND json_each.value->>"$.token" = :value 
                   AND date(json_each.value->>"$.created_at") >= date("now", "-30 days")
      )';

    else:

      $query = "SELECT * FROM Users WHERE `{$key}` = :value";

    endif;
    
    
    $stmt = $this->pdo->prepare($query);

    $param_type = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    
    $stmt->bindValue(':value', $value, $param_type);
    
    
    $stmt->execute();
    
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
    
    
  } // get_by()
  
  
  
  
  
  
  
  
  /**
   * Private function to get a single user column.
   */
  private function get_column(string $column, int $user_id): mixed {
    
    
    return $this->Db->get_column('Users', $column, $user_id);
    
    
  } // get_column()

  
  
  
  
  
  
  
  /**
   * Private function to set a single user column.
   */
  private function set_column(string $column, $value, int $user_id): bool {
    
    
    return $this->Db->set_column('Users', $column, $value, $user_id);
    
    
  } // set_column()
  
  
  
  
  
  
  
  
  /**
   * Mark a user account as having a verified email address.
   *
   * @internal instead of doing separate db calls, think
   *            of a nice way to do this with a transaction.
   *            Add a flag to set_column to allow for this.
   */
  public function verify( int $user_id ): bool {
    
    
    $remove_verifiy_key = $this->set_column('verify_key', null, $user_id);
    
    $set_verified = $this->set_column('is_verified', 1, $user_id);
    
    $set_updated_at = $this->set_column('updated_at', date('Y-m-d H:i:s'), $user_id);
    
    
    $this->remove_lockout($user_id);
    
    
    return ($remove_verifiy_key && $set_verified && $set_updated_at);
    
    
  } // verify()
  
  
  
  
  
  
  
  
  /**
   * Determine if a user has verified their email address.
   *
   * If a User ID is passed check the `is_verified` column
   * for that user id in the database. Otherwise, check in the 
   * the current user's session. If the role is anything 
   * other than 'null' then assume they are verified.
   */
  public function is_verified( $user_id = false ): bool {


    $return = false;


    if ( is_int($user_id) ):

      $return = (bool) $this->get_column('is_verified', $user_id);

    else:

      // @todo Test whether it works the same to remove $user_id
      // @todo get_role() could return false if the role key isn't set
      //       in this case we shouldn't assume the user is verified.
      $return = ( $this->get_role($user_id) !== 'null' );

    endif;
    

    return $return;
    

  } // is_verified()

  
  
  
  
  
  
  
  /**
   * Get the role of a user either by user id in the db, or from the session.
   */
  public function get_role( $user_id = false ): string|false {

    
    $return = false;


    if ( is_int($user_id) ):

      $return = $this->get_column('role', $user_id);

    else:

      $return = ( Session::key_isset(['user', 'role']) ) ? Session::get_key(['user', 'role']) : false;

    endif;


    return $return;
    

  } // get_role()

  
  
  
  
  
  
  
  /**
   * Update the last login time for the user.
   *
   * @param int $value User ID or selector.
   * @param string $key
   * 
   * @return void
   */
  public function update_last_login(int $value, string $key = 'id'): void {
    
    
    $valid_keys = [
      'id',
      'selector'
    ];
    
    $key = ( in_array($key, $valid_keys) ) ? $key : 'id';
    
    
    // @internal when/if mariadb support is added
    // this can be switched to datetime('now') with
    // a db type check.
    $current_time = date('Y-m-d H:i:s');
    
    
    $query = "UPDATE `Users` SET `last_login` = :current_time WHERE `{$key}` = :value";
    
    
    $stmt = $this->pdo->prepare($query);
    
    $stmt->bindValue(':value', $value, PDO::PARAM_STR);
    $stmt->bindValue(':current_time', $current_time);
    
    $stmt->execute();
    

  } // update_last_login()
  
  
  
  
  
  
  
  
  /**
   * Does the given user exist?
   */
  public function user_exists( $id_or_email ): bool {
    
    
    $user_key = null;
    
    
    // Check if the input is a valid integer
    if ( is_numeric($id_or_email) && intval($id_or_email) == $id_or_email ):
    
      $user_key = intval($id_or_email);
      
    endif;    
    

    // Check if the input is a valid email address
    // @todo this check will always run even if we already know the key
    // is an int.. don't do this.
    if ( filter_var($id_or_email, FILTER_VALIDATE_EMAIL) ):
      
      $user_key = trim($id_or_email);
        
    endif;  
    
    
    if ( is_null($user_key) ):
    
      return false;
      
    else:
      
      $user_key_type = ( is_int($user_key) ) ? 'id' : 'email';
      
      return $this->Db->row_exists('Users', $user_key_type, $user_key);
      
    endif;
    
     
  } // user_exists()
  
  
  
  
  
  
  
  
  /**
   * Is the current user logged in?
   */
  public function is_logged_in(): bool {
    
    
    $return = false;
    
    
    if ( Session::get_key(['user', 'id']) ):
      
      $this->update_last_active();

      Page::remove_expired_nonces();
      
      $return = true;
      
    elseif ( $token = Cookie::get('remember_me') ):
      
      
      $hashed_token = hash('sha256', $token);
      
      $user_to_check = $this->get_by('remember_me', $hashed_token);
      
      
      // We found a user with a matching remember_me token.
      // Set session variables for use throughout the page
      // load and update the last_active db column.
      if ( $user_to_check ):
        
        Session::set_key(['user', 'id'], $user_to_check['id']);
        Session::set_key(['user', 'selector'], $user_to_check['selector']);
        Session::set_key(['user', 'role'], $user_to_check['role']);
        
        $this->update_last_active();
        
        $return = true;
        
      endif;
      
      
    endif;

    
    return $return;
    
    
  } // is_logged_in()
  
  
  
  
  
  
  
  
  /**
   * Is the current user an admin user?
   *
   * This will govern which routes a user is able to visit
   * and what functionality they have access to.
   *
   * @internal Is this thorough enough?
   */
  public function is_admin(): bool {

    return ( $this->is_logged_in() && ($this->get_role() == 'admin') );

  } // is_admin()
  
  
  
  
  
  
  
  
  /**
   * Determine whether a string is formatted as a valid
   * password.
   *
   * @todo Flesh this function out
   */
  public function validate_pass( string $password ): bool {
    
    
    return ( strlen($password) >= 4 );
    
    
  } // validate_pass()
  
  
  
  
  
  
  
  
  /**
   * Update the password for the given user ID.
   */
  public function update_password( int $user_id, string $password ): bool {

    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

    return $this->set_column('password', $hashed_pass, $user_id);

  } // update_password()
  
  
  
  
  
  
  
  
  /**
   * Update the last_active column for a given user.
   *
   * If time_str is null the current time will be used.
   */
  public function update_last_active( ?int $user_id = 0, ?string $time_str = null ): bool {
    
    
    $user_id = ( $user_id ) ? $user_id : Session::get_key(['user', 'id']);
      
    $last_active = ( $time_str ) ? $time_str : date('Y-m-d H:i:s');
    
    
    return $this->set_column('last_active', $last_active, $user_id);
    
    
  } // update_last_active()
  
  
  
  
  
  
  
  
  /**
   * Set a remember_me token for the given user.
   *
   * The token is stored in a cookie in the user's browser.
   * We store a hashed version of the token in the database.
   *
   * To avoid overwriting remember_me tokens when using multiple
   * browers, we store tokens in a JSON array in the database
   * with a distinct creation time used to determining expiration.
   */
  public function set_remember_me( int $user_id, string $token ): bool {
    
    
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

  
  
  
  
  
  
  
  /**
   * Remove expired and incorrectly formated remember_me tokens
   * from an array of existing tokens.
   */
  private function clean_remember_me_tokens( $tokens ): array {


    if ( Utils::is_valid_json($tokens) ):

      $tokens = json_decode($tokens, true);

    else:

      $tokens = [];

    endif;


    $valid_tokens = [];


    if ( !empty($tokens) ):


      $now = new DateTime('now', new DateTimeZone('UTC'));

      // Loop through the tokens array adding valid
      // tokens to the valid_tokens array. Skipped
      // tokens will effectively be removed.
      foreach ($tokens as $token_data):
        

        // Check that both 'token' and 'created_at' keys exist.
        if ( !isset($token_data['token'], $token_data['created_at']) ):
            continue;
        endif;

        // Check if 'created_at' is a valid date.
        // @todo use Utils::is_valid_datetime() instead.
        $created_at = DateTime::createFromFormat('Y-m-d H:i:s', $token_data['created_at']);

        // Skip if 'created_at' is not valid.
        if ( !$created_at ):
            continue; 
        endif;

        // Get difference between the current date and 'created_at'.
        $interval = $now->diff($created_at);

        // Skip tokens older than 30 days.
        if ( $interval->days > 30 ):
            continue; 
        endif;
        

        // If everything looks good add this token to the valid tokens array.
        $valid_tokens[] = [
            'token' => $token_data['token'],
            'created_at' => $token_data['created_at']
        ];
        

      endforeach;


    endif;


    // Return formatted JSON string as an array
    return $valid_tokens;


  } // clean_remember_me_tokens()

  
  
  
  
  
  
  
  /**
   * Delete the given remember_me token for the given user.
   */
  public function delete_remember_me_token( int $user_id, string $token_to_remove ): bool {


    $existing_tokens = $this->get_column('remember_me', $user_id);

    $clean_tokens = $this->clean_remember_me_tokens( $existing_tokens );
    
    $tokens_modified = false;


    if ( !empty($clean_tokens) ):
      
      // Filter the given token out of the existing tokens
      // that this user has stored in the database.
      $filtered_tokens = array_filter($clean_tokens, function ($t) use ($token_to_remove) {
  
        $hashed_token = hash('sha256',  $token_to_remove);
  
        return $t['token'] !== $hashed_token;
  
      });
      
  
      $updated_tokens = json_encode(array_values($filtered_tokens));
      
      $new_tokens = $this->set_column('remember_me', $updated_tokens, $user_id);
      
    endif;
    
    
    return $new_tokens;


  } // delete_remember_me_token()

  
  
  
  
  
  
  
  /**
   * Create and set a new password reset token for the given user.
   *
   * Password reset tokens last for a set period of time so we store
   * both the token and the expiration.
   *
   * @todo Switch to created_at instead of expiration for consistency.
   * @todo use $return for return value and use only one return.
   * @internal Should this be JSON in a single column?
   */
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
      $new_reset_token = $this->Db->get_unique_column_val('Users', 'password_reset_token', ['min_len' => 16]);


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

  
  
  
  
  
  
  
  /**
   * Check whether a given token exists and hasn't expired.
   *
   * @return User ID for user matching this reset token if not expired.
   *         If no user matches, return false.
   */
  public function check_password_reset_token( string $token ): int|false {

    $stmt = $this->pdo->prepare('SELECT `id`
                                FROM `Users`
                                WHERE `password_reset_token` = :token
                                AND `password_reset_expires` >= :now
                                LIMIT 1');
                                      

    $stmt->execute([
      ':token' => $token,
      ':now' => date('Y-m-d H:i:s')
    ]);


    // Fetch the result (just the 'id')
    return $stmt->fetchColumn();

  } // check_password_reset_token()

  
  
  
  
  
  
  
  /**
   * Increment the failed_login_attempts column for the given user.
   */
  function increment_failed_login(array $user, ?bool $extend_lockout = true): bool {
    

    $user_id = $user['id'];
    
    $failed_login_attempts = (int) $user['failed_login_attempts'];
    

    if ( $failed_login_attempts <= 50 ):

      $failed_login_attempts++;

      // Extend the lockout period but do not increment
      // the failed lockout count.
      if ( $extend_lockout ):
        
        $this->extend_lockout($user, false);
        
      endif;
      

      $stmt = $this->pdo->prepare('
        UPDATE `Users` 
        SET `failed_login_attempts` = :failed_login_attempts
        WHERE `id` = :id
      ');


      $stmt->bindValue(':failed_login_attempts', $failed_login_attempts, PDO::PARAM_INT);
      $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);


      return $stmt->execute();

    else:

      return false;

    endif;

  } // increment_failed_login()

  
  
  
  
  
  
  
  /**
   * Extend the locked_until timestamp for a given user.
   * 
   * The length of the extension of the lockout depends on
   * the number of failed login attempts.
   *
   * @todo Review everywhere `locked_until` is set.
   *       As it is a malformed value will cause problems.
   *       Consider better validation.
   * @todo Consider using max() for all extensions other than
   *       cases where locked_until is null. What if we allow
   *       admins to lock an account for an extended period..
   *       We don't want a failed login attempt to reduce the lockout.
   */
  function extend_lockout(array $user, ?bool $increment = true): string|false {
    

    $user_id = $user['id'];
    $failed_login_attempts = (int) $user['failed_login_attempts'];
    $locked_until = $user['locked_until'] ? new DateTime($user['locked_until']) : null;
    $now = new DateTime();
  
    
    if ( $increment ):
      
      $this->increment_failed_login($user, false);
      
    endif;
    
    
    if ( $failed_login_attempts < 5 ):
      
      return false;
      
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
    
  
    $stmt = $this->pdo->prepare('
      UPDATE `Users` 
      SET `locked_until` = :locked_until 
      WHERE `id` = :id
    ');


    $stmt->bindValue(':locked_until', $new_locked_until, PDO::PARAM_STR);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
  
    $stmt->execute();
    
    return $new_locked_until;

  } // extend_lockout()
  
  
  
  
  
  
  
  
  /**
   * Remove the lockout from a given user.
   *
   * Conditionally remove only the locked_until timestamp, or
   * the failed_login_attempts. Defaults to removing both.
   */
  function remove_lockout(array|int $user, ?string $mode = 'all'): void {
    

    $user_id = is_array($user) ? (int) $user['id'] : $user;

  
    if ( $mode == 'lockout-only' ):
      
      $query = 'UPDATE `Users` SET `locked_until` = NULL WHERE `id` = :id';
      
    elseif ( $mode == 'attempts-only' ):
      
      $query = 'UPDATE `Users` SET `failed_login_attempts` = 0 WHERE `id` = :id';
      
    elseif ( $mode == 'all' ):
      
      $query = 'UPDATE `Users`
                SET `failed_login_attempts` = 0,
                    `locked_until` = NULL
                WHERE `id` = :id';
      
    else:
      
      return;
      
    endif;
    
    
    $stmt = $this->pdo->prepare($query);
    
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    
  
    $stmt->execute();

    
  } // remove_lockout()  

  
  
  
  
  
  
  
  /**
   * Create the database tables needed for users.
   */
  public static function make_tables( $pdo ): bool {
    
  
    try {
      
      // Optionally, create tables or perform other setup tasks here
      $result = $pdo->exec('
         CREATE TABLE IF NOT EXISTS `Users` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `selector` VARCHAR(16) UNIQUE,
          `email` VARCHAR(255) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `display_name` VARCHAR(128),
          `remember_me` JSON DEFAULT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `last_login` DATETIME,
          `last_active` DATETIME,
          `is_active` BOOLEAN DEFAULT 1,
          `is_verified` BOOLEAN DEFAULT 0,
          `verify_key` VARCHAR(16) UNIQUE,
          `password_reset_token` VARCHAR(64),
          `password_reset_expires` DATETIME,
          `failed_login_attempts` INTEGER DEFAULT 0,
          `locked_until` DATETIME,
          `role` TEXT DEFAULT "user",
          CHECK (`role` IN ("user", "author", "admin"))
        )
      ');
      
      
      return $result;
      
    } catch (PDOException $e) {
    
      echo "Error: " . $e->getMessage();
      
      return false;
      
    }
    

  } // make_tables()  
  
  
  
  
  
  
  
  
  /**
   * Return an instance of this class.
   */
  public static function get_instance(): self {
    
    if ( is_null(self::$instance) ):
      
      self::$instance = new self();
      
    endif;
    
    return self::$instance;
    
  } // get_instance()

  
    
} // ::User