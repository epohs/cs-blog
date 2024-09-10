<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head'); ?>

<body>
  

  

<div class="page-wrap">

  
  <?php $page->get_partial('page-header'); ?>
  

  <main class="content">
    
    <?php $page->get_partial('errors'); ?>
  
    <h1>Hello.</h1>
  
    <p><?= Utils::format_date(); ?></p>
    
  </main>


</div>





</body>
</html>
