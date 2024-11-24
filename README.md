# cs-blog
Super basic blog, built for my dad.


## To-Do


- Ensure client passes an IP and supports persistant sessions.
  - Create a new method that will return a bool.
  - Rate limited routes should check this before checking rate limits and
  redirect with an error or deny a form if false.
- Remove get_unique_column_val() in User. Use Db instead.
- Move Auth::is_logged_in() and is_admin() to User.
- Move form-handler out of admin.
- Merge profile pages and move out of admin.
- All templates need User passed as an arg.
- Templates with forms need a `show_form` arg.
  - When `show_form` is false we need a `form_denied_msg` arg.
- Rework on-page error display to handle non-error messages.
  - Create a new Routing::redirect_with_err() method. This method will take a url_for
  string, and a level string, and a code string, and optional data array. It will set 
  a session var with this level, and code, and data, then it will redirect_to the url with the query var.
  - Change all redirect_to() that have errors to redirect_with_err().
  - Rename Page::errors property to page_messages.
  - Move $acceptable_levels to a class property. Set in construct.
  - Rename all Page methods that are _error related to more generic
  - Change Page::handle_queryvar_errs() to a more generic handle_queryvar_msg() that will parse the message type.
    - This method will check the error code and level against the session and if they
    don't match, redirect.
    - It will also look for data in the session and build the output using this if
    it is there.
- If User is not verified:
  - Block all forms other than verify with link to /verify/.
  - Any form submission other than verify should redirect to /verify/.
- Document and clean everything.
  - Cast all method parameters and define return values where possible.
  - Class properties that are references to other classes should be uppercase.
  - Add phpdoc for every class and method explaining *why* it exists.
  - Inline document only tricky lines.
- Profile page to update display name.
- Delete expired nonces.
- Check all User db flags and datetimes are updated correctly.
- Revisit failed login process to remove cyclical functions and multiple db calls.



- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [Trix](https://github.com/basecamp/trix) to handle post formatting as HTML and saving as Markdown.




## Requirements

- PHP 8