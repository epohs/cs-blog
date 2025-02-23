<!DOCTYPE html>
<html lang="en">

<?php $Page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  
  <?php $Page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

<div class="page-wrap">

  <div class="page-body">

    <?php $Page->get_partial('primary', null, false, 'admin/partials/sidebar'); ?>
    

    <main class="content">
      
      <?php $Page->get_partial('page-alerts', null, false, 'admin/partials'); ?>
    
      <h1>Admin Dashboard</h1>
      
    </main> <!-- .content -->


    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>