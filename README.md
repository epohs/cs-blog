# cs-blog
Super basic blog, built for my dad.


## To-Do

- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [Trix](https://github.com/basecamp/trix) to handle post formatting as HTML and saving as Markdown.


- Restructure Routing
  - Create Routing class that will handle routing logic
    - This class will determine whether a route is registered, and then
      whether the route is valid, then pass to the Routes, or FormHandler classes
  - Routes class will register and hold route specific methods
  - FormHandler class will be the same as Routes but for form specific methods
- Restructure config value handling
  - Look into best practice for config file format
  - Create Defaults class
  - Config::get() should check the config file first, then db settings table, 
    then Defaults class
- Failed login lockout
- Document and clean everything
  - Cast all method parameters and define return values where possible
  - Add phpdoc for every class and method explaining *why* it exists
  - Inline document only tricky lines
- Rework on-page error display to handle non-error messages
- Profile page to update display name
- Delete expired nonces
- Check all User db flags and datetimes are updated correctly
