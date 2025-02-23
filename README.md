# cs-blog
Super basic blog, built for my dad.


## To-Do ✓list



- Document and clean everything.
  - Cast all method parameters and define return values where possible.
  - Class properties that are references to other classes should be uppercase.
  - Add phpdoc for every class and method explaining *why* it exists.
  - Inline document only tricky lines.
- Setup password reset email.
- Profile page to update display name.
- Public templates with forms need a `show_form` arg.
  - When `show_form` is false we need a `form_denied_msg` arg.
- Check all User db flags and datetimes are updated correctly.




- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [Trix](https://github.com/basecamp/trix) to handle post formatting as HTML and saving as Markdown.
- Revisit failed login process to remove cyclical functions and multiple db calls.
- Add debug log entries to check for multiple class instantiation and multiple crucial function alls.



## Requirements

- PHP 8