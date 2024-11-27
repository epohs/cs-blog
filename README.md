# cs-blog
Super basic blog, built for my dad.


## To-Do ✓list


- Rework on-page error display to handle non-error messages.
  ✓ Create a new Routing::redirect_with_err() method. This method will take a url_for
  string, and a level string, and a code string, and optional data array. It will set 
  a session var with this level, and code, and data, then it will redirect_to the url with the query var.
  - Change all redirect_to() that have errors to redirect_with_err().
  - Rename Page::errors property to page_messages.
  - Move $acceptable_levels to a class property. Set in construct.
  - Rename all Page methods that are _error related to more generic
- Document and clean everything.
  - Cast all method parameters and define return values where possible.
  - Class properties that are references to other classes should be uppercase.
  - Add phpdoc for every class and method explaining *why* it exists.
  - Inline document only tricky lines.
- Profile page to update display name.
- Public templates with forms need a `show_form` arg.
  - When `show_form` is false we need a `form_denied_msg` arg.
- Check all User db flags and datetimes are updated correctly.




- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [Trix](https://github.com/basecamp/trix) to handle post formatting as HTML and saving as Markdown.
- Revisit failed login process to remove cyclical functions and multiple db calls.
- Add debug log entries to check for multiple class instantiation and multiple crucial function alls.



## Requirements

- PHP 8