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
  
    <p><?= date("F j, Y, g:i a"); ?></p>

    
    <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
    
      <input type="hidden" name="form_name" value="verify">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
      
      <label for="VerifiyCode">One time code:</label>
      <input type="text" id="VerifyCode" name="verify_code" required>
      
      <button type="submit">Sign up</button>
    
    </form>

    
    
  </main>


</div>





</body>
</html>
