# cs-blog
Super basic blog, built for my dad.


## To-Do ✓list


- Move page alerts to a new class Alerts.
  - Put Alerts instantialization ahead of Config if possible.
    - Move config alerts to use this new class.
  - Add new debug level page alerts that get filtered out if not in debug.
- Rough in Email class to actually send emails.
  - Add email-templates folder to admin directory.
    - Try to add curly bracket templating to these.
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