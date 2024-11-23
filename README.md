# cs-blog
Super basic blog, built for my dad.


## To-Do

- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [Trix](https://github.com/basecamp/trix) to handle post formatting as HTML and saving as Markdown.




- If User is not verified we need to handle them differently.
  - Add a link to /verify/ in the user menu.
  - Block all forms other than verify with link to /verify/.
  - Any form submission other than verify should redirect to /verify/.
- Move form-handler out of admin.
- Merge profile pages and move out of admin.
- Document and clean everything
  - Cast all method parameters and define return values where possible
  - Class properties that are references to other classes should be uppercase
  - Add phpdoc for every class and method explaining *why* it exists
  - Inline document only tricky lines
- Rework on-page error display to handle non-error messages
- Profile page to update display name
- Delete expired nonces
- Check all User db flags and datetimes are updated correctly
- Revisit failed login process to remove cyclical functions and multiple db calls



## Requirements

- PHP 8