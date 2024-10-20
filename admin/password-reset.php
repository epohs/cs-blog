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
      
      
      
      <?php if ( $active_key_found ): ?>
        
        <p>Enter new password</p>

        <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">

          <input type="hidden" name="form_name" value="password-reset">
          <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

          <label for="Password">New Password:</label>
          <input type="password" id="Password" name="new_pass" required>

          <button type="submit">Submit</button>

        </form>
        
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
