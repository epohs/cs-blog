# cs-blog
Super basic blog, built for my dad.


## To-Do

- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [Trix](https://github.com/basecamp/trix) to handle post formatting as HTML and saving as Markdown.



- Restructure config value handling
  - Look into best practice for config file format
  - Create Defaults class
  - Config::get() should check the config file first, then db settings table, 
    then Defaults class
- Look into rate limiting with `symfony/rate-limiter`
  - Failed login lockout
- Document and clean everything
  - Cast all method parameters and define return values where possible
  - Class properties that are references to other classes should be uppercase
  - Add phpdoc for every class and method explaining *why* it exists
  - Inline document only tricky lines
- Rework on-page error display to handle non-error messages
- Profile page to update display name
- Delete expired nonces
- Check all User db flags and datetimes are updated correctly
