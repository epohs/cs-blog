# Next Steps: Security & Code Quality

This document outlines security vulnerabilities, code quality issues, and architectural improvements to address before release.

---

## Critical Security Issues


### 1. Timing Attack Vulnerability in Remember-Me Token Lookup

**File:** `classes/User.php:275-283`

The `remember_me` token lookup uses a SQL query that searches JSON data. The database comparison time varies based on the token being compared, potentially leaking information through timing analysis.

**Fix:** Use `hash_equals()` for constant-time comparison after fetching the user record.

---

### 2. Session Fixation Window

**File:** `init.php:14`

`session_start()` is called unconditionally at the top of `init.php`, but session regeneration only happens in `Auth::login()`. This creates a window where an attacker could fixate a session ID before login.

**Fix:** Regenerate session ID on privilege escalation (verification) as well. Review all authentication state changes.

---

### 3. Weak Password Validation

**File:** `classes/User.php:802-827`

Password validation is minimal:
- Only checks length
- Checks against a hardcoded list of 10 common passwords

Common password lists should have thousands of entries.

**Fix:**
- Expand the common passwords list significantly
- Consider checking against haveibeenpwned API (optional, adds dependency)
- Add complexity requirements as a config option

---

### 4. Password Reset Token Not Hashed

**File:** `classes/User.php:1176-1186`

Unlike remember-me tokens (correctly hashed with SHA-256), password reset tokens are stored in plaintext:

```php
$new_reset_token = $this->Db->get_unique_column_val('Users', 'password_reset_token', ['min_len' => 16]);
// ...
$is_good_token = $this->set_column('password_reset_token', $new_reset_token, $user_id);
```

If the database is compromised, attackers can reset any user's password.

**Fix:** Hash password reset tokens before storage, similar to remember-me tokens.

---

## Medium-Priority Issues

### 5. Static Method Bug

**File:** `classes/Routing.php:521`

Bug in `clean_post_vars()`:

```php
public static function clean_post_vars(array $post): array {
    // ...
    if (is_array($value)):
        $sanitized[$key] = $this->clean_post_vars($value);  // $this in static method
```

`$this` doesn't exist in a static method. This will fatal error on nested form data.

**Fix:** Change to `self::clean_post_vars($value)`

---

### 6. Verbose Debug Logging

**Files:** `classes/FormHandler.php:328-332, 449-450`

Sensitive data is logged in debug mode:

```php
debug_log('Posted user selector: ' . var_export($posted_selector, true));
debug_log('user_to_edit: ' . var_export($user_to_edit, true));
```

Even in debug mode, logging full user records (which may include tokens) to a file is risky.

**Fix:** Sanitize logged data or only log non-sensitive fields.

---

### 7. No Rate Limiting on Signup

**File:** `classes/FormHandler.php:1014-1104`

Login has rate limiting, but signup doesn't:

```php
$this->Limits->set('form_login', 5, '5 minutes');
// No limit for signup
```

This allows attackers to create unlimited accounts or enumerate valid email addresses.

**Fix:** Add rate limiting to signup similar to login.

---

### 8. Remember-Me Token Accumulation

**File:** `classes/User.php:889-917`

Tokens accumulate per user without a hard limit. An attacker could force a user to log in from many browsers, bloating the JSON column.

**Fix:** Add a maximum token count (e.g., 10) and remove oldest tokens when exceeded.

---

## Code Quality Improvements

### Long Methods to Refactor

These methods exceed 80 lines and should be broken into smaller functions:

| Method | File | Lines |
|--------|------|-------|
| `login()` | `FormHandler.php` | ~150 |
| `is_logged_in()` | `User.php` | ~80 |
| `edit_user()` | `FormHandler.php` | ~170 |
| `password_reset()` | `FormHandler.php` | ~100 |

---

### Unused Utility Functions

`Utils::is_valid_selector()` exists but is never called. Several `@todo` comments mention adding selector validation.

**Action:** Use this function in `FormHandler` methods where selectors are received from POST data.

---

### Inconsistent Static/Instance Methods

Classes like `Session`, `Auth`, and `Routing` inconsistently mix static and instance methods.

| Class | Static Methods | Instance Methods |
|-------|---------------|------------------|
| Session | All | None |
| Auth | `set_nonce`, `validate_nonce`, `remove_expired_nonces` | `login`, `logout` |
| Routing | `is_route`, `redirect_to`, `redirect_with_alert`, `nonce_redirect`, `clean_post_vars` | `serve_route`, `get_path` |

**Action:** Document your reasoning for doing this (If some methods of a class don't rely on an instant's state, and if another class's method only uses those stateless methods, then I think it is cleaner to be able to use static methods than to instantiate and reference a class variable). Also, though, audit your code to be more sure that you are using the methods the way you are saying you are.

---

### Singleton Pattern Considerations

Every class uses the singleton pattern. This makes testing difficult and creates hidden dependencies.

For a project intended to teach good practices, consider:
- Dependency injection for core services
- Constructor injection rather than `get_instance()` calls within methods
- At minimum, document why singletons were chosen

---

## Documentation Tasks

From README to-do list, still pending:

- [ ] Cast all method parameters and define return values where possible
- [ ] Class properties that are references to other classes should be uppercase
- [ ] Add PHPDoc for every class and method explaining *why* it exists
- [ ] Inline document only tricky lines
- [ ] Profile page to update display name
- [ ] Public templates with forms need a `show_form` arg
- [ ] Check all User db flags and datetimes are updated correctly

---

## Additional Recommendations

### Security Headers

Consider adding security headers in `init.php` or via `.htaccess`:

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

### Content Security Policy

For the admin panel especially, consider a strict CSP header.

### Database Backup Strategy

Document a backup strategy for the SQLite database file.

### Error Handling

Add a global exception handler for production to prevent stack traces from leaking to users:

```php
set_exception_handler(function($e) {
    debug_log($e->getMessage());
    http_response_code(500);
    // Show generic error page
});
```

