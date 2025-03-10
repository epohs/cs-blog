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
    
      <?php if ( $User->is_admin() ): ?>
      <h1>Admin profile page.</h1>
      <?php else: ?>
      <h1>Non-admin profile page.</h1>
      <?php endif; ?>

      <p>Logged in as <strong><?php echo $cur_user['email']; ?></strong> (<?php echo $cur_user['selector']; ?>)</p>
      
      <p>Your IP: <?php echo Utils::get_client_ip(); ?></p>

      <?php if ( !$User->is_verified() ): ?>
        <p>You need to <a href="<?php echo $Page->url_for('verify'); ?>">verify your email address</a>.</p>
      <?php endif; ?>

      <details><summary>Session:</summary> <?php echo var_export($_SESSION, true); ?></details>
    
      <p><?= Utils::format_date(); ?></p>
      
    </main> <!-- .content -->


    <?php $Page->get_partial('sidebar/secondary'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer'); ?>



</body>
</html>

  

