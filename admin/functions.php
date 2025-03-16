<?php




Actions::add_action('html-head', function(array $args = []) {
  
  $Page = Page::get_instance();
  
  $current_page = $Page->get_prop('cur_page');
  
  echo "<p>This is extra content for the html head on: {$current_page}</p>";
  
});