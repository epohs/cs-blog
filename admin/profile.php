<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  
<?php $page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

<div class="page-wrap">

  <div class="page-body">

    <?php $page->get_partial('primary', null, false, 'admin/partials/sidebar'); ?>
    

    <main class="content">
      
      <?php $page->get_partial('errors', null, false, 'admin/partials'); ?>
    
      <h1>Admin Profile</h1>

      <p>Logged in as <strong><?php echo $cur_user['email']; ?></strong> (<?php echo $cur_user['selector']; ?>)</p>

      <details><summary>Session:</summary> <?php echo var_export($_SESSION, true); ?></details>
      
    </main> <!-- .content -->


    <?php $page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>