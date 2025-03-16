<?php



/**
 * @todo Think of a nicer way to handle including this js.
 *        Should I add a way to register css & js files,
 *        Or add an arg like 'needs_editor_js'?
 */
Actions::add_action('html-head', function(array $args = []) {
  
  $Page = Page::get_instance();
  
  $current_page = $Page->get_prop('cur_page');
  
  
  if ( 
      $current_page == 'admin/post/new' ||
      str_starts_with($current_page, 'admin/post/edit/')
     ):
    
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <?php
  
  endif;
  
});