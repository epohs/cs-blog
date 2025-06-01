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

In most places I chose simplicity and clarity in the code over scaling and optimization. I don't expect to use this codebase on larger projects, so I chose to write clean, simple code where possible to both make it easier to follow my own train of thought, and to maintain the project if I ever do decide to revisit it later.

The implementation of pages is quite limited. I wanted this project to be, primarily, an old-school blogging platform, not a full featured CMS. The project is designed first and foremost to put blog posts front and center. And, to be frank, the idea of dynamic pages in this project didn't interest me much, so I didn't bother building out the framework to support a lot of dynamic pages. That being the case, it probably will feel cumbersome and limited if you attempt to create a robust page structure, and performance may degrade if you try.  Specifically, routing dynamic page URLs is neither cached nor optimized.

I chose to store a Post's Category IDs as an array in a JSON column in the Posts table rather than using the more tradidtional pivot table to link Posts to their Categories. I did this for two reasons. 1.) Because pivot tables have always felt just a litle irksome to me. An additional database table, and an additional level of abstraction. I understand why they're good and useful, but for a personal project that I expect to remain fairly small their performance and scaling benefit lost the battle against my minor distaste for them in general... because, 2.) I've never used the JSON column type in this way so it felt interesting and compelling for me to try here.

The choice to avoid pivot tables does add some complexity to the SQL statements around Posts and Categories. And, it does hurt the ability of the project to scale, both in terms of the number of overall Posts and Categories, as well as site traffic. Let this serve as a warning to not use this project in cases where you expect the site to grow very large or very popular.

The decision to store Category IDs as a JSON array probably seems all the more odd when you realize that in its current state the project only provides for a Post to be in a single Category anyway. You're not wrong. It is odd. However, I really wanted to try storing Category IDs this way, so I did it. But, I realize that the ability to place a Post in multiple Categories is, in many cases, a useful thing, so I wanted to bake support for it in on a low level. I don't personally care to use it so I didn't expose that in the templates. If you did want to add that ability, though, a good bit of the work is already in place for it.