<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head'); ?>

<body>
  

  
<?php $page->get_partial('page-header'); ?>
  

<div class="page-wrap">

  <div class="page-body">


    <?php $page->get_partial('sidebar/primary'); ?>


    <main class="content">
      
      <?php $page->get_partial('errors'); ?>
    
      <h1>Non-admin profile page.</h1>

      <p>Logged in as <strong><?php echo $cur_user['email']; ?></strong> (<?php echo $cur_user['selector']; ?>)</p>
    
      <p><?= Utils::format_date(); ?></p>
      
    </main> <!-- .content -->


    <?php $page->get_partial('sidebar/secondary'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $page->get_partial('page-footer'); ?>



</body>
</html>

  

