<?php

/**
 * Functionality specifically tied to user accounts.
 */
class User {
    
    
  private static $instance = null;
  
  private $Config = null;

  private $Db = null;

  private $pdo = null;
  
  
  
  
  
  
  
  
  private function __construct() {
    

    $this->Config = Config::get_instance();

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
    
    $login_token = $this->Db->get_unique_column_val('Users', 'login_token');
    
    
    try {
      
      
      // Hash the password before storing it
      $hashed_pass = password_hash($user_data['password'], PASSWORD_DEFAULT);
  
      // Prepare the SQL statement
      $query = 'INSERT INTO Users (`email`, `password`, `selector`, `role`, `verify_key`, `login_token`) 
                VALUES (:email, :password, :selector, :role, :verify_key, :login_token)';
        
      $stmt = $this->pdo->prepare( $query );
  
      $stmt->bindValue(':email', $user_data['email'], PDO::PARAM_STR);
      $stmt->bindValue(':password', $hashed_pass, PDO::PARAM_STR);
      $stmt->bindValue(':selector', $selector, PDO::PARAM_STR);
      $stmt->bindValue(':role', $user_role, PDO::PARAM_STR);
      $stmt->bindValue(':verify_key', $verify_key, PDO::PARAM_STR);
      $stmt->bindValue(':login_token', $login_token, PDO::PARAM_STR);
  
      
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
  public function get( int $user_id, array $args = [] ): array|false {
    
    $defaults = [
      'fields' => 'safe'
    ];

    $args = array_merge($defaults, $args);
    
    $user = $this->Db->get_row_by_id('Users', $user_id);
    
    $user = $this->sanitize_results($user, $args['fields']);

    return $user;

  } // get()
  
  
  
  
  
  
  
  
  /**
   * Update a User.
   *
   * @todo Finish this.
   */
  public function update( int $user_id, array $user_data ): array|false {
    
    // Define columns that should never be updated
    $protected_columns = [
        'id',
        'selector',
        'created_at'
    ];

    // Fetch column names from the Users table
    $stmt = $this->pdo->query("PRAGMA table_info(Users)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    // Filter out invalid or protected columns
    $editable_columns = array_diff($columns, $protected_columns);
    $valid_updates = array_intersect_key($user_data, array_flip($editable_columns));

    // If there are no valid updates, return false
    if ( empty($valid_updates) ):
      
      return false;
      
    endif;

    // Generate SQL for updating the post
    // @todo Change this to $columns_to_update?
    $set_clauses = [];
    
    foreach ($valid_updates as $column => $value):
      
      $set_clauses[] = "`$column` = :$column";
      
    endforeach;

    // Always update the `updated_at` column
    // @todo Should updated_at be a protected column?
    $set_clauses[] = "`updated_at` = CURRENT_TIMESTAMP";

    $query = "UPDATE `Users` SET " . implode(", ", $set_clauses) . " WHERE `id` = :user_id";
    
    
    $stmt = $this->pdo->prepare($query);
    

    // Bind valid parameters, casting as either INT or STR.
    foreach ($valid_updates as $column => &$value):
      
      $param_type = ( is_numeric($value) && ctype_digit(strval($value)) ) ? PDO::PARAM_INT : PDO::PARAM_STR;
      
      $stmt->bindValue(":$column", $value, $param_type);
      
    endforeach;
    

    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);


    if ( !$stmt->execute() ):
      
      return false;
      
    endif;
    

    return $this->get($user_id);
    
    
  } // update()








  /**
   * Delete a User row by it's ID.
   */
  public function delete( int $user_id ): bool {

    return $this->Db->delete_row('Users', $user_id);

  } // delete()

  
  
  
  
  
  
  
  /**
   * Get a user row by certain allowed keys.
   */
  public function get_by( string $key, $value, array $args = [] ): array|false {
    
    
    $defaults = [
      'fields' => 'safe'
    ];

    $args = array_merge($defaults, $args);
    
    
    $valid_keys = [
      'id',
      'email',
      'selector',
      'remember_me',
      'verify_key'
    ];
    
    $key = ( in_array($key, $valid_keys) ) ? $key : 'id';
    
    
    if ( $key == 'id'):

      return $this->get($value, $args);

    elseif ( $key == 'remember_me' ):

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
    
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $user = $this->sanitize_results($user, $args['fields']);
    
    
    return $user;
    
    
  } // get_by()
  
  
  
  
  
  
  
  
  /**
   * 
   * @todo Expand fields arg to accept an array of columns, or a single field name
   */
  function get_users( array $args = [] ): array|false {
    
    
    $defaults = [
      'is_verified' => true,
      'is_locked_out' => false,
      'is_banned' => false,
      'fields' => 'safe',
      'limit' => 10, // @todo This should be a setting
      'offset' => 0
    ];

    $args = array_merge($defaults, $args);
    
    
    if ( $args['is_locked_out'] ):
      
      // @todo Verify that this actually works
      $locked_cond = 'AND `locked_until` IS NULL OR `locked_until` < DATETIME("now")';
      
    else:
      
      $locked_cond = '';
      
    endif;


    $query = "SELECT * FROM `Users` 
              WHERE `is_verified` = :is_verified
                AND `is_banned` = :is_banned
                {$locked_cond}
              ORDER BY `created_at` 
              ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $this->pdo->prepare($query);

    
    $stmt->bindParam(':is_verified', $args['is_verified'], PDO::PARAM_BOOL);
    $stmt->bindParam(':is_banned', $args['is_banned'], PDO::PARAM_BOOL);
    $stmt->bindParam(':limit', $args['limit'], PDO::PARAM_INT);
    $stmt->bindParam(':offset', $args['offset'], PDO::PARAM_INT);

    $stmt->execute();


    // Fetch Users
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $users = $this->sanitize_results($users, $args['fields']);
    
    
    return $users;

  } // get_users()
  
  
  
  
  
  
  
  
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
   *
   */
  public function get_display_name( int|string $key): string {
    
    
    if ( is_int($key) ):
      
      $user = $this->get($key);
        
    else:
      
      $user = $this->get_by('selector', $key);
      
    endif;

    
    if ( is_array($user) && !empty($user) ):
      
      // @todo 'User @' should be a config setting
      return !empty($user['display_name']) ? $user['display_name'] : '@' . $user['selector'];
      
    endif;
    

    return 'BAD USER';
      
  } // get_display_name()

  
  
  
  
  
  
  
  
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

      $user_role = $this->get_role();

      $return = ( $user_role && ($user_role !== 'null') );

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
  public function user_exists( int|string $id_or_email ): bool {
    
    
    $user_key = null;

    $user_key_type = null;
    
    
    // Check if the input is a valid integer
    if ( is_numeric($id_or_email) && intval($id_or_email) == $id_or_email ):
    
      $user_key = intval($id_or_email);

      $user_key_type = 'id';
      
    // Check if the input is a valid email address
    elseif ( filter_var($id_or_email, FILTER_VALIDATE_EMAIL) ):
      
      $user_key = trim($id_or_email);

      $user_key_type = 'email';
        
    endif;  
    
    
    if ( is_null($user_key) ):
    
      return false;
      
    else:
      
      return $this->Db->row_exists('Users', $user_key_type, $user_key);
      
    endif;
    
     
  } // user_exists()
  
  
  
  
  
  
  
  
  /**
   * Is the current user logged in?
   */
  public function is_logged_in(): bool {
    
    
    $return = false;
    
    $user_id = null;
    
    
    if ( Session::get_key(['user', 'id']) ):
      
      $this->update_last_active();

      Auth::remove_expired_nonces();
      
      $user_id = Session::get_key(['user', 'id']);
      
      $return = true;
      
    elseif ( $token = Cookie::get('remember_me') ):
      
      debug_log("Found no user id but did find a remember me token: {$token}.");
      
      $hashed_token = hash('sha256', $token);
      
      $user_to_check = $this->get_by('remember_me', $hashed_token, ['fields' => 'all']);
      
      
      // We found a user with a matching remember_me token.
      // Set session variables for use throughout the page
      // load and update the last_active db column.
      if ( $user_to_check ):

        debug_log("The token matched user id: {$user_to_check['id']}.");
        
        Session::set_key(['user', 'id'], $user_to_check['id']);
        Session::set_key(['user', 'selector'], $user_to_check['selector']);
        Session::set_key(['user', 'role'], $user_to_check['role']);
        Session::set_key(['user', 'login_token'], $user_to_check['login_token']);
        
        $this->update_last_active();
      
        $user_id = $user_to_check['id'];
        
        $return = true;

      else:

        debug_log("The token did not match any user.");
        
      endif;
      
      
    endif;
    
    
    // Check whether the login_token column matches the token in
    // the session for the current user.
    // If it doesn't, clear all session and database rows related to 
    // a logged in user and return false.
    if ( is_int($user_id) && $this->user_exists($user_id) ):
      
      $login_token_db = $this->get_column('login_token', $user_id);
      $login_token_session = Session::get_key(['user', 'login_token']);
      
      if ( $login_token_db !== $login_token_session ):

        debug_log("The login token in the session ({$login_token_session}) didn't match the DB ({$login_token_db}).");
        
        $this->delete_remember_me($user_id);
        
        Session::destroy();
        Cookie::delete('remember_me');
        
        $return = false;
        
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
   * @todo Add user_id param to test a given user.
   *
   * @internal Is this thorough enough?
   */
  public function is_admin(): bool {

    return ( $this->is_logged_in() && ($this->get_role() == 'admin') );

  } // is_admin()








  /**
   * Is the current user an author user?
   *
   * This will govern which routes a user is able to visit
   * and what functionality they have access to.
   *
   * @todo Add user_id param to test a given user.
   *
   * @internal Is this thorough enough?
   */
  public function is_author(): bool {

    return ( $this->is_logged_in() && ($this->get_role() == 'author') );

  } // is_author()
  
  
  
  
  
  
  
  
  /**
   * Determine whether a string is formatted as a valid
   * password.
   *
   * @todo Flesh this function out
   */
  public function validate_pass( string $password ): bool {
    
    // Minimum length requirement
    if ( strlen($password) < $this->Config->get('password_min_length') ):

      return false;

    endif;

    // Check against common passwords (optional: integrate with a leaked password API)
    $common_passwords = [
      'password', '123456', 'qwerty', 'letmein', 'admin', 'welcome', 'monkey', 
      '12345678', 'abc123', 'password1'
    ];

    if ( in_array(strtolower($password), $common_passwords, true) ):

      return false;
    
    endif;


    return true;
    
    
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
    
    
    // @internal This could be more secure by using password_hash()
    // but it makes it difficult to delete a given token from the database
    // as password_hash() will generate a different string each time it's run.
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
        // @todo Test assigning a timezone with this:
        // $created_at = DateTime::createFromFormat('Y-m-d H:i:s', $token_data['created_at'], new DateTimeZone('UTC'));
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
   *
   * @todo I think I could merge this function with delete_remember_me()
   */
  public function delete_remember_me_token( int $user_id, string $token_to_remove ): bool {

    
    if ( !$this->user_exists($user_id) ):
      
      return false;
      
    endif;
    

    $existing_tokens = $this->get_column('remember_me', $user_id);

    $clean_tokens = $this->clean_remember_me_tokens( $existing_tokens );
    
    $new_tokens = null;


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
   * Reset the entire remember_me column for the given user.
   */
  public function delete_remember_me( int $user_id ): bool {

    
    if ( !$this->user_exists($user_id) ):
      
      return false;
      
    else:
      
      return $this->set_column('remember_me', null, $user_id);
      
    endif;
    
    
  } // delete_remember_me()

  
  
  
  
  
  
  
  /**
   * Change the login_token column for the given user.
   *
   * The login_token value will be stored in the user's session.
   *
   * When this column doesn't match the value stored in the session
   * value for a visitor it will force a user to log in again.
   *
   * @internal Always set the remember_me column to null in addition
   * to changing the login_token if the intent is to boot a user.
   *
   * @internal If a user has a valid remember_me cookie but not an
   * active session when they visit, the is_logged_in() function will
   * rebuild their session with the current login_token, rendering
   * this function useless to force them to log in again.
   */
  public function reset_login_token( int $user_id ): bool {

    
    if ( !$this->user_exists($user_id) ):
      
      return false;
      
    else:
      
      $new_login_token = $this->Db->get_unique_column_val('Users', 'login_token');
      
      return $this->set_column('login_token', $new_login_token, $user_id);
      
    endif;
    
    
  } // reset_login_token()
  
  
  
  
  
  
  
  
  /**
   * Create and set a new password reset token for the given user.
   *
   * Password reset tokens last for a set period of time so we store
   * both the token and the expiration.
   *
   * @internal Should this be JSON in a single column?
   */
  public function set_password_reset_token( int $user_id ): string|false {

    $now = new DateTime('now', new DateTimeZone('UTC'));

    $user_to_reset = $this->get( $user_id );

    $password_reset_age = $this->Config->get('password_reset_age');
    

    // Is this a valid existing User ID?
    if ( is_array($user_to_reset) && isset($user_to_reset['id']) ):


      // Check whether we have an ongoing password reset request
      $reset_started = isset($user_to_reset['password_reset_started']) ?? null;


      if ( Utils::is_valid_datetime($reset_started) ):

        $reset_started_datetime = new DateTime($reset_started, new DateTimeZone('UTC'));
        
        // Add 30 minutes to the current time
        $threshold_time = $now->modify("+{$password_reset_age} minutes");
        
        // Compare the two DateTime objects
        // @todo Use debug_log to double-check my logic here.
        if ( $reset_started_datetime <= $threshold_time ):

          // There has already been a password reset requested
          // too recently, just bail
          return false;

        endif;

      endif;


      // If we made it here we have a valid User, and that user is
      // eligible for a new password reset.
      $new_reset_token = $this->Db->get_unique_column_val('Users', 'password_reset_token', ['min_len' => 16]);

      $reset_started_str = Utils::format_date($now, 'Y-m-d H:i:s');


      $is_good_token = $this->set_column('password_reset_token', $new_reset_token, $user_id);
    
      $is_good_date = $this->set_column('password_reset_started', $reset_started_str, $user_id);

      
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
                                AND `password_reset_started` <= :token_expires
                                LIMIT 1');

                            
    $now = new DateTime('now', new DateTimeZone('UTC'));

    $password_reset_age = $this->Config->get('password_reset_age');

    $token_expires_datetime = $now->modify("+{$password_reset_age} minutes");

    $token_expires = Utils::format_date($token_expires_datetime, 'Y-m-d H:i:s');

    $stmt->execute([
      ':token' => $token,
      ':token_expires' => $token_expires
    ]);


    // Fetch the result (just the 'id')
    return $stmt->fetchColumn();


  } // check_password_reset_token()

  






  /**
   * 
   */
  function clear_password_reset( int $user_id ): bool {

    $return = false;

    if ( $this->user_exists($user_id) ):

      $reset_token = $this->set_column('password_reset_token', null, $user_id);

      $reset_timestamp = $this->set_column('password_reset_started', null, $user_id);

      $return = ($reset_token && $reset_timestamp);

    endif;
    
    return $return;

  } // clear_password_reset()
  
  
  
  
  
  
  
  
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
   * @todo Test this thoroughly.
   */
  function extend_lockout(array $user, ?bool $increment = true): string|false {
    

    $user_id = $user['id'];
    $failed_login_attempts = (int) $user['failed_login_attempts'];
    $now = new DateTime('now', new DateTimeZone('UTC'));
    
    
    if ( isset($user['locked_until']) && Utils::is_valid_datetime($user['locked_until']) ):

      $locked_until = new DateTime($user['locked_until'], new DateTimeZone('UTC'));

    else:

      $locked_until = null;

    endif;
  
    
    if ( $increment ):
      
      $this->increment_failed_login($user, false);
      
    endif;
    
    
    if ( $failed_login_attempts < 5 ):
      
      return false;
      
    endif;

  
    if ( is_null($locked_until) ):

      $new_locked_until = $now->modify('+5 minutes');
      
    elseif ($failed_login_attempts <= 10 ):

      $new_locked_until = max($now, $locked_until)->modify('+5 minutes');
  
    elseif ( $failed_login_attempts <= 15 ):

      $new_locked_until = max($now, $locked_until)->modify('+30 minutes');
  
    else:

      $new_locked_until = max($now, $locked_until)->modify('+1 hour');
  
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
   * Remove sensitive columns from a results set for use
   * in various contexts.
   *
   * @internal I have doubts that this is even necessary.
   *           It may be entirely unneded, but I just don't
   *           like seeing sensitive tokens in result sets
   *           in which they have no valid use case.
   **/
  private function sanitize_results($results, $fields) {
    

    switch ( $fields ):
    
      case 'all':
      
        break;
        
      case 'safe':
      default:
      
        $strip_columns = [
                          'password',
                          'remember_me',
                          'login_token',
                          'verify_key',
                          'password_reset_token'
                         ];
        
        
        if ( is_array( $results ) && isset( $results[0] ) ):
        
          foreach ( $results as &$result ):
            
            foreach ( $strip_columns as $column ):
              
              unset( $result[$column] );
              
            endforeach;
            
          endforeach;
          
        elseif ( is_array( $results ) ):
          
          foreach ( $strip_columns as $column ):
            
            unset( $results[$column] );
            
          endforeach;
          
        endif;
      
    endswitch;
    
    
    return $results;
    
    
  } // sanitize_results()
  
  
  
  
  
  
  
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
          `is_verified` BOOLEAN DEFAULT 0,
          `verify_key` VARCHAR(16) UNIQUE,
          `password_reset_token` VARCHAR(64),
          `password_reset_started` DATETIME,
          `failed_login_attempts` INTEGER DEFAULT 0,
          `login_token`  VARCHAR(16) UNIQUE,
          `locked_until` DATETIME,
          `is_banned` BOOLEAN DEFAULT 0,
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