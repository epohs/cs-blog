<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  

<div class="page-wrap">

  
  <?php $page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

  <main class="content">
    
    <?php $page->get_partial('errors', null, false, 'admin/partials'); ?>
  
    <h1>Signup</h1>
    
    <p>Nonce: <?php echo $nonce; ?></p>
  
    <p><?= date("F j, Y, g:i a"); ?></p>

    
    <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
    
      <input type="hidden" name="form_name" value="signup">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
      
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>

      <label for="email">Password:</label>
      <input type="password" id="password" name="password" required>
      
      <button type="submit">Sign up</button>
    
    </form>

    
    
  </main>


</div>





</body>
</html>
