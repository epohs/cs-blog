# cs-blog
A blog, built for my dad.


## To-Do ✓list



- Setup password reset email.
- Document and clean everything.
  - Cast all method parameters and define return values where possible.
  - Class properties that are references to other classes should be uppercase.
  - Add phpdoc for every class and method explaining *why* it exists.
  - Inline document only tricky lines.
- Profile page to update display name.
- Public templates with forms need a `show_form` arg.
  - When `show_form` is false we need a `form_denied_msg` arg.
- Check all User db flags and datetimes are updated correctly.




- Proceed with [HTML to Markdown](https://github.com/thephpleague/html-to-markdown) and [Parsedown](https://github.com/erusev/parsedown) and [EasyMDE](https://github.com/Ionaru/easy-markdown-editor) to handle post formatting as HTML and saving as Markdown.
- Revisit failed login process to remove cyclical functions and multiple db calls.
- Add debug log entries to check for multiple class instantiation and multiple crucial function calls.



## Requirements

- PHP 8
- You'll get better URL slugs if you have the transliterator_transliterate PHP module.


## Compromises I made

In most places I chose simplicity and clarity in the code over scaling and optimization. I don't expect to use this codebase on larger projects, so I chose to write clean, simple code where possible to both make it easier to follow my chain of thought, and to maintain the project if I ever do decide to revisit it.

The implementation of pages is quite limited. I wanted this project to be, primarily, an old-school blogging platform, not a full featured CMS. The project is designed first and foremost to put blog posts front and center. And, to be frank, the idea of dynamic pages in this project didn't interest me much, so I didn't bother building out the framework to support a lot of dynamic pages. That being the case, it probably will feel cumbersome and limited if you attempt to create a robust page structure, and performance may degrade if you try.  Specifically, routing dynamic page URLs is neither cached nor optimized.

I chose to store a post's category IDs as an array in a JSON column in the Posts table rather than using the more tradidtional pivot table to link Posts to their Categories. I did this for two reasons. 1.) Because pivot tables have always felt just a litle irksome to me. An additional database table, and an extra level of abstraction. I understand why they're good and useful, but for a project that I expect to be fairly small scaled I chose to not use them. And, 2.) I've never used the JSON column type in this way so it felt interesting to try here.