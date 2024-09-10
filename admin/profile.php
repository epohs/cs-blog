<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  

<div class="page-wrap">

  
  <?php $page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

  <main class="content">
    
    <?php $page->get_partial('errors', null, false, 'admin/partials'); ?>
  
    <h1>Admin Profile</h1>
  
    <a href="<?php echo $page->url_for('/'); ?>">Home</a>
    
  </main>


</div>





</body>
</html>
