<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  

<div class="page-wrap">

  
  <?php $page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

  <main class="content">
    
    <?php $page->get_partial('errors', null, false, 'admin/partials'); ?>
  
    <h1>Verify</h1>
    
    <p>Nonce: <?php echo $nonce; ?></p>
  
    <p>User selector: <?php echo Session::get_key('user_selector'); ?></p>
    
    <p>Verify key: <?= var_export($verify_key, true); ?></p>

    
    <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
    
      <input type="hidden" name="form_name" value="verify">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
      
      <label for="VerifiyKey">One time code:</label>
      <input type="text" id="VerifyKey" name="verify_key" required>
      
      <button type="submit">Sign up</button>
    
    </form>

    
    
  </main>


</div>





</body>
</html>
