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
    
      <h1>Reset password</h1>
      
      <p>Route vars: <?php echo var_export($route_vars, true); ?></p>

      <?php /*
  
      <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
      
        <input type="hidden" name="form_name" value="forgot">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        
        <label for="Email">Email:</label>
        <input type="text" id="Email" name="email" autocapitalize="off" required>
        <br>
        
        <button type="submit">Reset password</button>
      
      </form>
      */ ?>
      
    </main> <!-- .content -->


    <?php $page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>
