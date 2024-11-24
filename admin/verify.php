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
    
      <h1>Verify</h1>

      <p>Verify key: <?= var_export($verify_key, true); ?></p>
    
      <form method="POST" action="<?php echo $page->url_for('form-handler'); ?>">

        <input type="hidden" name="form_name" value="verify">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        
        <label for="VerifiyKey">One time code:</label>
        <input type="text" id="VerifyKey" name="verify_key" required>
        
        <button type="submit">Sign up</button>

      </form>
      
    </main> <!-- .content -->


    <?php $page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>