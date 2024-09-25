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
    
      <h1>Enter reset key</h1>

      <p>Check your email.</p>
      
      
      
      <?php if ( $key_exists ): ?>
        
        <p>Reset key: <?php echo var_export($reset_key, true); ?></p>
        
        <p>Key valid: <?php echo var_export($key_valid, true); ?></p>
        
        <p>active_key_found: <?php echo var_export($active_key_found, true); ?></p>
        
      <?php else: ?>
        
        <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
      
          <input type="hidden" name="form_name" value="password-reset">
          <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
          
          <label for="ResetKey">Reset Key:</label>
          <input type="text" id="ResetKey" name="reset_key" autocapitalize="off" required>
          <br>
          
          <button type="submit">Submit</button>
        
        </form>
        
      <?php endif; ?>


      
    </main> <!-- .content -->


    <?php $page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>
