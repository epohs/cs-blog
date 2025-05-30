<!DOCTYPE html>
<html lang="en">

<?php $Page->get_partial('html-head'); ?>

<body>
  

  
<?php $Page->get_partial('page-header'); ?>
  

<div class="page-wrap">

  <div class="page-body">


    <?php $Page->get_partial('sidebar/primary'); ?>


    <main class="content">
      
      <?php $Page->get_partial('page-alerts'); ?>
    
      <?php $Page->get_partial("content/$template_content", null, $args, ''); ?>
      
    </main> <!-- .content -->


    <?php $Page->get_partial('sidebar/secondary'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer'); ?>



</body>
</html>
